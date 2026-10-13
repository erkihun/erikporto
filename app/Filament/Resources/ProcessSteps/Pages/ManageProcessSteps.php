<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProcessSteps\Pages;

use App\Filament\Resources\ProcessSteps\ProcessStepResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

final class ManageProcessSteps extends ManageRecords
{
    protected static string $resource = ProcessStepResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
