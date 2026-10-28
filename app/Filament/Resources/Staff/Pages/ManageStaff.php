<?php

declare(strict_types=1);

namespace App\Filament\Resources\Staff\Pages;

use App\Filament\Resources\Staff\StaffResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Override;

final class ManageStaff extends ManageRecords
{
    #[Override]
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        // `role` is deliberately not mass-assignable on User: only this screen may set it.
        return [
            CreateAction::make()->using(function (array $data): User {
                /** @var array<string, mixed> $data */
                return User::unguarded(fn (): User => User::create([...$data, 'email_verified_at' => now()]));
            }),
        ];
    }
}
