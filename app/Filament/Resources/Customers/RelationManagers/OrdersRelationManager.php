<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;

final class OrdersRelationManager extends RelationManager
{
    #[Override]
    protected static string $relationship = 'orders';

    #[Override]
    protected static ?string $title = 'Commandes';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->label('Numéro'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                    ->color(fn (OrderStatus $state): string => $state->color()),
                TextColumn::make('total')->label('Total TTC')->money('EUR', divideBy: 100),
                TextColumn::make('created_at')->label('Passée le')->dateTime('d/m/Y'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make()->url(fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
