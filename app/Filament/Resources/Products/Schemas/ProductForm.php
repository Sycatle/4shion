<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

final class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations')
                    ->columns(2)
                    ->columnSpan(2)
                    ->components([
                        TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (?string $state, Set $set): mixed => $set('slug', Str::slug((string) $state))),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Select::make('category_id')
                            ->label('Catégorie')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('base_price')
                            ->label('Prix TTC')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix('€')
                            ->formatStateUsing(fn (?int $state): ?float => $state === null ? null : $state / 100)
                            ->dehydrateStateUsing(fn (float|int|string|null $state): int => (int) round(((float) $state) * 100)),
                        Textarea::make('description')
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),
                Section::make('Fiche technique')
                    ->columns(2)
                    ->columnSpan(2)
                    ->components([
                        TextInput::make('specs.composition')->label('Composition'),
                        TextInput::make('specs.weight')->label('Grammage'),
                        TextInput::make('specs.fit')->label('Coupe'),
                        TextInput::make('specs.care')->label('Entretien'),
                    ]),
                Section::make('Publication')
                    ->columnSpan(1)
                    ->components([
                        Toggle::make('is_active')
                            ->label('Visible en boutique')
                            ->default(true),
                        Toggle::make('is_featured')
                            ->label('Mis en avant sur l\'accueil'),
                        Toggle::make('is_gift_card')
                            ->label('Carte cadeau')
                            ->helperText('Sans stock ni livraison : un code est envoyé par e-mail après paiement. Le prix de chaque variante est le montant de la carte.'),
                    ]),
                Section::make('Soldes')
                    ->description('Prix barré automatique sur toutes les variantes, pendant la période choisie.')
                    ->columns(3)
                    ->columnSpan(2)
                    ->components([
                        TextInput::make('sale_percent')
                            ->label('Remise')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(90)
                            ->suffix('%'),
                        DateTimePicker::make('sale_starts_at')
                            ->label('Début'),
                        DateTimePicker::make('sale_ends_at')
                            ->label('Fin')
                            ->after('sale_starts_at'),
                    ]),
                Section::make('Visuels')
                    ->columnSpanFull()
                    ->components([
                        SpatieMediaLibraryFileUpload::make('images')
                            ->label('Images')
                            ->collection(Product::IMAGES)
                            ->multiple()
                            ->reorderable()
                            ->image()
                            ->imageEditor()
                            ->maxFiles(8),
                    ]),
            ])
            ->columns(3);
    }
}
