<?php

declare(strict_types=1);

namespace App\Filament\Resources\Categories;

use App\Filament\Concerns\ResolvesRecordsByIdOrRouteKey;
use App\Filament\Concerns\RestrictedByRole;
use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Override;

final class CategoryResource extends Resource
{
    use ResolvesRecordsByIdOrRouteKey, RestrictedByRole;

    protected static function area(): string
    {
        return 'products';
    }

    #[Override]
    protected static ?string $model = Category::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    #[Override]
    protected static ?string $modelLabel = 'catégorie';

    #[Override]
    protected static ?string $pluralModelLabel = 'catégories';

    #[Override]
    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    protected static ?string $recordRouteKeyName = 'id';

    #[Override]
    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (?string $state, Set $set): mixed => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->label('Description')
                    ->columnSpanFull(),
                Textarea::make('content')
                    ->label('Contenu éditorial (Markdown)')
                    ->helperText('Affiché sous la liste des produits : utile pour le référencement.')
                    ->rows(6)
                    ->columnSpanFull(),
                TextInput::make('meta_title')
                    ->label('Titre SEO')
                    ->maxLength(255),
                Textarea::make('meta_description')
                    ->label('Description SEO')
                    ->maxLength(320)
                    ->rows(2),
                TextInput::make('position')
                    ->label('Position')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('products_count')
                    ->label('Produits')
                    ->counts('products')
                    ->sortable(),
                TextColumn::make('position')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('position')
            ->reorderable('position')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (Category $record): bool => $record->products()->exists()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategories::route('/'),
        ];
    }
}
