<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;

use App\Models\JugeLogs;
use App\Models\TAcc;
use App\Models\Meta;

class Madeline extends Model
{
  use HasFactory;

  private $debug = false;
  // private $debug = true;
  private $login;
  private $loginInfo;
  private $cryptKey = 'pSUmlYgwbfAu57cH@yH4Ky9z6KHC9OJa';
  private $error = false;
  private $log;

  // $a = new Madeline('+37128885282'); $a->getSelf();


  private static function getUrl(){return getenv('APP_URL') . 'madeline/';}
  private function setLoginInfo($loginInfo){return $this->loginInfo = $loginInfo;}
  public function getLoginInfo(){return $this->loginInfo;}
  private function setLogin($login){return $this->login = $login;}
  private function getLogin(){return $this->login;}
  private function getCryptKey(){return $this->cryptKey;}  
  private function getDebug(){return $this->debug;}
  private function setError($error){return $this->error = $error;}
  private function getError(){return $this->error;}
  private function setLog($log){return $this->log = $log;}
  private function getLog(){return $this->log;}
  
  public function __construct($login) {
    $this->setLogin($login);
  }
  
  //Core
  private function encrypt($message): string{
    if(gettype($message) !== 'string') $message = json_encode($message);
    $key = $this->getCryptKey();

    if (mb_strlen($key, '8bit') !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
        return ('Key is not the correct size (must be 32 bytes).');
    }
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    
    $cipher = base64_encode(
        $nonce.
        sodium_crypto_secretbox(
            $message,
            $nonce,
            $key
        )
    );
    sodium_memzero($message);
    sodium_memzero($key);
    return $cipher;
  }
  
  private function decrypt(string $encrypted): string{
    $key = $this->getCryptKey();

    $decoded = base64_decode($encrypted);
    $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
    $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
    
    $plain = sodium_crypto_secretbox_open(
        $ciphertext,
        $nonce,
        $key
    );
    if (!is_string($plain)) {
        throw new Exception('Invalid MAC');
    }
    sodium_memzero($ciphertext);
    sodium_memzero($key);
    return $plain;
  }

  private function _query($work, $params=[]){
    
    //Start log
    $log = new MadelineLog($this->getLogin(), debug_backtrace()[1]['function']);
    $this->setLog($log);

    $data['work'] = $work;
    $data['login'] = $this->getLogin();
    $data['params'] = $params;
    
    //Encrypt params
    $encrypt = $this->encrypt($data);

    //Query
    $request = Http::get(self::getUrl(), ['enc' => $encrypt]);
    $response = (string) $request->getBody();
    $result = self::resultDecode($response);

    //Debug
    if($this->getDebug()){
      dump($response);
    }

    return $result;

  }

  private function checkBan($result){
    //Login ban
    if(
      gettype($result) == 'string' && 
      strpos($result, "The current account was banned from telegram due to abuse (401) (USER_DEACTIVATED_BAN)") !== false
    ){
      $tAcc = TAcc::where('phone', $this->getLogin())->update(
        ['status' => -1]
      );
      $this->getLog()->info('Set Account Ban');
      return true;
    } 
  }
  
  public function checkLogin(){
    $self = $this->getSelf();

    if(!$self){
      TAcc::setNotLogin($this->getLogin());
      return false;
    }

    if(isset($self->_) && $self->_ == 'user'){
      TAcc::updateLoginTime($this->getLogin());
      return true;
    } 

    return false;
  }
  private function setFlood($time, $type){
    $meta = new Meta;
    $meta->metable_id = $this->getLogin();
    $meta->metable_type = "App\Models\TAcc";
    $meta->name = $type;
    $meta->value = now()->timestamp + $time;
    return $meta->save();
  }


  //To madeline
  public function getSelf(){

    $result = $this->_query('getSelf');
    if(isset($result->_) && $result->_ == 'user'){
      $this->setLoginInfo($result);
      $this->getLog()->success($result);
      return $result;
    } 

    {//Catch errors
      if($this->checkBan($result)) return false;
    }

    //Log
    $this->getLog()->fail($result);
    
    return false;
  }

  public function login(){

    if($this->checkLogin()) return 'already log in';
    
    $result = $this->_query('phoneLogin');
        
    //Success
    if($result->_ == 'auth.sentCode'){
      $this->getLog()->success($result);
      return 'need code';
    }
    
    {//Errors
      //Flood
      if(gettype($result) == 'string' && strpos($result, "FLOOD_WAIT_X (420)") !== false){
        $this->getLog()->info('flood');
        return 'flood';
      }
    }    

    $this->getLog()->fail($result);
    return false;

  }

  public function loginSendCode($code, $relog = false){

    if($this->checkLogin()) return 'already log in';

    $result = $this->_query('completePhoneLogin', ['code' => $code]);

    {//Success
      if(isset($result->_) && $result->_ == "auth.authorization" && isset($result->user)){
        $this->getLog()->success($result);
        return true;
      }
    }
    
    {//Catch errors
      if(!isset($result) || !$result){
        $this->getLog()->fail($result);
        return false;
      }
  
      if(gettype($result) == 'string' && strpos($result, "I'm not waiting for the code!") !== false){
  
        //Try relog
        if(!$relog){
          $login = $this->login();
          if($login == 'need code'){
            $this->getLog()->success($result);
            return $this->loginSendCode($code, true);
          }
        }
        
        $this->getLog()->info("I'm not waiting for the code!");
        return "I'm not waiting for the code!";
      }
  
      if(gettype($result) == 'string' && strpos($result, "The provided phone code is invalid") !== false){
        $this->getLog()->info("bad code");
        return "bad code";
      }
    }


    $this->getLog()->fail($result);
    return false;

  }

  public function joinChannel($channel){

    //Madeline
    $result = $this->_query('channels.joinChannel', ['channel' => $channel]);
    
    {//Success

      $success = false;

      if(gettype($result) == 'string' && strpos($result, "Telegram returned an RPC error: The user is already in the group (400) (USER_ALREADY_PARTICIPANT),") !== false) $success = true;
    
      if(isset($result->chats) && isset($result->chats[0]) && isset($result->_) && $result->_ == 'updates') $success = true;
      
      if($success){
        $this->getLog()->success($result);
        return true;
      }     

    }
    
    {//Catch errors

      //Check ban
      if($this->checkBan($result)) return false;

      //Invalid peer
      if(gettype($result) == 'string' && strpos($result, "The provided peer id is invalid") !== false){
        $this->getLog()->info('The provided peer id is invalid');
        return false;
      }

      //Peer ban
      if(gettype($result) == 'string' && strpos($result, "This peer is not present in the internal peer database in") !== false){
        $this->setError('This peer is not present in the internal peer database in');

        $spam = Spam::where('t_acc_phone', $this->getLogin())->where('peer', $channel)->update(
          [
            'status' => -1,
            'group_joined_at' => NULL,
          ]
        );

        $this->getLog()->info('ban');

        return false;
      }
      
      //Set flood
      if($result && gettype($result) == 'string'){
        
        $matches = [];
        preg_match(
          "~^Telegram returned an RPC error: FLOOD_WAIT_X [(]420[)] [(]FLOOD_WAIT_([0-9]*)[)], caused by~",
          $result,
          $matches
        );

        if(isset($matches[1]) && $matches[1]){
          $this->setFlood($matches[1], "JoinChannelFlood");
          $this->getLog()->info('Flood');
          return false;
        }
      }
    }

    //Unknown Fail
    $this->getLog()->fail($result);
    
    return false;
  }

  
  public function getAllChats(){

    //Get chats
    $chats = $this->_query('messages.getAllChats');
  
    //Check ban
    if($this->checkBan($chats)) return false;

    //Check chats exists 
    if(
      !$chats || 
      !isset($chats->_) || 
      $chats->_ != "messages.chats" || 
      !isset($chats->chats) || 
      !is_array($chats->chats)
    ){
      $this->getLog()->fail($result);
      return false;
    } 

    $this->getLog()->success($result);
    return $chats->chats;
  }

  public function sendMessage($peer, $text){
 
    $params = [
      'peer' => $peer,
      'message' => $text
    ];

    $result = $this->_query('messages.sendMessage', $params);
    
    {//Success
      {//Short
        if(
          isset($result->_) && 
          $result->_ == 'updateShortSentMessage' && 
          isset($result->request) && 
          isset($result->request->_) && 
          $result->request->_ == "messages.sendMessage"
        ){
          $this->getLog()->success($result);
          return true;
        }
      }
      
      {//Other
        if(
          isset($result->_) &&
          $result->_ == 'updates' &&
          isset($result->request) &&
          isset($result->request->_) &&
          $result->request->_ == 'messages.sendMessage' &&
          isset($result->users) &&
          isset($result->users[0]) &&
          isset($result->chats) &&
          isset($result->chats[0])
        ){
          $this->getLog()->success($result);
          return true;
        }
      }
    }

    {//Catch errors

      //Check ban
      if($this->checkBan($result)) return false;

      //Peer ban
      if(
        gettype($result) == 'string' && 
        (
          // strpos($result, "You can't write in this chat (403)") !== false ||
          strpos($result, "You are spamreported, you can't do this (400)") !== false
        )        
      ){

        $spam = Spam::where('t_acc_phone', $this->getLogin())->where('peer', $peer)->update(
          [
            'status' => -1,
            'group_joined_at' => NULL,
          ]
        );

        $this->getLog()->info('ban');

        return false;
      }    

      //Flood
      if(gettype($result) == 'string'){
        
        if(strpos($result, "Slowmode is enabled in this chat: wait X seconds before sending another message to this chat. (420)")){
          $matches = [];
          preg_match(
            "~[(]SLOWMODE_WAIT_([0-9]*)[)],~",
            $result,
            $matches
          );
  
          if(isset($matches[1]) && $matches[1]){
            $this->setFlood($matches[1], 'sendMessageFlood');
            return false;
          }else{
            $this->getLog()->info('flood');
          }
        }

        if(strpos($result, "Telegram returned an RPC error: FLOOD_WAIT_X (420)")){
          $matches = [];
          preg_match(
            "~^Telegram returned an RPC error: FLOOD_WAIT_X [(]420[)] [(]FLOOD_WAIT_([0-9]*)[)], caused by~",
            $result,
            $matches
          );
  
          if(isset($matches[1]) && $matches[1]){
            $this->setFlood($matches[1], 'sendMessageFlood');
            return false;
          }else{
            $this->getLog()->info('flood 2');
          }
        }

      }
      
    }

    //Log
    $this->getLog()->fail($result);

    return false;

  }

  public function getFullDialogs(){

    $result = $this->_query('getFullDialogs');

    //Check Dialogs exists 
    if(gettype($result) == "object"){
      $this->getLog()->success($result);
      return $result;
    } 

    //Log
    $this->getLog()->fail($result);

    return false;

  }

  public function getHistory($peer, $limit){

    $params = [
      'peer' => $peer, 
      'limit' => $limit, 
      'offset_id' => 0, 
      'offset_date' => 0, 
      'add_offset' => 0, 
      'max_id' => 0, 
      'min_id' => 0
    ];

    $result = $this->_query('messages.getHistory', $params);

    //Success
    if(isset($result->_) && ($result->_ == 'messages.messages' || $result->_ == 'messages.messagesSlice')){
      $this->getLog()->success($result);
      return $result;
    } 
    
    $this->getLog()->fail($result);

    return false;
  }

  public function readHistory($peer){

    $params = [
      'peer' => $peer,
      'max_id' => 0
    ];

    $result = $this->_query('messages.readHistory', $params);

    if(isset($result->_) && $result->_ == 'messages.affectedMessages'){
      $this->getLog()->success($result);
      return $result;
    } 
    
    $this->getLog()->fail($result);

    return false;

  }

  public function forwardMessages($messageIds, $fromPeer, $toPeer){

    $params = [
      'from_peer' => $fromPeer,
      'to_peer' => $toPeer,
      'id' => $messageIds,
    ];

    $result = $this->_query('messages.forwardMessages', $params);

    {//Success
      if(
        isset($result->_) && 
        $result->_ == 'updates' && 
        isset($result->request) && 
        isset($result->request->_) && 
        $result->request->_ == "messages.forwardMessages"
      ){
        $this->getLog()->success($result);
        return true;
      } 
    }

    //Log
    $this->getLog()->fail($result);

    return false;

  }


  
  public function testMessage(){

    $chats = $this->getAllChats();

    $join = true;;
    foreach ($chats as $key => $chat) {
      if($chat->id == 778310890){
        $join = false;
        break;  
      }
    }

    if($join) self::joinChannel('https://t.me/+2bE5sADRxpI5ZWY0');

    dump(self::sendMessage('https://t.me/+2bE5sADRxpI5ZWY0', '👺🦆'));

    // self::leaveChannel($login, 778310890);

  }


  public function metas(){
    return $this->morphMany(Meta::class, 'metable');
  }  

}
