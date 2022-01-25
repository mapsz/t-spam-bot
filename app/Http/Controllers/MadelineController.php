<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

use App\Models\Madeline;
use App\Models\tAcc;

class MadelineController extends Controller
{
  public static function login(Request $request){

    //Validate
    tAcc::phoneValidate($request->phone);

    //Try login
    $login = Madeline::login($request->phone);

    
    if($login == "already log in") return 5;
    if($login == "need code") return 4;

    return 0;
  }

  public static function sendCode(Request $request){

    $sendCode = Madeline::loginSendCode($request->phone, $request->code);


    

    return response()->json($sendCode);

  }

  public static function joinChannel(Request $request){

    Madeline::joinChannel('+79001485597', 'juge_playground');

    return 1;
  }


}
