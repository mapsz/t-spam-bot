<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class TAcc extends Model
{
  use HasFactory;


  protected $keys = [
    ['key'    => 'id','label' => 'ID'],
    ['key'    => 'name','label' => 'Название'],
    ['key'    => 'phone','label' => 'Телефон'],
    ['key'    => 'status', 'label' => 'Логин',
      'type' => 'intToStr', 'intToStr' =>[
      1 => 'Логинен 🐢',
      0 => 'НЕлогинен ❌😱',
    ]],
    ['key'    => 'created_at','label' => 'Создан', 'type' => 'moment', 'moment' => 'lll'],
  ];

  public static function updateLoginTime($phone){

    //Get acc
    $tAcc = self::where('phone', $phone)->first();
    if(!$tAcc) return false;

    $tAcc->last_login_at = now();
    $tAcc->status = 1;

    return $tAcc->save();

  }

  public static function setNotLogin($phone){

    //Get acc
    $tAcc = self::where('phone', $phone)->first();
    if(!$tAcc) return false;

    $tAcc->last_login_at = NULL;
    $tAcc->status = 0;

    return $tAcc->save();

  }

  public static function createFroLoginInfo($loginInfo){

    {//Phone check
      if(!isset($loginInfo->phone)) return false;
      $phone = $loginInfo->phone;
      if(strpos($phone, '+') === false) $phone = '+'.$phone;
    }

    //Create account
    $tAcc = new TAcc;
    $tAcc->owner_id = Auth::user()->id;
    $tAcc->name = isset($loginInfo->first_name) ? $loginInfo->first_name : "none";
    $tAcc->phone = $phone;
    $tAcc->status = 1;
    $tAcc->last_login_at = now();
    return $tAcc->save();
    
  }

  public static function phoneValidate($phone){
    $valid = preg_match('/^[+][0-9]{6}[0-9]*$/', $phone);
    Validator::make(['valid' => $valid], ['valid' => 'required|accepted'], ['valid.accepted' => 'Аккаунт должен быть подобного формата +77559874422'])->validate();    
  }


  public static function jugeGet($request = []) {
    //Model
    $query = new self;
  
    {//With
      //
    }
  
    {//Where
      $query = JugeCRUD::whereSearches($query,$request);
    }
  
    //Order by
    $query = $query->OrderBy('created_at', 'DESC');
  
    //Get
    $data = JugeCRUD::get($query,$request);
  
    //Single
    if(isset($request['id']) && isset(data[0])){$data = $data[0];}
  
    //Return
    return $data;
  }

  public function jugeGetKeys()         {return $this->keys;} 


}
