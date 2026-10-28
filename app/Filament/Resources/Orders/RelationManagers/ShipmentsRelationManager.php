<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Enums\Carrier;
use App\Models\Shipment;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;

final class ShipmentsRelationManager extends RelationManager
{
    #[Override]
    protected static string $relationship = 'shipments';

    #[Override]
    protected static ?string $title = 'Colis';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('shipped_at')->label('Expédié le')->dateTime('d/m/Y H:i'),
                TextColumn::make('carrier')->label('Transporteur')->formatStateUsing(fn (mixed $state): string => $state instanceof Carrier ? $state->label() : '—'),
                TextColumn::make('tracking_number')->label('Suivi')->placeholder('—')->copyable(),
                TextColumn::make('items_summary')
                    ->label('Contenu')
                    ->getStateUsing(fn (Shipment $record): string => $record->items()->with('orderItem')->get()->map(fn ($line): string => "{$line->quantity} × {$line->orderItem->product_name}")->implode(', ')),
            ])
            ->recordActions([
                Action::make('track')
                    ->label('Suivre')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Shipment $record): ?string => $record->trackingUrl(), shouldOpenInNewTab: true)
                    ->visible(fn (Shipment $record): bool => $record->trackingUrl() !== null),
            ]);
    }
}
