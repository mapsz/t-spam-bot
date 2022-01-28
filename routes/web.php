<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MadelineController;
use App\Http\Controllers\SpamController;
use App\Http\Controllers\Auth\LoginController;

Route::get('/test', function () {
  $a = 'Telegram returned an RPC error: FLOOD_WAIT_X (420) (FLOOD_WAIT_52860), caused by';


  $b = "~{\"result\":.,\"text\":`(.*)`}~";

  $matches = [];
  preg_match(
    "~Telegram returned an RPC error: FLOOD_WAIT_X [(]420[)] [(]FLOOD_WAIT_([0-9]*)[)], caused by~",
    $a,
    $matches
  );

  dd($matches);

});


{//Login madeline
  Route::post('/account/login', [MadelineController::class, 'login']);
  Route::post('/send/code', [MadelineController::class, 'sendCode']);
}

{//Crone
  Route::get('/do/actual', [SpamController::class, 'doActual']);
  // Route::get('/do/forward', [SpamController::class, 'doForward']);
}

//Auth
Route::group([], function (){
  Auth::routes();
  Route::get('/auth/user', function (){return response()->json(Auth::user());});
  Route::post('/logout', [LoginController::class, 'jsonLogout']);
});

//Juge CRUD
Route::middleware(['auth'])->group(function (){
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

