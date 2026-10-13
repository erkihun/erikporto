<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SubmitContactMessage;
use App\Http\Requests\StoreContactMessageRequest;
use Illuminate\Http\RedirectResponse;

final class ContactMessageController extends Controller
{
    public function store(StoreContactMessageRequest $request, SubmitContactMessage $submit): RedirectResponse
    {
        // Honeypot hits get the same success response so bots learn nothing.
        if (! $request->isSpam()) {
            $submit->handle($request->toData());
        }

        return back()->with('success', 'Thanks, your message is on its way. I’ll reply within two working days.');
    }
}
