<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CvDownloadController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        $profile = Profile::current();
        $disk = Storage::disk('local');

        abort_if($profile?->cv_path === null || ! $disk->exists($profile->cv_path), 404);

        return $disk->download($profile->cv_path, Str::slug($profile->name).'-cv.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
