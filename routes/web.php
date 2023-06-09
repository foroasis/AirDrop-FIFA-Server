<?php

use Illuminate\Support\Facades\Route;

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


//bot webhook
$twitterToken = config('twitter.bot_token');
Route::post("/{$twitterToken}/webhook", "BotController@alphababy");

//空投列表
Route::post('airdrop/address', 'AirdropController@list');
Route::post('airdrop/callback', 'AirdropController@callback');
