<?php

declare(strict_types=1);

namespace App\Filament\Resources\ShippingMethods;

use App\Enums\Carrier;
use App\Filament\Concerns\RestrictedByRole;
use App\Filament\Resources\ShippingMethods\Pages\ManageShippingMethods;
use App\Models\ShippingMethod;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;

final class ShippingMethodResource extends Resource
{
    use RestrictedByRole;

    #[Override]
    protected static ?string $model = ShippingMethod::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    #[Override]
    protected static ?string $modelLabel = 'mode de livraison';

    #[Override]
    protected static ?string $pluralModelLabel = 'modes de livraison';

    #[Override]
    protected static ?int $navigationSort = 55;

    protected static function area(): string
    {
        return 'marketing';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nom')->required()->maxLength(120),
            Select::make('carrier')
                ->label('Transporteur')
                ->options(collect(Carrier::cases())->mapWithKeys(fn (Carrier $carrier): array => [$carrier->value => $carrier->label()])),
            TextInput::make('delay')->label('Délai affiché')->placeholder('2 à 4 jours ouvrés')->maxLength(120),
            TextInput::make('price')
                ->label('Tarif TTC (€)')
                ->numeric()
                ->minValue(0)
                ->required()
                ->afterStateHydrated(fn (TextInput $component, mixed $state): TextInput => $component->state(is_numeric($state) ? $state / 100 : $state))
                ->dehydrateStateUsing(fn (mixed $state): int => is_numeric($state) ? (int) round((float) $state * 100) : 0),
            TextInput::make('free_threshold')
                ->label('Gratuit dès (€)')
                ->helperText('Vide : seuil général de la boutique.')
                ->numeric()
                ->minValue(0)
                ->afterStateHydrated(fn (TextInput $component, mixed $state): TextInput => $component->state(is_numeric($state) ? $state / 100 : $state))
                ->dehydrateStateUsing(fn (mixed $state): ?int => is_numeric($state) ? (int) round((float) $state * 100) : null),
            TextInput::make('position')->label('Ordre')->numeric()->integer()->default(0),
            Toggle::make('is_active')->label('Proposé au paiement')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nom'),
                TextColumn::make('carrier')->label('Transporteur')->formatStateUsing(fn (mixed $state): string => $state instanceof Carrier ? $state->label() : '—'),
                TextColumn::make('price')->label('Tarif')->money('EUR', divideBy: 100),
                TextColumn::make('free_threshold')->label('Gratuit dès')->money('EUR', divideBy: 100)->placeholder('Seuil général'),
                IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->defaultSort('position')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageShippingMethods::route('/')];
    }
}
