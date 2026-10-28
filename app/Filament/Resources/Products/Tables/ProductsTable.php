<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Tables;

use App\Actions\Catalog\ImportCatalog;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\CsvExport;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')
                    ->label('')
                    ->collection('images')
                    ->limit(1),
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Catégorie')
                    ->sortable(),
                TextColumn::make('base_price')
                    ->label('Prix')
                    ->money('EUR', divideBy: 100)
                    ->sortable(),
                TextColumn::make('variants_sum_stock')
                    ->label('Stock')
                    ->sum('variants', 'stock')
                    ->badge()
                    ->color(fn (int|string|null $state): string => (int) $state === 0 ? 'danger' : 'gray'),
                IconColumn::make('is_active')
                    ->label('Visible')
                    ->boolean(),
                IconColumn::make('is_featured')
                    ->label('Accueil')
                    ->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('category')
                    ->label('Catégorie')
                    ->relationship('category', 'name'),
                TernaryFilter::make('is_active')
                    ->label('Visible'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->headerActions([
                Action::make('export')
                    ->label('Exporter en CSV')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->action(fn (): StreamedResponse => self::export()),
                Action::make('import')
                    ->label('Importer un CSV')
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->modalDescription('Une ligne par variante : product_slug, product_name, category, base_price (en euros), sku, size, color, stock. Les produits sont repérés par leur slug, les variantes par leur SKU. Les nouveaux produits sont créés masqués, à publier après relecture.')
                    ->schema([
                        FileUpload::make('file')->label('Fichier CSV')->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])->disk('local')->directory('imports')->required(),
                    ])
                    ->action(function (array $data): void {
                        $path = Storage::disk('local')->path(is_string($data['file'] ?? null) ? $data['file'] : '');

                        try {
                            $user = auth()->user();
                            $result = app(ImportCatalog::class)($path, $user instanceof User ? $user : throw new InvalidArgumentException('Utilisateur requis.'));
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->title('Import refusé')->body($e->getMessage())->danger()->send();

                            return;
                        } finally {
                            File::delete($path);
                        }

                        Notification::make()->title('Import terminé')->body("{$result['products']} produit(s), {$result['variants']} variante(s).")->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** One line per variant, in the same format as the import. */
    private static function export(): StreamedResponse
    {
        $rows = (function (): iterable {
            foreach (ProductVariant::query()->with('product.category')->orderBy('id')->lazyById() as $variant) {
                yield [
                    $variant->product->slug,
                    $variant->product->name,
                    $variant->product->category->name,
                    number_format($variant->product->base_price / 100, 2, '.', ''),
                    $variant->sku,
                    $variant->size,
                    $variant->color,
                    $variant->stock,
                ];
            }
        })();

        return CsvExport::download('catalogue-'.now()->format('Y-m-d').'.csv', ['product_slug', 'product_name', 'category', 'base_price', 'sku', 'size', 'color', 'stock'], $rows);
    }
}
