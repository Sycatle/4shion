<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Concerns\RestrictedByRole;
use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Models\ActivityLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Override;

/** Read-only trail of back-office changes. */
final class ActivityLogResource extends Resource
{
    use RestrictedByRole;

    #[Override]
    protected static ?string $model = ActivityLog::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    #[Override]
    protected static ?string $modelLabel = 'activité';

    #[Override]
    protected static ?string $pluralModelLabel = 'journal d’activité';

    #[Override]
    protected static ?string $navigationLabel = 'Journal d’activité';

    #[Override]
    protected static ?int $navigationSort = 90;

    protected static function area(): string
    {
        return 'activity';
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

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i:s')->sortable(),
                TextColumn::make('user.name')->label('Par')->placeholder('—')->searchable(),
                TextColumn::make('event')
                    ->label('Action')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'created' => 'Création',
                        'deleted' => 'Suppression',
                        default => 'Modification',
                    }),
                TextColumn::make('subject_type')->label('Objet')->formatStateUsing(fn (string $state): string => class_basename($state)),
                TextColumn::make('subject_label')->label('Libellé')->placeholder('—')->searchable()->limit(40),
                TextColumn::make('changes')
                    ->label('Détail')
                    ->formatStateUsing(fn (mixed $state): string => self::summary($state))
                    ->limit(80)
                    ->tooltip(fn (ActivityLog $record): string => self::summary($record->changes, 2000)),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('event')->label('Action')->options(['created' => 'Création', 'updated' => 'Modification', 'deleted' => 'Suppression']),
                SelectFilter::make('user_id')->label('Par')->relationship('user', 'name'),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListActivityLogs::route('/')];
    }

    private static function summary(mixed $changes, int $limit = 400): string
    {
        if (! is_array($changes)) {
            return '';
        }

        $parts = [];

        foreach ($changes as $field => $change) {
            $parts[] = is_array($change) && array_key_exists('new', $change)
                ? sprintf('%s : %s → %s', $field, self::scalar($change['old'] ?? null), self::scalar($change['new']))
                : sprintf('%s : %s', $field, self::scalar($change));
        }

        return mb_strimwidth(implode(' ; ', $parts), 0, $limit, '…');
    }

    private static function scalar(mixed $value): string
    {
        return match (true) {
            $value === null => '∅',
            is_scalar($value) => (string) $value,
            default => (string) json_encode($value),
        };
    }
}
