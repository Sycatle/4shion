<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Enums\StockMovementReason;
use App\Inventory\StockLedger;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Imports a catalog CSV, one row per variant, semicolon- or comma-separated:
 * product_slug, product_name, category, base_price (euros), sku, size, color, stock.
 * Products are matched on slug, variants on SKU. Stock differences go through the ledger.
 * All or nothing: any invalid row aborts the whole import.
 */
final readonly class ImportCatalog
{
    private const array COLUMNS = ['product_slug', 'product_name', 'category', 'base_price', 'sku', 'size', 'color', 'stock'];

    public function __construct(private StockLedger $ledger) {}

    /**
     * @return array{products: int, variants: int}
     *
     * @throws InvalidArgumentException with a message naming the offending line
     */
    public function __invoke(string $path, User $user): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new InvalidArgumentException('Fichier illisible.');
        }

        try {
            $header = fgetcsv($handle, escape: '');
            $delimiter = is_array($header) && count($header) === 1 && str_contains((string) $header[0], ';') ? ';' : ',';

            if ($delimiter === ';') {
                rewind($handle);
                $header = fgetcsv($handle, separator: ';', escape: '');
            }

            $header = is_array($header) ? array_map(fn (?string $name): string => trim(ltrim((string) $name, "\xEF\xBB\xBF")), $header) : [];

            if (array_diff(self::COLUMNS, $header) !== []) {
                throw new InvalidArgumentException('Colonnes attendues : '.implode(', ', self::COLUMNS).'.');
            }

            $rows = [];

            for ($line = 2; ($cells = fgetcsv($handle, separator: $delimiter, escape: '')) !== false; $line++) {
                if ($cells === [null]) {
                    continue;
                }

                $rows[$line] = array_combine($header, array_pad(array_map(fn (?string $cell): string => trim((string) $cell), $cells), count($header), ''));
            }
        } finally {
            fclose($handle);
        }

        return DB::transaction(fn (): array => $this->import($rows, $user));
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array{products: int, variants: int}
     */
    private function import(array $rows, User $user): array
    {
        $products = [];
        $variants = 0;

        foreach ($rows as $line => $row) {
            $this->validate($row, $line);

            $category = Category::query()->firstOrCreate(['slug' => Str::slug($row['category'])], ['name' => $row['category']]);
            // New products stay hidden until someone has reviewed them in the back-office.
            $product = Product::query()->firstOrNew(['slug' => $row['product_slug']], ['is_active' => false]);
            $product->fill([
                'name' => $row['product_name'],
                'category_id' => $category->id,
                'base_price' => (int) round((float) str_replace(',', '.', $row['base_price']) * 100),
            ])->save();
            $products[$product->id] = true;

            $variant = ProductVariant::query()->firstOrNew(['sku' => $row['sku']]);

            if ($variant->exists && $variant->product_id !== $product->id) {
                throw new InvalidArgumentException("Ligne {$line} : le SKU {$row['sku']} appartient à un autre produit.");
            }

            $stock = (int) $row['stock'];
            $isNew = ! $variant->exists;
            $variant->fill(['product_id' => $product->id, 'size' => $row['size'], 'color' => $row['color'], 'stock' => $isNew ? 0 : $variant->stock])->save();

            $delta = $stock - $variant->stock;

            if ($delta > 0) {
                $this->ledger->add($variant, $delta, StockMovementReason::Restock, user: $user, note: 'Import CSV');
            } elseif ($delta < 0) {
                $this->ledger->adjust($variant, $delta, StockMovementReason::Adjustment, $user, 'Import CSV');
            }

            $variants++;
        }

        return ['products' => count($products), 'variants' => $variants];
    }

    /** @param  array<string, string>  $row */
    private function validate(array $row, int $line): void
    {
        foreach (['product_slug', 'product_name', 'category', 'sku', 'size', 'color'] as $required) {
            if ($row[$required] === '') {
                throw new InvalidArgumentException("Ligne {$line} : « {$required} » est obligatoire.");
            }
        }

        if (! is_numeric(str_replace(',', '.', $row['base_price'])) || (float) str_replace(',', '.', $row['base_price']) < 0) {
            throw new InvalidArgumentException("Ligne {$line} : prix invalide.");
        }

        if (preg_match('/^\d+$/', $row['stock']) !== 1) {
            throw new InvalidArgumentException("Ligne {$line} : le stock doit être un entier positif.");
        }
    }
}
