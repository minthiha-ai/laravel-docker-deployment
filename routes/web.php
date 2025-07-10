<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
   return response()->json(['status'=>200, 'message' => 'Your not login']);
})->name('login');
