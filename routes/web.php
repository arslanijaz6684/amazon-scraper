<?php

use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;

Route::controller(FileController::class)->group(function () {
    Route::get('/','index')->name('index');
    Route::post('/upload','upload')->name('upload');
    Route::post('/scrap','scrap')->name('scrap');
    Route::post('/download','download')->name('download');
    Route::post('/download-new','downloadNew')->name('download.new');
});

