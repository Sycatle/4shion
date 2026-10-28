<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Override;

final class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('days')
                ->label('Période')
                ->options([7 => '7 derniers jours', 30 => '30 derniers jours', 90 => '90 derniers jours', 365 => '12 derniers mois'])
                ->default(30)
                ->selectablePlaceholder(false),
        ]);
    }

    #[Override]
    public function getColumns(): int
    {
        return 2;
    }
}
