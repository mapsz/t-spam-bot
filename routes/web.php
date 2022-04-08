<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\MadelineController;
use App\Http\Controllers\SpamController;
use App\Http\Controllers\TAccController;
use App\Http\Controllers\PyrogramController;

Route::get('/test', function () {
  // $a = App\Models\Madeline::testMessage();
  $a = App\Models\Spam::pDoSends();
  // $a = App\Models\WorkProperty::where('work_id', 49)->where('name', 'chat_id')->first()->value;

  // // $body = json_decode($a->body);
  // // $response = json_decode($body->response);


  // $work = App\Models\Work::with('properties')->where('id', 120)->first();

  // $key = $work->properties[array_search('spam_id', array_column($work->properties->toArray(), 'name'))]->value;

  dd($a);

});

Route::get('/crone', function () {
  
  App\Models\Spam::pDoSends();
  App\Models\Spam::pDoJoins();

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

{//Pyrogram
  Route::post('/p/to/login', [PyrogramController::class, 'setToLogin']);
  Route::post('/p/send/code', [PyrogramController::class, 'sendCode']);
  Route::any('/p/api', [PyrogramController::class, 'api']);
}

{//Telegram Account
  Route::post('/t-acc/create', [TAccController::class, 'create']);
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

