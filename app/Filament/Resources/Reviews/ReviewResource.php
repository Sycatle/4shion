<?php

namespace App\Filament\Resources\Reviews;

use App\Filament\Resources\Reviews\Pages\ManageReviews;
use App\Models\Review;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static ?string $modelLabel = 'avis';

    protected static ?string $pluralModelLabel = 'avis';

    protected static ?int $navigationSort = 40;

    public static function getNavigationBadge(): ?string
    {
        $pending = Review::query()->whereNull('approved_at')->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextEntry::make('product.name')->label('Produit'),
                TextEntry::make('user.name')->label('Client'),
                TextEntry::make('rating')->label('Note')->formatStateUsing(fn (int $state): string => str_repeat('★', $state).str_repeat('☆', 5 - $state)),
                TextEntry::make('created_at')->label('Posté le')->dateTime('d/m/Y H:i'),
                TextEntry::make('title')->label('Titre')->placeholder('—')->columnSpanFull(),
                TextEntry::make('body')->label('Avis')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['product', 'user']))
            ->columns([
                TextColumn::make('product.name')
                    ->label('Produit')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Client')
                    ->searchable(),
                TextColumn::make('rating')
                    ->label('Note')
                    ->formatStateUsing(fn (int $state): string => str_repeat('★', $state).str_repeat('☆', 5 - $state))
                    ->sortable(),
                TextColumn::make('body')
                    ->label('Avis')
                    ->limit(60)
                    ->tooltip(fn (Review $record): string => $record->body),
                IconColumn::make('approved_at')
                    ->label('Publié')
                    ->boolean()
                    ->getStateUsing(fn (Review $record): bool => $record->approved_at !== null),
                TextColumn::make('created_at')
                    ->label('Posté le')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('approved')
                    ->label('Publié')
                    ->nullable()
                    ->attribute('approved_at')
                    ->default(false),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('approve')
                    ->label('Publier')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->visible(fn (Review $record): bool => $record->approved_at === null)
                    ->action(fn (Review $record) => $record->update(['approved_at' => now()])),
                Action::make('unpublish')
                    ->label('Dépublier')
                    ->icon(Heroicon::OutlinedEyeSlash)
                    ->visible(fn (Review $record): bool => $record->approved_at !== null)
                    ->requiresConfirmation()
                    ->action(fn (Review $record) => $record->update(['approved_at' => null])),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')
                        ->label('Publier la sélection')
                        ->icon(Heroicon::OutlinedCheck)
                        ->action(fn (Collection $records) => $records->each->update(['approved_at' => now()]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageReviews::route('/'),
        ];
    }
}
