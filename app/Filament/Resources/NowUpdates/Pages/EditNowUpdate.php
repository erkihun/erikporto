<?php

declare(strict_types=1);

namespace App\Filament\Resources\NowUpdates\Pages;

use App\Filament\Resources\NowUpdates\NowUpdateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditNowUpdate extends EditRecord
{
    protected static string $resource = NowUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
