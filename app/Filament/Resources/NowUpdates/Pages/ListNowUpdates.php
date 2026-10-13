<?php

declare(strict_types=1);

namespace App\Filament\Resources\NowUpdates\Pages;

use App\Filament\Resources\NowUpdates\NowUpdateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListNowUpdates extends ListRecords
{
    protected static string $resource = NowUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
