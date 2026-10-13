<?php

declare(strict_types=1);

namespace App\Filament\Resources\Profiles\Pages;

use App\Filament\Resources\Profiles\ProfileResource;
use App\Models\Profile;
use Filament\Resources\Pages\ListRecords;

final class ListProfiles extends ListRecords
{
    protected static string $resource = ProfileResource::class;

    /** Skip the one-row list and go straight to the editor. */
    public function mount(): void
    {
        $profile = Profile::current();

        if ($profile !== null) {
            $this->redirect(ProfileResource::getUrl('edit', ['record' => $profile]));

            return;
        }

        parent::mount();
    }
}
