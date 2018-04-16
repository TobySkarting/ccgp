<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

Route::prefix('register')->group(function () {
    Route::get('/', 'UserController@getRegister')->name('register');
    Route::post('/add', 'UserController@addRegister');
    Route::post('/', 'UserController@register');
});

Route::prefix('login')->group(function () {
    Route::get('/', 'UserController@getLogin')->name('login');
    Route::post('/add', 'UserController@addLogin');
    Route::post('/', 'UserController@login');
});

Route::post('/logout', 'UserController@logout')->name('logout');

Route::get('/toby', 'UserController@toby');
