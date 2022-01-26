<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use App\Models\Madeline;
use App\Models\tAcc;

class MadelineController extends Controller
{
  public static function login(Request $request){

    //Validate
    tAcc::phoneValidate($request->phone);

    $phone = trim($request->phone);

    //Check exists
    $tAcc = tAcc::where('phone', $phone)->first();

    if($tAcc && $tAcc->owner_id != Auth::user()->id) return 9;


    //Try login
    $madeline = new Madeline($phone);
    $login = $madeline->login();

    //Already login
    if($login == "already log in"){
      //Create account if doesnt exists
      if(!$tAcc){
        tAcc::createFroLoginInfo($madeline->getLoginInfo());
      }
      return 5;
    } 
    if($login == "need code") return 4;
    if($login == "flood") return 3;

    return 0;
  }

  public static function sendCode(Request $request){

    $phone = trim($request->phone);

    $madeline = new Madeline($phone);
    $sendCode = $madeline->loginSendCode($request->code);

    if($sendCode === "bad code") return 8;
    if($sendCode === 'already log in') $sendCode = true;
    if($sendCode){

      $tAcc = tAcc::where('phone', $phone)->first();
      if(!$tAcc){
        $madeline->getSelf();
        tAcc::createFroLoginInfo($madeline->getLoginInfo());
      }

      tAcc::updateLoginTime($phone);

      return 1;

    }

    // php artisan make:migration add_last_login_at_to_t_accs_table --table=users
    
    return false;

  }

  public static function joinChannel(Request $request){

    Madeline::joinChannel('+79001485597', 'juge_playground');

    return 1;
  }


}
