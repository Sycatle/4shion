<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;

/** Internal notes: never shown to the customer. */
final class NotesRelationManager extends RelationManager
{
    #[Override]
    protected static string $relationship = 'notes';

    #[Override]
    protected static ?string $title = 'Notes internes';

    /** Relation managers are read-only on view pages by default; notes are meant to be written there. */
    #[Override]
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('body')->label('Note')->required()->rows(3)->maxLength(2000)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('body')->label('Note')->wrap(),
                TextColumn::make('author.name')->label('Par')->placeholder('—'),
                TextColumn::make('created_at')->label('Le')->dateTime('d/m/Y H:i'),
            ])
            ->headerActions([
                CreateAction::make()->mutateDataUsing(fn (array $data): array => [...$data, 'user_id' => auth()->id()]),
            ])
            ->recordActions([DeleteAction::make()]);
    }
}
