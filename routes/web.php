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

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

Route::get('/corn', 'UserController@getRegister');
Route::post('/corn/add', 'UserController@addRegister');
Route::post('/corn/register', 'UserController@register');
Route::get('/corn/register', 'UserController@register');

Route::get('/more', 'UserController@getLogin');
Route::post('/more/add', 'UserController@addLogin');
Route::post('/more/login', 'UserController@login');
Route::get('/more/login', 'UserController@login');

Route::get('/toby', 'UserController@toby');
