<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

use App\Models\Madeline;

class MadelineController extends Controller
{
  public static function login(Request $request){

    if(!isset($request->login)) dd('no login');

    $login = Madeline::login($request->login);

    if($login  == 'need code'){
      return view('getCode', ['login' => $request->login]);
    }

    dump($login);

    return 0;
  }

  public static function sendCode(Request $request){

    if(!isset($request->login)) dd('no login');
    if(!isset($request->code)) dd('no code');

    Madeline::loginSendCodeAndTest($request->login, $request->code);

  }

  public static function joinChannel(Request $request){

    Madeline::joinChannel('+79001485597', 'juge_playground');

    return 1;
  }


}
