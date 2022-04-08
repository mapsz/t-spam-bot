<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use App\Models\Meta;

class TAcc extends Model
{
  use HasFactory;


  protected $keys = [
    ['key'    => 'id','label' => 'ID'],
    ['key'    => 'name','label' => 'Название'],
    ['key'    => 'phone','label' => 'Телефон'],
    ['key'    => 'status', 'label' => 'Логин', 'type' => 'custom', 'component' => 'telegram-account-status'],
    // ['key'    => 'status', 'label' => 'Логин',
    //   'type' => 'intToStr', 'intToStr' =>[
    //   1 => 'Логинен 🐢',
    //   0 => 'НЕлогинен ❌😱',
    //   2 => 'Логинем 🐤',
    //   -1 => 'бан ⛱️',
    // ]],
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

  public static function startWork($acc){
    $acc->work_at = now();
    return $acc->save();
  }

  public static function stopWork($acc){
    $acc->work_at = null;
    $acc->save();
  }

  public static function setNotLogin($phone){

    dumo('setNotLogin');

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

  public static function getWithActualSpams(){
    //Model
    $query = new self;

    $query = $query->with(['spams' => function($q){
      $q->where('status', 1);
    }]);

    $query = $query->whereHas('spams', function($q){
      $q = $q->where('status',1);
      $q = $q->whereNotNull('group_joined_at');
    });

    $query = $query->where('status', '>=' ,0);

    $query = $query->whereNull('work_at');
  
    $query = $query->orderBy('updated_at', 'ASC');

    $data = $query->get();

    //Remove not actual spams
    $gotActualSpam = false;
    foreach ($data as $k => $row) {      
      $actualSpams = [];
      foreach ($row->spams as $kspam => $spam) {
        if(
          (\Carbon\Carbon::parse($spam->sent_at)->add($spam->delay,'minutes') < now()) ||
          $spam->sent_at == NULL          
        ){
          array_push($actualSpams, $spam->id);
        }
      }
                
      if(count($actualSpams) > 0){
        $data[$k]->actualSpams = $actualSpams;
        $acc = $data[$k]->id;
        $gotActualSpam = true;
        break;
      }
    }
     
    if(!$gotActualSpam) return false;
    
    $data = self::jugeGet(['id' => $acc, 'spam_ids' => $actualSpams]);

    return $data;

  }

  public static function getByCurrentUser(){

    $user = Auth::user(); 
    if(!$user || $user->id < 1) return false;

    $accs = self::where('owner_id', $user->id)->get();

    $accs = $accs->pluck('phone');

    return $accs;

  }

  public static function jugeGet($request = []) {

    //Model
    $query = new self;
  
    {//With
      //Spams
      if((isset($request['spams']) && $request['spams'])){
        $query = $query->with('spams');
      } 

      //Spam ids
      if((isset($request['spam_ids']) && is_array($request['spam_ids']))){
        $spamIds = $request['spam_ids'];
        $query = $query->with(['spams' => function($q)use($spamIds){
          $q->whereIn('id', $spamIds);
        }]);
      }

      //Active spams
      if((isset($request['forSpam']) && $request['forSpam'])){
        $query = $query->with(['spams' => function($q){
          $q->where('status', 1);
        }]);
      }

      //Metas
      $query = $query->with('metas');

    }
  
    {//Where

      $user = Auth::user(); 
      if($user && $user->id != 1) $query = $query->where('owner_id', $user->id);      

      //Juge searches
      $query = JugeCRUD::whereSearches($query,$request);

      //Phone
      if(isset($request['phone']) && $request['phone']){
        $query = $query->where('phone', $request['phone']);
      }

      //Active spams
      if(isset($request['forSpam']) && $request['forSpam']){
        $query = $query->whereHas('spams', function($q){
          $q->where('status',1);
        });

        $query = $query->whereNull('work_at');
      } 
    }
  
    //Order by
    $query = $query->OrderBy('created_at', 'DESC');
  
    //Get
    $data = JugeCRUD::get($query,$request);

    
    {//After query work
      foreach ($data as $k => $row) {
       // 
      }

      $data= JugeCRUD::setMetas($data);
    }
  
    //Single
    if(isset($request['id']) && isset($data[0])){$data = $data[0];}

  
    //Return
    return $data;
  }

  public function jugeGetKeys()         {return $this->keys;} 

  //Relations
  public function spams(){
    return $this->hasMany('App\Models\Spam', 't_acc_phone', 'phone');
  }   
  public function forward(){
    return $this->hasOne('App\Models\Forward', 'acc', 'phone');
  }   
  public function metas(){
    return $this->morphMany(Meta::class, 'metable');
  }  

}
