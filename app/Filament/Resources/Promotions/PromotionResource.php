<?php

declare(strict_types=1);

namespace App\Filament\Resources\Promotions;

use App\Enums\PromotionType;
use App\Filament\Concerns\RestrictedByRole;
use App\Filament\Resources\Promotions\Pages\ManagePromotions;
use App\Models\Promotion;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;

final class PromotionResource extends Resource
{
    use RestrictedByRole;

    protected static function area(): string
    {
        return 'marketing';
    }

    #[Override]
    protected static ?string $model = Promotion::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    #[Override]
    protected static ?string $modelLabel = 'promotion';

    #[Override]
    protected static ?string $pluralModelLabel = 'promotions';

    #[Override]
    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    protected static ?int $navigationSort = 31;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Nom affiché au client')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->label('Type')
                    ->options(collect(PromotionType::cases())->mapWithKeys(fn (PromotionType $type): array => [$type->value => $type->label()]))
                    ->required()
                    ->live()
                    ->default(PromotionType::SpendThreshold->value),
                TextInput::make('min_subtotal')
                    ->label('Panier minimum')
                    ->visible(fn (Get $get): bool => $get('type') === PromotionType::SpendThreshold->value)
                    ->required(fn (Get $get): bool => $get('type') === PromotionType::SpendThreshold->value)
                    ->numeric()
                    ->minValue(0)
                    ->suffix('€')
                    ->formatStateUsing(fn (?int $state): ?float => $state === null ? null : $state / 100)
                    ->dehydrateStateUsing(fn (float|int|string|null $state): ?int => $state === null || $state === '' ? null : (int) round(((float) $state) * 100)),
                TextInput::make('percent')
                    ->label('Remise')
                    ->visible(fn (Get $get): bool => $get('type') === PromotionType::SpendThreshold->value)
                    ->required(fn (Get $get): bool => $get('type') === PromotionType::SpendThreshold->value)
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(100)
                    ->suffix('%'),
                TextInput::make('buy_quantity')
                    ->label('Articles pour en avoir un offert')
                    ->helperText('Avec 3 : pour trois articles dans le panier, le moins cher est offert.')
                    ->visible(fn (Get $get): bool => $get('type') === PromotionType::BuyXGetCheapestFree->value)
                    ->required(fn (Get $get): bool => $get('type') === PromotionType::BuyXGetCheapestFree->value)
                    ->numeric()
                    ->integer()
                    ->minValue(2),
                Select::make('category_id')
                    ->label('Catégorie')
                    ->helperText('Laisser vide pour toute la boutique.')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                DateTimePicker::make('starts_at')
                    ->label('Début'),
                DateTimePicker::make('ends_at')
                    ->label('Fin')
                    ->after('starts_at'),
                Toggle::make('is_active')
                    ->label('Actif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (Promotion $record): string => $record->type->label().' · '.$record->summary()),
                TextColumn::make('category.name')
                    ->label('Catégorie')
                    ->placeholder('Toute la boutique'),
                TextColumn::make('ends_at')
                    ->label('Fin')
                    ->dateTime('d/m/Y')
                    ->placeholder('—')
                    ->color(fn (Promotion $record): ?string => $record->ends_at?->isPast() ? 'danger' : null),
                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePromotions::route('/'),
        ];
    }
}
