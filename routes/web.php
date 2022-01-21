<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MadelineController;
use App\Http\Controllers\SpamController;
use App\Http\Controllers\Auth\LoginController;

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

// Route::get('/', function () {
//   return view('welcome');
// });

Route::get('/test', function () {
  dd(App\Models\Madeline::testMessage('+447789122157'));
  // App\Models\Madeline::test(
  //   [
  //     'work' => 'getAllChats',
  //     'login' => '+79001485597',
  //   ]
  // );
});


{//Login madeline
  Route::get('/login', [MadelineController::class, 'login']);
  Route::get('/send/code', [MadelineController::class, 'sendCode']);
}

{//Crone
  Route::get('/do/actual', [SpamController::class, 'doActual']);
  Route::get('/do/forward', [SpamController::class, 'doForward']);
}

//Auth
Auth::routes();
Route::get('/auth/user', function (){return response()->json(Auth::user());});
Route::post('/logout', [LoginController::class, 'jsonLogout']);

//Juge CRUD
Route::middleware([])->group(function (){
  Route::get('/juge', 'App\Http\Controllers\JugeCRUDController@get');
  Route::get('/juge/keys', 'App\Http\Controllers\JugeCRUDController@getKeys');
  Route::get('/juge/inputs', 'App\Http\Controllers\JugeCRUDController@getInputs');    
  Route::get('/juge/post/inputs', 'App\Http\Controllers\JugeCRUDController@getPostInputs');    
  Route::put('/juge', 'App\Http\Controllers\JugeCRUDController@put');
  Route::post('/juge', 'App\Http\Controllers\JugeCRUDController@post');
  Route::delete('/juge', 'App\Http\Controllers\JugeCRUDController@delete'); 
  
  //Config
  Route::post('/juge/crud/settings', 'App\Http\Controllers\JugeCRUDController@postConfig');
});


Route::group(['middleware' => ['auth']], function (){

  //Vue Pages
  Route::get('/{vue_capture?}', function () {
    return view('main');
  })->where('vue_capture', '[\/\w\.-]*');

});



// Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

