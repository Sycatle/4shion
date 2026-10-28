<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Tables;

use App\Actions\Orders\GeneratePackingSlips;
use App\Actions\Orders\MarkOrderShipped;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\CsvExport;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->columns([
                TextColumn::make('number')
                    ->label('Numéro')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('user.name')
                    ->label('Client')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                    ->color(fn (OrderStatus $state): string => $state->color()),
                TextColumn::make('total')
                    ->label('Total TTC')
                    ->money('EUR', divideBy: 100)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Passée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(fn (OrderStatus $status): array => [$status->value => $status->label()])),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('packingSlip')
                    ->label('Bon de préparation')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->color('gray')
                    ->visible(fn (Order $record): bool => $record->status->isPaid())
                    ->action(fn (Order $record): StreamedResponse => self::packingSlips([$record])),
            ])
            ->headerActions([
                Action::make('export')
                    ->label('Exporter en CSV')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->action(fn (): StreamedResponse => self::export()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('packingSlips')
                        ->label('Bons de préparation')
                        ->icon(Heroicon::OutlinedPrinter)
                        ->action(fn (Collection $records): StreamedResponse => self::packingSlips(Order::query()->whereKey($records->modelKeys())->get())),
                    BulkAction::make('ship')
                        ->label('Marquer expédiées')
                        ->icon(Heroicon::OutlinedTruck)
                        ->requiresConfirmation()
                        ->modalDescription('Les clients recevront un email d’expédition. Les commandes non expédiables (non payées, précommandes en attente) sont ignorées.')
                        ->action(function (Collection $records, MarkOrderShipped $markOrderShipped): void {
                            $shipped = Order::query()->whereKey($records->modelKeys())->get()->filter(fn (Order $order): bool => $markOrderShipped($order))->count();

                            Notification::make()
                                ->title("{$shipped} commande(s) expédiée(s)")
                                ->body($shipped < $records->count() ? ($records->count() - $shipped).' ignorée(s) : statut ou précommande non éligible.' : null)
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->recordUrl(fn (Order $record): string => route('filament.admin.resources.orders.view', $record));
    }

    /** @param  iterable<Order>  $orders */
    private static function packingSlips(iterable $orders): StreamedResponse
    {
        $pdf = app(GeneratePackingSlips::class)($orders);

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf->output();
        }, 'bons-de-preparation-'.now()->format('Y-m-d-His').'.pdf');
    }

    /** Every order matching the active filters, one line per order. */
    private static function export(): StreamedResponse
    {
        $rows = (function (): iterable {
            foreach (Order::query()->with('user')->orderBy('id')->lazyById() as $order) {
                yield [
                    $order->number,
                    $order->created_at?->format('Y-m-d H:i'),
                    $order->status->label(),
                    $order->user->email,
                    number_format($order->subtotal / 100, 2, '.', ''),
                    number_format($order->discount_total / 100, 2, '.', ''),
                    number_format($order->shipping_total / 100, 2, '.', ''),
                    number_format($order->total / 100, 2, '.', ''),
                    $order->coupon_code,
                    $order->promotion_name,
                ];
            }
        })();

        return CsvExport::download(
            'commandes-'.now()->format('Y-m-d').'.csv',
            ['Numéro', 'Date', 'Statut', 'Client', 'Sous-total', 'Remise', 'Livraison', 'Total TTC', 'Code promo', 'Promotion'],
            $rows,
        );
    }
}
