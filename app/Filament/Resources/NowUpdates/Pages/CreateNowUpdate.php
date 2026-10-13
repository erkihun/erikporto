<?php

declare(strict_types=1);

namespace App\Filament\Resources\NowUpdates\Pages;

use App\Filament\Resources\NowUpdates\NowUpdateResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateNowUpdate extends CreateRecord
{
    protected static string $resource = NowUpdateResource::class;
}
