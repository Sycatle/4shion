<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\RelationManagers;

use App\Enums\StockMovementReason;
use App\Filament\Resources\StockMovements\StockMovementResource;
use App\Inventory\StockLedger;
use App\Models\ProductVariant;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Override;

final class VariantsRelationManager extends RelationManager
{
    #[Override]
    protected static string $relationship = 'variants';

    #[Override]
    protected static ?string $title = 'Variantes';

    #[Override]
    protected static ?string $modelLabel = 'variante';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('sku')
                    ->label('SKU')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                // Stock only moves through the ledger: initial stock on creation, then "Ajuster le stock".
                TextInput::make('stock')
                    ->label(fn (?Model $record): string => $record instanceof Model ? 'Stock (via « Ajuster le stock »)' : 'Stock initial')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(0)
                    ->disabled(fn (?Model $record): bool => $record instanceof Model)
                    ->dehydrated(fn (?Model $record): bool => ! $record instanceof Model),
                TextInput::make('size')
                    ->label('Taille')
                    ->required()
                    ->maxLength(255),
                TextInput::make('color')
                    ->label('Couleur')
                    ->required()
                    ->maxLength(255),
                ColorPicker::make('color_hex')
                    ->label('Pastille'),
                TextInput::make('price_override')
                    ->label('Prix spécifique TTC')
                    ->helperText('Laisser vide pour utiliser le prix du produit.')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->suffix('€')
                    ->formatStateUsing(fn (?int $state): ?float => $state === null ? null : $state / 100)
                    ->dehydrateStateUsing(fn (float|int|string|null $state): ?int => $state === null || $state === '' ? null : (int) round(((float) $state) * 100)),
                TextInput::make('low_stock_threshold')
                    ->label('Seuil de stock bas')
                    ->helperText('Laisser vide pour utiliser le seuil par défaut de la boutique.')
                    ->numeric()
                    ->integer()
                    ->minValue(0),
                Section::make('Précommande')
                    ->description('Une variante épuisée reste commandable ; les commandes sont servies dans l\'ordre à chaque réassort.')
                    ->columns(3)
                    ->columnSpanFull()
                    ->components([
                        Toggle::make('preorder_enabled')
                            ->label('Autoriser la précommande')
                            ->live(),
                        DatePicker::make('preorder_available_on')
                            ->label('Disponible le')
                            ->visible(fn (Get $get): bool => (bool) $get('preorder_enabled')),
                        TextInput::make('preorder_limit')
                            ->label('Limite de précommandes')
                            ->helperText('Laisser vide pour ne pas limiter.')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->visible(fn (Get $get): bool => (bool) $get('preorder_enabled')),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                ColorColumn::make('color_hex')
                    ->label(''),
                TextColumn::make('color')
                    ->label('Couleur')
                    ->searchable(),
                TextColumn::make('size')
                    ->label('Taille'),
                TextColumn::make('price_override')
                    ->label('Prix spécifique')
                    ->money('EUR', divideBy: 100)
                    ->placeholder('—'),
                TextColumn::make('stock')
                    ->label('Stock')
                    ->badge()
                    ->color(fn (ProductVariant $record): string => match (true) {
                        $record->stock === 0 => 'danger',
                        $record->isLowStock() => 'warning',
                        default => 'success',
                    })
                    ->sortable(),
                IconColumn::make('preorder_enabled')
                    ->label('Précommande')
                    ->boolean(),
            ])
            ->defaultSort('id')
            ->headerActions([
                CreateAction::make()
                    ->using(fn (array $data): Model => $this->createVariant($data)),
            ])
            ->recordActions([
                Action::make('adjustStock')
                    ->label('Ajuster le stock')
                    ->icon(Heroicon::OutlinedArrowsUpDown)
                    ->schema([
                        TextInput::make('delta')
                            ->label('Quantité')
                            ->helperText('Positive pour une entrée, négative pour une sortie.')
                            ->required()
                            ->numeric()
                            ->integer()
                            ->rule('not_in:0'),
                        Select::make('reason')
                            ->label('Motif')
                            ->options([
                                StockMovementReason::Restock->value => StockMovementReason::Restock->label(),
                                StockMovementReason::Adjustment->value => StockMovementReason::Adjustment->label(),
                            ])
                            ->default(StockMovementReason::Restock->value)
                            ->required(),
                        TextInput::make('note')
                            ->label('Note')
                            ->maxLength(255),
                    ])
                    /** @param array<string, mixed> $data */
                    ->action(function (Action $action, ProductVariant $record, array $data): void {
                        $note = $data['note'] ?? null;

                        try {
                            app(StockLedger::class)->adjust(
                                $record,
                                Arr::integer($data, 'delta'),
                                StockMovementReason::from(Arr::string($data, 'reason')),
                                $this->currentUser(),
                                is_string($note) && $note !== '' ? $note : null,
                            );
                        } catch (InvalidArgumentException) {
                            $action->failure();

                            return;
                        }

                        $action->success();
                    })
                    ->successNotificationTitle('Stock mis à jour')
                    ->failureNotificationTitle('Stock insuffisant pour cette sortie'),
                Action::make('movements')
                    ->label('Mouvements')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->url(fn (ProductVariant $record): string => StockMovementResource::getUrl('index', ['tableFilters' => ['sku' => ['value' => $record->sku]]])),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** @param array<mixed> $form Filament leaves the form state untyped */
    private function createVariant(array $form): ProductVariant
    {
        /** @var array<string, mixed> $data */
        $data = $form;
        $initialStock = Arr::integer($data, 'stock', 0);
        $variant = ProductVariant::query()->create([...$data, 'product_id' => $this->getOwnerRecord()->getKey(), 'stock' => 0]);

        if ($initialStock > 0) {
            app(StockLedger::class)->add($variant, $initialStock, StockMovementReason::Restock, user: $this->currentUser(), note: 'Stock initial');
        }

        return $variant;
    }

    private function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
