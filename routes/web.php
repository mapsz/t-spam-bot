<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MadelineController;
use App\Http\Controllers\SpamController;
use App\Http\Controllers\Auth\LoginController;

Route::get('/test', function () {
  dd(

    json_decode('{"result":1,"text":{"_":"auth.authorization","setup_password_required":false,"user":{"_":"user","self":true,"contact":false,"mutual_contact":false,"deleted":false,"bot":false,"bot_chat_history":false,"bot_nochats":false,"verified":false,"restricted":false,"min":false,"bot_inline_geo":false,"support":false,"scam":false,"apply_min_photo":true,"fake":false,"id":517183883,"access_hash":-7849556256254681675,"first_name":"\u042e\u0440\u0430","username":"jurijsgergelaba","phone":"37128885282","photo":{"_":"userProfilePhoto","has_video":false,"photo_id":2221287863959529390,"stripped_thumb":{"_":"bytes","bytes":"AQgIJJmVNvmHbnGMjGKKKKLAfw=="},"dc_id":4},"status":{"_":"userStatusOffline","was_online":1642854814}}}}')
    ->text
  );
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

