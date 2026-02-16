<?php

namespace App\Filament\Resources\Coupons;

use App\Enums\DiscountType;
use App\Filament\Resources\Coupons\Pages\ManageCoupons;
use App\Models\Coupon;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static ?string $modelLabel = 'code promo';

    protected static ?string $pluralModelLabel = 'codes promo';

    protected static ?string $recordTitleAttribute = 'code';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn (string $state): string => strtoupper(trim($state))),
                Select::make('type')
                    ->label('Type de remise')
                    ->options(collect(DiscountType::cases())->mapWithKeys(fn (DiscountType $type) => [$type->value => $type->label()]))
                    ->required()
                    ->live()
                    ->default(DiscountType::Percent->value),
                TextInput::make('value')
                    ->label('Valeur')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(fn (Get $get): ?int => $get('type') === DiscountType::Percent->value ? 100 : null)
                    ->suffix(fn (Get $get): string => $get('type') === DiscountType::Percent->value ? '%' : '€')
                    ->formatStateUsing(fn (?int $state, Get $get): int|float|null => $state !== null && $get('type') === DiscountType::Fixed->value ? $state / 100 : $state)
                    ->dehydrateStateUsing(fn (float|int|string $state, Get $get): int => $get('type') === DiscountType::Fixed->value ? (int) round(((float) $state) * 100) : (int) $state),
                TextInput::make('min_subtotal')
                    ->label('Panier minimum')
                    ->numeric()
                    ->minValue(0)
                    ->suffix('€')
                    ->formatStateUsing(fn (?int $state): ?float => $state === null ? null : $state / 100)
                    ->dehydrateStateUsing(fn (float|int|string|null $state): ?int => $state === null || $state === '' ? null : (int) round(((float) $state) * 100)),
                TextInput::make('max_uses')
                    ->label('Utilisations maximum')
                    ->numeric()
                    ->integer()
                    ->minValue(1),
                DateTimePicker::make('starts_at')
                    ->label('Début'),
                DateTimePicker::make('expires_at')
                    ->label('Expiration')
                    ->after('starts_at'),
                Toggle::make('is_active')
                    ->label('Actif')
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
                TextColumn::make('type')
                    ->label('Remise')
                    ->formatStateUsing(fn (Coupon $record): string => $record->type === DiscountType::Percent
                        ? "{$record->value} %"
                        : number_format($record->value / 100, 2, ',', ' ').' €'),
                TextColumn::make('used_count')
                    ->label('Utilisations')
                    ->formatStateUsing(fn (Coupon $record): string => $record->max_uses ? "{$record->used_count} / {$record->max_uses}" : (string) $record->used_count),
                TextColumn::make('expires_at')
                    ->label('Expire le')
                    ->dateTime('d/m/Y')
                    ->placeholder('—')
                    ->color(fn (Coupon $record): ?string => $record->expires_at?->isPast() ? 'danger' : null),
                IconColumn::make('is_active')
                    ->label('Actif')
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
            'index' => ManageCoupons::route('/'),
        ];
    }
}
