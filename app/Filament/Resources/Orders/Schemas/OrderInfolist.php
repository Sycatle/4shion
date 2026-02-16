<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Commande')
                    ->columns(3)
                    ->columnSpanFull()
                    ->components([
                        TextEntry::make('number')->label('Numéro'),
                        TextEntry::make('status')
                            ->label('Statut')
                            ->badge()
                            ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                            ->color(fn (OrderStatus $state): string => $state->color()),
                        TextEntry::make('user.name')->label('Client'),
                        TextEntry::make('created_at')->label('Passée le')->dateTime('d/m/Y H:i'),
                        TextEntry::make('paid_at')->label('Payée le')->dateTime('d/m/Y H:i')->placeholder('—'),
                        TextEntry::make('shipped_at')->label('Expédiée le')->dateTime('d/m/Y H:i')->placeholder('—'),
                    ]),
                Section::make('Articles')
                    ->columnSpanFull()
                    ->components([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->columns(4)
                            ->components([
                                TextEntry::make('product_name')->label('Produit'),
                                TextEntry::make('variant_label')->label('Variante'),
                                TextEntry::make('quantity')->label('Quantité'),
                                TextEntry::make('total')->label('Total')->money('EUR', divideBy: 100),
                            ]),
                    ]),
                Section::make('Livraison')
                    ->components([
                        TextEntry::make('shipping_address')
                            ->hiddenLabel()
                            ->state(fn ($record): string => self::formatAddress($record->shipping_address))
                            ->html(),
                    ]),
                Section::make('Facturation')
                    ->components([
                        TextEntry::make('billing_address')
                            ->hiddenLabel()
                            ->state(fn ($record): string => self::formatAddress($record->billing_address))
                            ->html(),
                    ]),
                Section::make('Montants')
                    ->columns(4)
                    ->columnSpanFull()
                    ->components([
                        TextEntry::make('subtotal')->label('Sous-total')->money('EUR', divideBy: 100),
                        TextEntry::make('discount_total')
                            ->label(fn ($record): string => $record->coupon_code ? "Remise ({$record->coupon_code})" : 'Remise')
                            ->money('EUR', divideBy: 100),
                        TextEntry::make('shipping_total')->label('Livraison')->money('EUR', divideBy: 100),
                        TextEntry::make('total')->label('Total TTC')->money('EUR', divideBy: 100)->weight('bold'),
                    ]),
            ]);
    }

    /** @param  array<string, string|null>  $address */
    private static function formatAddress(array $address): string
    {
        return collect([
            trim(($address['first_name'] ?? '').' '.($address['last_name'] ?? '')),
            $address['line1'] ?? null,
            $address['line2'] ?? null,
            trim(($address['postal_code'] ?? '').' '.($address['city'] ?? '')),
            $address['country'] ?? null,
            $address['phone'] ?? null,
        ])->filter()->map(fn (string $line): string => e($line))->implode('<br>');
    }
}
