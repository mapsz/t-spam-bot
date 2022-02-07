<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MadelineController;
use App\Http\Controllers\SpamController;
use App\Http\Controllers\Auth\LoginController;

Route::get('/test', function () {
  $a = new App\Models\Madeline('+37128885282'); $a->getFullDialogs();

  dd($a);

});


{//Login madeline
  Route::post('/account/login', [MadelineController::class, 'login']);
  Route::post('/send/code', [MadelineController::class, 'sendCode']);
}

{//Crone
  //
  Route::get('/remove/works', function(){App\Models\Spam::removeWorks();});

  Route::get('/do/joins', function(){App\Models\Spam::doJoins(true);});
  
  Route::get('/do/actual', function(){App\Models\Spam::doSends();});

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

