<?php

declare(strict_types=1);

use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\CvDownloadController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/work/{project}', [ProjectController::class, 'show'])->name('projects.show');
Route::post('/contact', [ContactMessageController::class, 'store'])
    ->middleware('throttle:contact')
    ->name('contact.store');
Route::get('/cv', CvDownloadController::class)->middleware('throttle:30,1')->name('cv.download');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
