<?php

declare(strict_types=1);

namespace App\Filament\Resources\StockMovements;

use App\Enums\StockMovementReason;
use App\Filament\Concerns\RestrictedByRole;
use App\Filament\Resources\StockMovements\Pages\ListStockMovements;
use App\Models\StockMovement;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * Read-only: the ledger is only written by StockLedger.
 */
final class StockMovementResource extends Resource
{
    use RestrictedByRole;

    protected static function area(): string
    {
        return 'stock';
    }

    #[Override]
    protected static ?string $model = StockMovement::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    #[Override]
    protected static ?string $modelLabel = 'mouvement de stock';

    #[Override]
    protected static ?string $pluralModelLabel = 'mouvements de stock';

    #[Override]
    protected static ?string $navigationLabel = 'Mouvements de stock';

    #[Override]
    protected static ?int $navigationSort = 15;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['variant.product', 'order', 'user']))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('variant.product.name')
                    ->label('Produit'),
                TextColumn::make('variant.sku')
                    ->label('SKU'),
                TextColumn::make('reason')
                    ->label('Motif')
                    ->badge()
                    ->formatStateUsing(fn (StockMovementReason $state): string => $state->label()),
                TextColumn::make('quantity')
                    ->label('Quantité')
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? "+{$state}" : (string) $state)
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger'),
                TextColumn::make('stock_after')
                    ->label('Stock après'),
                TextColumn::make('order.number')
                    ->label('Commande')
                    ->placeholder('—'),
                TextColumn::make('user.name')
                    ->label('Par')
                    ->placeholder('—'),
                TextColumn::make('note')
                    ->label('Note')
                    ->placeholder('—')
                    ->limit(40),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('reason')
                    ->label('Motif')
                    ->options(collect(StockMovementReason::cases())->mapWithKeys(fn (StockMovementReason $reason): array => [$reason->value => $reason->label()])),
                Filter::make('sku')
                    ->label('SKU')
                    ->schema([TextInput::make('value')->label('SKU')])
                    ->query(function (Builder $query, array $data): Builder {
                        $sku = $data['value'] ?? null;

                        return is_string($sku) && $sku !== ''
                            ? $query->whereHas('variant', fn (Builder $variant) => $variant->where('sku', $sku))
                            : $query;
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockMovements::route('/'),
        ];
    }
}
