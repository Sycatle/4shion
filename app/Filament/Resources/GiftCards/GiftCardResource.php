<?php

declare(strict_types=1);

namespace App\Filament\Resources\GiftCards;

use App\Filament\Concerns\RestrictedByRole;
use App\Filament\Resources\GiftCards\Pages\ManageGiftCards;
use App\Models\GiftCard;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;

/**
 * Cards bought in the shop are issued on payment; this resource is for cards the shop offers.
 */
final class GiftCardResource extends Resource
{
    use RestrictedByRole;

    protected static function area(): string
    {
        return 'marketing';
    }

    #[Override]
    protected static ?string $model = GiftCard::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    #[Override]
    protected static ?string $modelLabel = 'carte cadeau';

    #[Override]
    protected static ?string $pluralModelLabel = 'cartes cadeaux';

    #[Override]
    protected static ?string $recordTitleAttribute = 'code';

    #[Override]
    protected static ?int $navigationSort = 32;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->default(fn (): string => GiftCard::generateCode())
                    ->dehydrateStateUsing(fn (string $state): string => GiftCard::normalizeCode($state)),
                TextInput::make('initial_value')
                    ->label('Montant')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->step(0.01)
                    ->suffix('€')
                    ->disabledOn('edit')
                    ->formatStateUsing(fn (?int $state): ?float => $state === null ? null : $state / 100)
                    ->dehydrateStateUsing(fn (float|int|string $state): int => (int) round(((float) $state) * 100))
                    ->live(onBlur: true),
                TextInput::make('balance')
                    ->label('Solde')
                    ->helperText('À la création, le solde est égal au montant.')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->suffix('€')
                    ->visibleOn('edit')
                    ->formatStateUsing(fn (?int $state): ?float => $state === null ? null : $state / 100)
                    ->dehydrateStateUsing(fn (float|int|string $state): int => (int) round(((float) $state) * 100)),
                DateTimePicker::make('expires_at')
                    ->label('Expiration')
                    ->default(now()->addYear()),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('initial_value')
                    ->label('Montant')
                    ->money('EUR', divideBy: 100),
                TextColumn::make('balance')
                    ->label('Solde')
                    ->money('EUR', divideBy: 100)
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Acheteur')
                    ->placeholder('Offerte par la boutique'),
                TextColumn::make('expires_at')
                    ->label('Expire le')
                    ->dateTime('d/m/Y')
                    ->placeholder('—')
                    ->color(fn (GiftCard $record): ?string => $record->expires_at?->isPast() ? 'danger' : null),
                IconColumn::make('is_active')
                    ->label('Active')
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
            'index' => ManageGiftCards::route('/'),
        ];
    }
}
