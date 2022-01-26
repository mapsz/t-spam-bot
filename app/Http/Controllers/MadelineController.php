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

    //Check exists
    $tAcc = tAcc::where('phone', $request->phone)->first();

    if($tAcc) dd('exists!');


    //Try login
    $madeline = new Madeline($request->phone);
    $login = $madeline->login();

    //Already login
    if($login == "already log in"){
      //Create account if doesnt exists
      if(!$tAcc){
        $loginInfo = $madeline->getLoginInfo();

        dd($loginInfo);
      }
      return 5;
    } 
    if($login == "need code") return 4;

    return 0;
  }

  public static function sendCode(Request $request){

    $madeline = new Madeline($request->phone);
    $madeline->loginSendCode($request->code);


    

    return response()->json($sendCode);

  }

  public static function joinChannel(Request $request){

    Madeline::joinChannel('+79001485597', 'juge_playground');

    return 1;
  }


}
