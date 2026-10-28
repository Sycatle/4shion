<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products;

use App\Filament\Concerns\ResolvesRecordsByIdOrRouteKey;
use App\Filament\Concerns\RestrictedByRole;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\RelationManagers\VariantsRelationManager;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;

final class ProductResource extends Resource
{
    use ResolvesRecordsByIdOrRouteKey, RestrictedByRole;

    protected static function area(): string
    {
        return 'products';
    }

    #[Override]
    protected static ?string $model = Product::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    #[Override]
    protected static ?string $modelLabel = 'produit';

    #[Override]
    protected static ?string $pluralModelLabel = 'produits';

    #[Override]
    protected static ?string $recordTitleAttribute = 'name';

    // Slugs are editable, so records are addressed by id in admin URLs.
    #[Override]
    protected static ?string $recordRouteKeyName = 'id';

    #[Override]
    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            VariantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
