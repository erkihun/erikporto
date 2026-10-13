<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Profile;
use Illuminate\Http\Response;

final class FeedController extends Controller
{
    public function __invoke(): Response
    {
        $notes = Note::query()->published()->latestFirst()->limit(30)->get();

        return response()
            ->view('feed', [
                'notes' => $notes,
                'author' => Profile::current()?->name ?? config('app.name'),
                'updated' => $notes->max('updated_at') ?? now(),
            ])
            ->header('Content-Type', 'application/atom+xml; charset=UTF-8');
    }
}
