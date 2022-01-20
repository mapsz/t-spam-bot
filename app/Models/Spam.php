<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ['key'    => 'delay','label' => 'Только первый заказ'],
    ['key'    => 'status','label' => 'Статус'],
    ['key'    => 'sent_at','label' => 'Last Send At'],
    ['key'    => 'created_at','label' => 'Создан'],
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
  public function jugeGetInputs()       {return $this->inputs;}
  public function jugeGetPostInputs()   {return $this->postInputs;}
  public function jugeGetKeys()         {return $this->keys;} 


  public static function jugeGet($request = []) {
    //Model
    $query = new self;
  
    {//With
      //
    }
  
    {//Where
      //
    }
  
    //Get
    $data = JugeCRUD::get($query,$request);
  
    //Single
    if(isset($request['id']) && isset(data[0])){$data = $data[0];}
  
    //Return
    return $data;
  }

}
