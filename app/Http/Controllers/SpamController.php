<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Spam;

class SpamController extends Controller
{
  public static function doActual(){
    Spam::doActual();
  }

  public static function doForward(){
    Spam::doForward();
  }
}
