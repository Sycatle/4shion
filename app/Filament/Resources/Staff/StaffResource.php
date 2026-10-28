<?php

declare(strict_types=1);

namespace App\Filament\Resources\Staff;

use App\Enums\Role;
use App\Filament\Concerns\RestrictedByRole;
use App\Filament\Resources\Staff\Pages\ManageStaff;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Override;

/** Back-office accounts and their role. The legacy `is_admin` flag is shown as Administrateur. */
final class StaffResource extends Resource
{
    use RestrictedByRole;

    #[Override]
    protected static ?string $model = User::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    #[Override]
    protected static ?string $modelLabel = 'membre de l’équipe';

    #[Override]
    protected static ?string $pluralModelLabel = 'équipe';

    #[Override]
    protected static ?string $slug = 'staff';

    #[Override]
    protected static ?int $navigationSort = 95;

    protected static function area(): string
    {
        return 'staff';
    }

    /** @return Builder<User> */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<User> $query */
        $query = parent::getEloquentQuery();

        return $query->where(fn (Builder $query) => $query->whereNotNull('role')->orWhere('is_admin', true));
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof User && $record->id !== auth()->id();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nom')->required()->maxLength(255),
            TextInput::make('email')->label('E-mail')->email()->required()->unique(ignoreRecord: true)->maxLength(255),
            Select::make('role')
                ->label('Rôle')
                ->options(collect(Role::cases())->mapWithKeys(fn (Role $role): array => [$role->value => $role->label()]))
                ->required(),
            TextInput::make('password')
                ->label('Mot de passe')
                ->password()
                ->revealable()
                ->minLength(12)
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state)),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable(),
                TextColumn::make('email')->label('E-mail')->searchable(),
                TextColumn::make('role')
                    ->label('Rôle')
                    ->badge()
                    ->getStateUsing(fn (User $record): string => $record->staffRole()?->label() ?? '—'),
            ])
            ->recordActions([
                EditAction::make()->using(function (User $record, array $data): User {
                    /** @var array<string, mixed> $data */
                    User::unguarded(fn (): bool => $record->update($data));

                    return $record;
                }),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageStaff::route('/')];
    }
}
