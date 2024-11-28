<?php

use App\Http\Controllers\QRViewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/qr/list', [QRViewController::class, 'index'])->name('qr.list');
Route::get('/qr/{control_number}', [QRViewController::class, 'show'])->name('qr.show');
