<?php

declare(strict_types=1);

namespace App\Filament\Resources\NewsletterSubscribers;

use App\Filament\Concerns\RestrictedByRole;
use App\Filament\Resources\NewsletterSubscribers\Pages\ListNewsletterSubscribers;
use App\Models\NewsletterSubscriber;
use App\Support\CsvExport;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Override;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class NewsletterSubscriberResource extends Resource
{
    use RestrictedByRole;

    #[Override]
    protected static ?string $model = NewsletterSubscriber::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    #[Override]
    protected static ?string $modelLabel = 'abonné';

    #[Override]
    protected static ?string $pluralModelLabel = 'newsletter';

    #[Override]
    protected static ?int $navigationSort = 65;

    protected static function area(): string
    {
        return 'marketing';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')->label('E-mail')->searchable(),
                IconColumn::make('active')->label('Actif')->boolean()->getStateUsing(fn (NewsletterSubscriber $record): bool => $record->isActive()),
                TextColumn::make('confirmed_at')->label('Confirmé le')->dateTime('d/m/Y')->placeholder('Non confirmé')->sortable(),
                TextColumn::make('unsubscribed_at')->label('Désinscrit le')->dateTime('d/m/Y')->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('confirmed')->label('Confirmé')->nullable()->attribute('confirmed_at'),
            ])
            ->headerActions([
                Action::make('export')
                    ->label('Exporter les abonnés actifs (CSV)')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->action(fn (): StreamedResponse => CsvExport::download(
                        'newsletter-'.now()->format('Y-m-d').'.csv',
                        ['E-mail', 'Confirmé le'],
                        NewsletterSubscriber::query()->active()->orderBy('id')->get()->map(fn (NewsletterSubscriber $subscriber): array => [$subscriber->email, $subscriber->confirmed_at?->format('Y-m-d')]),
                    )),
            ])
            ->recordActions([DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListNewsletterSubscribers::route('/')];
    }
}
