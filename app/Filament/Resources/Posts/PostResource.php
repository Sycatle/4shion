<?php

declare(strict_types=1);

namespace App\Filament\Resources\Posts;

use App\Filament\Concerns\RestrictedByRole;
use App\Filament\Resources\Posts\Pages\ManagePosts;
use App\Models\Post;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Override;

final class PostResource extends Resource
{
    use RestrictedByRole;

    #[Override]
    protected static ?string $model = Post::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    #[Override]
    protected static ?string $modelLabel = 'article';

    #[Override]
    protected static ?string $pluralModelLabel = 'journal';

    #[Override]
    protected static ?int $navigationSort = 60;

    protected static function area(): string
    {
        return 'marketing';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label('Titre')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (?string $state, Set $set): mixed => $set('slug', Str::slug((string) $state))),
            TextInput::make('slug')->label('Slug')->required()->maxLength(255)->unique(ignoreRecord: true),
            Textarea::make('excerpt')->label('Résumé (meta description)')->maxLength(320)->rows(2)->columnSpanFull(),
            Textarea::make('body')->label('Contenu (Markdown)')->required()->rows(14)->columnSpanFull(),
            DateTimePicker::make('published_at')->label('Publié le')->helperText('Vide : brouillon. Date future : programmé.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Titre')->searchable(),
                IconColumn::make('is_live')->label('En ligne')->boolean()->getStateUsing(fn (Post $record): bool => $record->isPublished()),
                TextColumn::make('published_at')->label('Publié le')->dateTime('d/m/Y H:i')->placeholder('Brouillon')->sortable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePosts::route('/')];
    }
}
