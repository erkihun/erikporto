<?php

declare(strict_types=1);

use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\CvDownloadController;
use App\Http\Controllers\FallbackController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\NowController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/*
 * Pages that exist in every language: English at `/`, Amharic under `/am`
 * (route names "home" / "am.home"; see App\Enums\Locale and App\Support\LocalizedUrls).
 */
$localized = static function (): void {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/work/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::post('/contact', [ContactMessageController::class, 'store'])
        ->middleware('throttle:contact')
        ->name('contact.store');
};

Route::middleware('locale:en')->group($localized);
Route::prefix('am')->name('am.')->middleware('locale:am')->group($localized);

// Written in one language and not translated.
Route::get('/notes', [NoteController::class, 'index'])->name('notes.index');
Route::get('/notes/{note}', [NoteController::class, 'show'])->name('notes.show');
Route::get('/feed.xml', FeedController::class)->name('feed');
Route::get('/now', NowController::class)->name('now');

Route::get('/cv', CvDownloadController::class)->middleware('throttle:30,1')->name('cv.download');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// Last: unknown URLs get the designed 404 in the language of their path.
Route::fallback(FallbackController::class)->middleware('locale');
