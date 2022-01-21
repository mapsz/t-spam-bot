<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use App\Models\Madeline;

class Spam extends Model
{
  use HasFactory;

  protected $keys = [
    ['key'    => 'id','label' => 'ID'],
    ['key'    => 't_acc_phone','label' => 'Аккаунт'],
    ['key'    => 'name','label' => 'Название'],
    ['key'    => 'peer','label' => 'Группа'],
    ['key'    => 'text','label' => 'Текст'],
    ['key'    => 'delay','label' => 'Задержка'],
    ['key'    => 'status', 'label' => 'Активен','type' => 'intToStr', 'intToStr' =>[
      1 => 'да',
      0 => 'нет',
    ]],
    ['key'    => 'sent_at','label' => 'Last Send At'],
    ['key'    => 'created_at','label' => 'Создан'],
  ];
  
  protected $inputs = [
    [
      'name' => 'status',
      'caption' => 'Активен',
      'type' => 'checkbox'
    ],
    [
      'name' => 't_acc_phone',
      'caption' => 'Аккаунт',
      'type' => 'text'
    ],
    [
      'name' => 'name',
      'caption' => 'Название',
      'type' => 'text'
    ],
    [
      'name' => 'peer',
      'caption' => 'Группа',
      'info' => 'ссылка (https://t.me/XXXXX)',
      'type' => 'text'
    ],
    [
      'name' => 'delay',
      'caption' => 'Задержка',
      'info' => 'минуты',
      'type' => 'number'
    ],
    [
      'name' => 'text',
      'caption' => 'Текст',
      'type' => 'textEditor'
    ],
  ];


  public static function doForward(){
    $logins = ['+447789122157','+380992416157'];

    

    foreach ($logins as $login) {
      $users = Madeline::getUnreadUsers($login);

      
      foreach ($users as $key => $user) {
        //Get messages
        $messages = Madeline::getHistory($login, $user['peer']->user_id, $user['unread_count']);      

        //Form toForward
        $messageIds = [];
        foreach ($messages as $key => $message) {
          array_push($messageIds, $message->id);        
        }
        
        {//Forward
          $forwardTo = false;
          if($login == '+447789122157') $forwardTo = 'miner2t2';
          if($login == '+380992416157') $forwardTo = 'miner2t2';
          
          $doForward = false;
          if($forwardTo){
  
            $doForward = Madeline::forwardMessages($login, $messageIds, $user['peer']->user_id, $forwardTo);
  
            dump($doForward);
            if($doForward){
              Madeline::readHistory($login, $user['peer']->user_id);
            }
          }
        }

      }
    }

   
    
  }

  public static function doActual(){

    $spams = self::getActual();

    $joinChatsAccounts = [];
    foreach ($spams as $key => $spam) {
      
      //Join Channel
      if(array_search($spam->t_acc_phone, $joinChatsAccounts) === false){
        Spam::joinAllChats ($spam->t_acc_phone);
        array_push($joinChatsAccounts, $spam->t_acc_phone);
      }
            
      //Send
      self::send($spam);
    }

  }

  public static function getActual(){

    $spams = Spam::where('status', '1')->get();

    $actualSpams = [];
    foreach ($spams as $key => $spam) {
      if($spam->sent_at == NULL || \Carbon\Carbon::parse($spam->sent_at)->add($spam->delay,'minutes') < now()) array_push($actualSpams, $spam);
    }

    return $actualSpams;

  }

  public static function joinAllChats($login){
    
    //Get Chats
    $chats = Madeline::getAllChats($login);    

    $spams = Spam::where('status', '1')->where('t_acc_phone', $login)->get();

    $joined = 0;
    foreach ($spams as $key => $spam) {

      if($joined > 4){
        dump('to many joins');
        return;        
      }

      //Trim peer
      $peer = str_replace('http://t.me/', '', $spam->peer);
      $peer = str_replace('https://t.me/', '', $peer);

      //Check already joined
      $join = true;
      foreach ($chats as $key => $chat) {
        if(isset($chat->username) && $chat->username == $peer){
          $join = false;
          break;
        } 
      }

      dump('join - ' . $peer);

      //Join
      if($join){
        dump(Madeline::joinChannel($login, $spam->peer));
        $joined++;
      }

    }
    
  }

  public static function send($spam){

    Madeline::sendMessage($spam->t_acc_phone, $spam->peer, $spam->text);

    $spam->sent_at = now();

    $spam->save();

  }



  //JugeCRUD  
  public function jugeGetInputs(){
    $inputs = $this->inputs;
    $fInputs = [];
    foreach ($inputs as $key => $input) {
      if($input['name'] == 'status') continue;
      array_push($fInputs, $input);
    }
    return $fInputs;
  }
  public function jugeGetPostInputs()   {return $this->inputs;}
  public function jugeGetKeys()         {return $this->keys;} 


  public static function jugeGet($request = []) {
    //Model
    $query = new self;
  
    {//With
      //
    }
  
    {//Where
      $query = JugeCRUD::whereSearches($query,$request);

      
      {//Owner
        $user = Auth::user(); 
        if($user) $query = $query->where('owner_id', $user->id);        
      }

    }

    //Order
    $query = $query->OrderBy('created_at', 'DESC');
  
    //Get
    $data = JugeCRUD::get($query,$request);
  
    //Single
    if(isset($request['id']) && isset($data[0])){$data = $data[0];}
  
    //Return
    return $data;
  }

  //Pre validate edits
  public static function jugePostPreValidateEdits($data){return self::preValidateEdits($data);}
  public static function jugePutPreValidateEdits($data){    
    $user = Auth::user(); 
    if($user) $data['owner_id'] = $user->id;  
    return self::preValidateEdits($data);
  }
  public static function preValidateEdits($data){
    //Clear html tags
    if(isset($data['text'])){
      $data['text'] = strip_tags($data['text'], ['<br>', '<p>']);
      $data['text'] = str_replace('<br>',"\n",$data['text']);
      $data['text'] = str_replace('<p>',"",$data['text']);
      $data['text'] = str_replace('</p>',"\n",$data['text']);
    }

    return $data;
  }

  //Validate
  public static function jugePostValidate($data){return self::validate($data, 'post');}
  public static function jugePutValidate($data){return self::validate($data, 'put');}
  public static function validate($data, $type){

    //Validate
    Validator::make($data, [
      'name'                  => 'string|max:191',
      'peer'                  => 'string|max:191',
      'delay'                 => 'numeric',
      'text'                  => 'string|max:650',
    ])->validate();

    //t_acc_phone
    if(isset($data['t_acc_phone'])){
      $valid = preg_match('/^[+][0-9]{6}[0-9]*$/', $data['t_acc_phone']);
      Validator::make(['valid' => $valid], ['valid' => 'required|accepted'], ['valid.accepted' => 'Аккаунт должен быть подобного формата +77559874422'])->validate();
    }

    //peer
    if(isset($data['peer'])){
      $valid = preg_match('/^https:\/\/t.me\/.{3}.*$/', $data['peer']);
      Validator::make(['valid' => $valid], ['valid' => 'required|accepted'], ['valid.accepted' => 'Группа должен быть подобного формата https://t.me/Shopmining1'])->validate();
    }

    //More put things
    if($type == 'put'){
      Validator::make($data, [
        't_acc_phone'           => 'required',
        'peer'                  => 'required',
        'delay'                 => 'required',
        'text'                  => 'required',
      ])->validate();
    }

    return true;

  }

}
