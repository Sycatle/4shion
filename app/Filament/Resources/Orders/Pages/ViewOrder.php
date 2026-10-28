<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Pages;

use App\Actions\Orders\CreateShipment;
use App\Actions\Orders\GenerateInvoice;
use App\Actions\Orders\GeneratePackingSlips;
use App\Actions\Orders\MarkOrderShipped;
use App\Actions\Orders\RefundOrder;
use App\Enums\Carrier;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;
use Override;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/** @property Order $record */
final class ViewOrder extends ViewRecord
{
    #[Override]
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ship')
                ->label('Marquer expédiée')
                ->icon(Heroicon::OutlinedTruck)
                ->color('info')
                ->visible(fn (): bool => $this->record->status->canTransitionTo(OrderStatus::Shipped))
                ->modalDescription('Expédie tout ce qui reste en un seul colis. Le client recevra un email de confirmation d’expédition.')
                ->schema($this->trackingFields())
                ->action(function (array $data, MarkOrderShipped $markOrderShipped): void {
                    $shipped = $markOrderShipped($this->record, $this->carrierFrom($data), $this->trackingFrom($data));
                    $this->refreshRecord();

                    if (! $shipped) {
                        Notification::make()->title('Commande non expédiable')->body('Elle n’est pas payée ou attend encore des précommandes : utilisez l’expédition partielle.')->warning()->send();
                    }
                }),
            Action::make('partialShip')
                ->label('Expédier une partie')
                ->icon(Heroicon::OutlinedTruck)
                ->color('gray')
                ->visible(fn (): bool => $this->record->status === OrderStatus::Paid)
                ->modalDescription('Indiquez les quantités qui partent dans ce colis.')
                ->fillForm(fn (): array => ['lines' => $this->record->items->map(fn (OrderItem $item): array => ['id' => $item->id, 'label' => "{$item->product_name} ({$item->variant_label})", 'quantity' => 0, 'max' => $item->shippableQuantity()])->filter(fn (array $line): bool => $line['max'] > 0)->values()->all()])
                ->schema([
                    Repeater::make('lines')
                        ->label('Articles')
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->schema([
                            Hidden::make('id'),
                            Hidden::make('max'),
                            TextInput::make('label')->label('Article')->disabled()->dehydrated(false),
                            TextInput::make('quantity')->label('Quantité')->numeric()->integer()->minValue(0)->maxValue(fn (Get $get): int => is_numeric($get('max')) ? (int) $get('max') : 0)->default(0),
                        ])
                        ->columns(2),
                    ...$this->trackingFields(),
                ])
                ->action(function (array $data, CreateShipment $createShipment): void {
                    $quantities = [];

                    foreach (is_array($data['lines'] ?? null) ? $data['lines'] : [] as $line) {
                        if (is_array($line) && is_numeric($line['id'] ?? null) && is_numeric($line['quantity'] ?? null)) {
                            $quantities[(int) $line['id']] = (int) $line['quantity'];
                        }
                    }

                    try {
                        $createShipment($this->record, $quantities, $this->carrierFrom($data), $this->trackingFrom($data));
                    } catch (InvalidArgumentException $e) {
                        Notification::make()->title('Expédition refusée')->body($e->getMessage())->danger()->send();

                        return;
                    }

                    $this->refreshRecord();
                    Notification::make()->title('Colis enregistré')->success()->send();
                }),
            Action::make('deliver')
                ->label('Marquer livrée')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn (): bool => $this->record->status->canTransitionTo(OrderStatus::Delivered))
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->transitionTo(OrderStatus::Delivered, ['delivered_at' => now()]);
                    $this->refreshRecord();
                }),
            Action::make('invoice')
                ->label('Facture PDF')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->visible(fn (): bool => $this->record->status->isPaid() || $this->record->status === OrderStatus::Refunded)
                ->action(fn (GenerateInvoice $generateInvoice): StreamedResponse => response()->streamDownload(
                    function () use ($generateInvoice): void {
                        echo $generateInvoice($this->record)->output();
                    },
                    GenerateInvoice::filename($this->record),
                )),
            Action::make('packingSlip')
                ->label('Bon de préparation')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('gray')
                ->visible(fn (): bool => $this->record->status->isPaid())
                ->action(fn (GeneratePackingSlips $generate): StreamedResponse => response()->streamDownload(
                    function () use ($generate): void {
                        echo $generate([$this->record])->output();
                    },
                    "bon-de-preparation-{$this->record->number}.pdf",
                )),
            Action::make('refund')
                ->label('Rembourser')
                ->icon(Heroicon::OutlinedReceiptRefund)
                ->color('danger')
                ->visible(fn (): bool => $this->record->status->canTransitionTo(OrderStatus::Refunded) && $this->record->refunded_total === 0)
                ->requiresConfirmation()
                ->modalHeading('Rembourser la commande ?')
                ->modalDescription('Le montant total sera remboursé au client et les articles remis en stock. Cette action est irréversible.')
                ->action(function (RefundOrder $refundOrder): void {
                    try {
                        $refundOrder($this->record);
                    } catch (Throwable $e) {
                        report($e);
                        Notification::make()->title('Le remboursement a échoué')->body('Le paiement n’a pas pu être remboursé. Réessayez ou consultez Stripe.')->danger()->send();

                        return;
                    }

                    $this->refreshRecord();
                    Notification::make()->title('Commande remboursée')->success()->send();
                }),
        ];
    }

    /** @return list<Component> */
    private function trackingFields(): array
    {
        return [
            Select::make('carrier')
                ->label('Transporteur')
                ->options(collect(Carrier::cases())->mapWithKeys(fn (Carrier $carrier): array => [$carrier->value => $carrier->label()])),
            TextInput::make('tracking_number')->label('Numéro de suivi')->maxLength(100),
        ];
    }

    /** @param  array<mixed>  $data */
    private function carrierFrom(array $data): ?Carrier
    {
        return is_string($data['carrier'] ?? null) ? Carrier::tryFrom($data['carrier']) : null;
    }

    /** @param  array<mixed>  $data */
    private function trackingFrom(array $data): ?string
    {
        return is_string($data['tracking_number'] ?? null) ? $data['tracking_number'] : null;
    }

    private function refreshRecord(): void
    {
        $this->record->refresh();
        $this->refreshFormData(['status', 'shipped_at']);
    }
}
