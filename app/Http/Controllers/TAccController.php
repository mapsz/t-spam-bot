<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use App\Models\TAcc;

class TAccController extends Controller
{
  public static function create(Request $request){

    //Validate
    TAcc::phoneValidate($request->phone);

    $phone = trim($request->phone);

    //Check exists
    $tAcc = TAcc::where('phone', $phone)->first();
    if($tAcc) return 9;

    if(!Auth::user()->id) return 21;
    
    //Create account
    $tAcc = new TAcc;
    $tAcc->owner_id = Auth::user()->id;
    $tAcc->name = "just done 🎭";
    $tAcc->phone = $phone;
    $tAcc->status = 0;
    $tAcc->last_login_at = now();
    return $tAcc->save();

    return response()->json($tAc);

  }

}
