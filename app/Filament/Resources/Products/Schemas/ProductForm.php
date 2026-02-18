<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
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
                            ->afterStateUpdated(fn (?string $state, Set $set) => $set('slug', Str::slug((string) $state))),
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
