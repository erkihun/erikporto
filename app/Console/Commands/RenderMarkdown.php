<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Images\ImageReferences;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Notes and /now updates store their HTML when saved. After the renderer changes
 * (deploy/deploy.sh runs this on every release), this brings the stored HTML up to date
 * without touching updated_at.
 */
#[AsCommand(name: 'portfolio:render-markdown', description: 'Re-render the stored HTML of every note and /now update')]
final class RenderMarkdown extends Command
{
    public function handle(): int
    {
        $count = 0;
        foreach (ImageReferences::OWNERS as $owner) {
            /** @var Model $prototype */
            $prototype = new $owner;
            if ($prototype->markdownImageAttributes() === []) {
                continue;
            }

            $owner::query()->lazyById()->each(function (Model $record) use (&$count): void {
                $record->refreshRenderedMarkdown();
                $count++;
            });
        }

        $this->components->info("Re-rendered {$count} Markdown document(s).");

        return self::SUCCESS;
    }
}
