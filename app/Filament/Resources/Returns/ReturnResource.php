<?php

declare(strict_types=1);

namespace App\Filament\Resources\Returns;

use App\Actions\Returns\ApproveReturn;
use App\Actions\Returns\GenerateCreditNote;
use App\Actions\Returns\ReceiveReturn;
use App\Actions\Returns\RefundReturn;
use App\Actions\Returns\RejectReturn;
use App\Enums\ReturnReason;
use App\Enums\ReturnStatus;
use App\Filament\Concerns\RestrictedByRole;
use App\Filament\Resources\Returns\Pages\ManageReturns;
use App\Models\OrderReturn;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use LogicException;
use Override;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class ReturnResource extends Resource
{
    use RestrictedByRole;

    #[Override]
    protected static ?string $model = OrderReturn::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnLeft;

    #[Override]
    protected static ?string $modelLabel = 'retour';

    #[Override]
    protected static ?string $recordTitleAttribute = 'number';

    #[Override]
    protected static ?int $navigationSort = 11;

    protected static function area(): string
    {
        return 'orders';
    }

    public static function getNavigationBadge(): ?string
    {
        $open = OrderReturn::query()->where('status', ReturnStatus::Requested)->count();

        return $open > 0 ? (string) $open : null;
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

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextEntry::make('number')->label('Retour'),
            TextEntry::make('order.number')->label('Commande'),
            TextEntry::make('reason')->label('Motif')->formatStateUsing(fn (mixed $state): string => $state instanceof ReturnReason ? $state->label() : ''),
            TextEntry::make('status')->label('Statut')->formatStateUsing(fn (mixed $state): string => $state instanceof ReturnStatus ? $state->label() : ''),
            TextEntry::make('comment')->label('Commentaire du client')->placeholder('—')->columnSpanFull(),
            TextEntry::make('lines')
                ->label('Articles')
                ->getStateUsing(fn (OrderReturn $record): string => $record->items()->with('orderItem')->get()->map(fn ($line): string => "{$line->quantity} × {$line->orderItem->product_name} ({$line->orderItem->variant_label})")->implode("\n"))
                ->columnSpanFull(),
            TextEntry::make('goods')->label('Valeur des articles')->getStateUsing(fn (OrderReturn $record): int => $record->goodsValue())->money('EUR', divideBy: 100),
            TextEntry::make('refund_amount')->label('Remboursé')->money('EUR', divideBy: 100),
            TextEntry::make('decision_note')->label('Note de décision')->placeholder('—')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('order.user'))
            ->columns([
                TextColumn::make('number')->label('Retour')->searchable(),
                TextColumn::make('order.number')->label('Commande')->searchable(),
                TextColumn::make('order.user.name')->label('Client'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (ReturnStatus $state): string => $state->label())
                    ->color(fn (ReturnStatus $state): string => $state->color()),
                TextColumn::make('created_at')->label('Demandé le')->dateTime('d/m/Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(ReturnStatus::cases())->mapWithKeys(fn (ReturnStatus $status): array => [$status->value => $status->label()])),
            ])
            ->recordActions([
                ViewAction::make(),
                self::stepAction('approve', 'Accepter', Heroicon::OutlinedCheck, 'success', ReturnStatus::Approved)
                    ->action(fn (OrderReturn $record, ApproveReturn $approve) => self::run(fn () => $approve($record))),
                self::stepAction('reject', 'Refuser', Heroicon::OutlinedXMark, 'danger', ReturnStatus::Rejected)
                    ->schema([Textarea::make('note')->label('Motif (visible par le client)')->maxLength(500)])
                    ->action(fn (OrderReturn $record, array $data, RejectReturn $reject) => self::run(fn () => $reject($record, is_string($data['note'] ?? null) ? $data['note'] : null))),
                self::stepAction('receive', 'Colis reçu', Heroicon::OutlinedInboxArrowDown, 'info', ReturnStatus::Received)
                    ->schema([Toggle::make('restock')->label('Remettre les articles en stock')->default(true)])
                    ->action(function (OrderReturn $record, array $data, ReceiveReturn $receive): void {
                        $user = auth()->user();

                        self::run(fn () => $receive($record, (bool) ($data['restock'] ?? false), $user instanceof User ? $user : null));
                    }),
                self::stepAction('refund', 'Rembourser', Heroicon::OutlinedReceiptRefund, 'success', ReturnStatus::Refunded)
                    ->fillForm(fn (OrderReturn $record): array => ['amount' => number_format($record->goodsValue() / 100, 2, '.', '')])
                    ->schema([TextInput::make('amount')->label('Montant TTC à rembourser (€)')->numeric()->minValue(0.01)->required()])
                    ->action(function (OrderReturn $record, array $data, RefundReturn $refund): void {
                        $amount = is_numeric($data['amount'] ?? null) ? (int) round((float) $data['amount'] * 100) : null;

                        self::run(fn () => $refund($record, $amount), 'Remboursement effectué');
                    }),
                Action::make('creditNote')
                    ->label('Avoir PDF')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->visible(fn (OrderReturn $record): bool => $record->status === ReturnStatus::Refunded)
                    ->action(fn (OrderReturn $record, GenerateCreditNote $generate): StreamedResponse => response()->streamDownload(
                        function () use ($record, $generate): void {
                            echo $generate($record)->output();
                        },
                        GenerateCreditNote::filename($record),
                    )),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageReturns::route('/')];
    }

    private static function stepAction(string $name, string $label, Heroicon $icon, string $color, ReturnStatus $target): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->visible(fn (OrderReturn $record): bool => $record->status->canTransitionTo($target));
    }

    private static function run(callable $step, string $success = 'Retour mis à jour'): void
    {
        try {
            $step();
        } catch (InvalidArgumentException|LogicException $e) {
            Notification::make()->title('Action impossible')->body($e->getMessage())->danger()->send();

            return;
        } catch (Throwable $e) {
            report($e);
            Notification::make()->title('Le remboursement a échoué')->body('Le paiement n’a pas pu être remboursé. Réessayez ou consultez Stripe.')->danger()->send();

            return;
        }

        Notification::make()->title($success)->success()->send();
    }
}
