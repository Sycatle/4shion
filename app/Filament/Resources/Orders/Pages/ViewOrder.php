<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Pages;

use App\Actions\Orders\GenerateInvoice;
use App\Actions\Orders\MarkOrderShipped;
use App\Actions\Orders\RefundOrder;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/** @property Order $record */
final class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ship')
                ->label('Marquer expédiée')
                ->icon(Heroicon::OutlinedTruck)
                ->color('info')
                ->visible(fn (): bool => $this->record->status->canTransitionTo(OrderStatus::Shipped))
                ->requiresConfirmation()
                ->modalDescription('Le client recevra un email de confirmation d’expédition.')
                ->action(function (MarkOrderShipped $markOrderShipped): void {
                    $markOrderShipped($this->record);
                    $this->refreshRecord();
                }),
            Action::make('deliver')
                ->label('Marquer livrée')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn (): bool => $this->record->status->canTransitionTo(OrderStatus::Delivered))
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->transitionTo(OrderStatus::Delivered);
                    $this->refreshRecord();
                }),
            Action::make('invoice')
                ->label('Facture PDF')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->visible(fn (): bool => $this->record->status->isPaid() || $this->record->status === OrderStatus::Refunded)
                ->action(fn (GenerateInvoice $generateInvoice): StreamedResponse => response()->streamDownload(
                    fn () => print ($generateInvoice($this->record)->output()),
                    GenerateInvoice::filename($this->record),
                )),
            Action::make('refund')
                ->label('Rembourser')
                ->icon(Heroicon::OutlinedReceiptRefund)
                ->color('danger')
                ->visible(fn (): bool => $this->record->status->canTransitionTo(OrderStatus::Refunded))
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

    private function refreshRecord(): void
    {
        $this->record->refresh();
        $this->refreshFormData(['status', 'shipped_at']);
    }
}
