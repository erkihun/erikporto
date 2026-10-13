<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Queries\ColophonQuery;
use Inertia\Inertia;
use Inertia\Response;

final class ColophonController extends Controller
{
    public function __invoke(): Response
    {
        $profile = Profile::current();

        return Inertia::render('Colophon', [
            'colophon' => ColophonQuery::get(),
            'owner' => ['name' => $profile?->name, 'email' => $profile?->email],
        ]);
    }
}
