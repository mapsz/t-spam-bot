<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MadelineController;
use App\Http\Controllers\SpamController;

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
Route::get('/test', function () {
  dd(App\Models\Madeline::testMessage('+447789122157'));
  // App\Models\Madeline::test(
  //   [
  //     'work' => 'getAllChats',
  //     'login' => '+79001485597',
  //   ]
  // );
});

// Route::get('/test', [MadelineController::class, 'joinChannel']);
Route::get('/login', [MadelineController::class, 'login']);
Route::get('/send/code', [MadelineController::class, 'sendCode']);


Route::get('/do/actual', [SpamController::class, 'doActual']);
Route::get('/do/forward', [SpamController::class, 'doForward']);


Auth::routes();

//Vue Pages
Route::get('/{vue_capture?}', function () {
  return view('main');
})->where('vue_capture', '[\/\w\.-]*');

// Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

