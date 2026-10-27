<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\Schemas\OrderInfolist;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;

final class OrderResource extends Resource
{
    #[Override]
    protected static ?string $model = Order::class;

    #[Override]
    protected static ?string $recordRouteKeyName = 'id';

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    #[Override]
    protected static ?string $modelLabel = 'commande';

    #[Override]
    protected static ?string $recordTitleAttribute = 'number';

    #[Override]
    protected static ?int $navigationSort = 10;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $toShip = Order::query()->where('status', OrderStatus::Paid)->count();

        return $toShip > 0 ? (string) $toShip : null;
    }

    public static function getNavigationBadgeTooltip(): string
    {
        return 'Commandes à expédier';
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
