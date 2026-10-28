<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers;

use App\Enums\OrderStatus;
use App\Filament\Concerns\RestrictedByRole;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Resources\Customers\RelationManagers\OrdersRelationManager;
use App\Models\User;
use App\Support\CsvExport;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Override;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Customer file: orders count and lifetime value over paid orders. Read-only. */
final class CustomerResource extends Resource
{
    use RestrictedByRole;

    #[Override]
    protected static ?string $model = User::class;

    #[Override]
    protected static ?string $recordRouteKeyName = 'id';

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    #[Override]
    protected static ?string $modelLabel = 'client';

    #[Override]
    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    protected static ?int $navigationSort = 12;

    protected static function area(): string
    {
        return 'customers';
    }

    /** @return Builder<User> */
    public static function getEloquentQuery(): Builder
    {
        $paid = fn (Builder $orders): Builder => $orders->whereIn('status', [OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Delivered]);

        /** @var Builder<User> $query */
        $query = parent::getEloquentQuery();

        return $query
            ->withCount(['orders' => $paid])
            ->withSum(['orders as lifetime_value' => $paid], 'total')
            ->withMax('orders as last_order_at', 'created_at');
    }

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

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextEntry::make('name')->label('Nom'),
            TextEntry::make('email')->label('E-mail')->copyable(),
            TextEntry::make('orders_count')->label('Commandes payées'),
            TextEntry::make('lifetime_value')->label('Valeur client (TTC)')->money('EUR', divideBy: 100)->placeholder('—'),
            TextEntry::make('last_order_at')->label('Dernière commande')->dateTime('d/m/Y')->placeholder('—'),
            TextEntry::make('created_at')->label('Inscrit le')->dateTime('d/m/Y'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable(),
                TextColumn::make('email')->label('E-mail')->searchable(),
                TextColumn::make('orders_count')->label('Commandes')->sortable(),
                TextColumn::make('lifetime_value')->label('Valeur client')->money('EUR', divideBy: 100)->sortable()->placeholder('—'),
                TextColumn::make('last_order_at')->label('Dernière commande')->dateTime('d/m/Y')->sortable()->placeholder('—'),
            ])
            ->defaultSort('lifetime_value', 'desc')
            ->recordActions([ViewAction::make()])
            ->headerActions([
                Action::make('export')
                    ->label('Exporter en CSV')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->action(fn (): StreamedResponse => self::export()),
            ]);
    }

    public static function getRelations(): array
    {
        return [OrdersRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'view' => ViewCustomer::route('/{record}'),
        ];
    }

    private static function cents(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private static function export(): StreamedResponse
    {
        $rows = (function (): iterable {
            foreach (self::getEloquentQuery()->orderBy('id')->lazyById() as $customer) {
                yield [$customer->name, $customer->email, $customer->getAttribute('orders_count'), number_format(self::cents($customer->getAttribute('lifetime_value')) / 100, 2, '.', ''), $customer->created_at?->format('Y-m-d')];
            }
        })();

        return CsvExport::download('clients-'.now()->format('Y-m-d').'.csv', ['Nom', 'E-mail', 'Commandes payées', 'Valeur client TTC', 'Inscrit le'], $rows);
    }
}
