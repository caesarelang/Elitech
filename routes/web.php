<?php

use Illuminate\Support\Facades\Route;

Route::get('/ppic/{any?}', function () {
    return view('ppic');
})->where('any', '^(?!api).*$');

Route::get('/produksi/{any?}', function () {
    return view('produksi');
})->where('any', '^(?!api).*$');
