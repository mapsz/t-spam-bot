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

  // private $debug = false;
  private $debug = true;
  private $login;
  private $loginInfo;
  private $cryptKey = 'pSUmlYgwbfAu57cH@yH4Ky9z6KHC9OJa';
  private $error = false;

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
  
  public function __construct($login) {
    $this->setLogin($login);
  }
 
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
  
  public function decrypt(string $encrypted): string{
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
      JugeLogs::log(11, 'ban set ' . $this->getLogin());
      return true;
    } 
  }

  public function getSelf(){
    $result = $this->_query('getSelf');
    if(isset($result->_) && $result->_ == 'user'){
      $this->setLoginInfo($result);
      return $result;
    } 

    {//Catch errors
      if($this->checkBan($result)) return false;
    }

    JugeLogs::log(143, $result);
    
    return false;
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

  public function login(){

    if($this->checkLogin()) return 'already log in';
    
    $result = $this->_query('phoneLogin');
    
    if(gettype($result) == 'string' && strpos($result, "FLOOD_WAIT_X (420)") !== false){
      return 'flood';
    }

    //Catch errors
    if(!isset($result->_)){
      JugeLogs::log(101, $result);
      return false;
    }

    if($result->_ == 'auth.sentCode'){
      return 'need code';
    }
    
    JugeLogs::log(102, $result);
    return false;

  }

  public function resetAuthorization($hash){
    
    return $this->_query('account.resetAuthorization', ['hash' => $hash]);
    

  }

  public function loginSendCode($code, $relog = false){

    if($this->checkLogin()) return 'already log in';

    $result = $this->_query('completePhoneLogin', ['code' => $code]);

    //Catch errors
    if(!isset($result) || !$result){
      JugeLogs::log(103, $result);
      return false;
    }

    if(gettype($result) == 'string' && strpos($result, "I'm not waiting for the code!") !== false){

      //Try relog
      if(!$relog){
        $login = $this->login();
        if($login == 'need code'){
          return $this->loginSendCode($code, true);
        }
      }
      


      return "I'm not waiting for the code!";
    }

    if(gettype($result) == 'string' && strpos($result, "The provided phone code is invalid") !== false){
      return "bad code";
    }


    if(isset($result->_) && $result->_ == "auth.authorization" && isset($result->user)){
      return true;
    }

    JugeLogs::log(104, $result);
    return false;

  }

  public static function loginSendCodeAndTest($login, $code){

    self::loginSendCode($login, $code);
    self::testMessage($login);


  }

  public function joinChannel($channel){
    $result = $this->_query('channels.joinChannel', ['channel' => $channel]);

    
    {//Success

      if(gettype($result) == 'string' && strpos($result, "Telegram returned an RPC error: The user is already in the group (400) (USER_ALREADY_PARTICIPANT),") !== false) return true;
    
      if(isset($result->chats) && isset($result->chats[0]) && isset($result->_) && $result->_ == 'updates') return true;

    }
    
    {//Catch errors

      //Check ban
      if($this->checkBan($result)) return false;

      //Bad peer
      if(gettype($result) == 'string' && strpos($result, "The provided peer id is invalid") !== false){
        $this->setError('The provided peer id is invalid');
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
          return false;
        }
      }
    }
    
    //Log
    JugeLogs::log(116, $result);
    
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
      JugeLogs::log(117, $chats);
      return false;
    } 

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
        ){return true;}
      }
    }

    {//Catch errors

      //Check ban
      if($this->checkBan($result)) return false;

      //Peer ban
      if(
        gettype($result) == 'string' && 
        (
          strpos($result, "You can't write in this chat (403)") !== false ||
          strpos($result, "You are spamreported, you can't do this (400)") !== false
        )        
      ){

        $spam = Spam::where('t_acc_phone', $this->getLogin())->where('peer', $peer)->update(
          [
            'status' => -1,
            'group_joined_at' => NULL,
          ]
        );

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
            JugeLogs::log(188, 'flood no flood');
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
            JugeLogs::log(188, 'flood no flood2');
          }
        }

      }
      
    }


    //Log
    JugeLogs::log(120, $result);

    return false;

  }

  public function getFullDialogs(){

    $result = $this->_query('getFullDialogs');

    //Check Dialogs exists 
    if(gettype($result) == "object") return $result;

    //Log
    JugeLogs::log(128, $result);

    return false;

  }

  public static function getUsersDialogs($login){
    // $fullDialogs = json_decode('[{"_":"dialog","pinned":false,"unread_mark":false,"peer":{"_":"peerChannel","channel_id":1507634215},"top_message":9,"read_inbox_max_id":9,"read_outbox_max_id":0,"unread_count":0,"unread_mentions_count":0,"notify_settings":{"_":"peerNotifySettings","show_previews":false,"silent":false},"pts":10},{"_":"dialog","pinned":false,"unread_mark":false,"peer":{"_":"peerUser","user_id":724321708},"top_message":18,"read_inbox_max_id":0,"read_outbox_max_id":18,"unread_count":0,"unread_mentions_count":0,"notify_settings":{"_":"peerNotifySettings","show_previews":false,"silent":false}},{"_":"dialog","pinned":false,"unread_mark":false,"peer":{"_":"peerUser","user_id":777000},"top_message":22,"read_inbox_max_id":22,"read_outbox_max_id":0,"unread_count":0,"unread_mentions_count":0,"notify_settings":{"_":"peerNotifySettings","show_previews":false,"silent":false},"draft":{"_":"draftMessageEmpty","date":1641468954}},{"_":"dialog","pinned":false,"unread_mark":false,"peer":{"_":"peerUser","user_id":517183883},"top_message":27,"read_inbox_max_id":27,"read_outbox_max_id":17,"unread_count":0,"unread_mentions_count":0,"notify_settings":{"_":"peerNotifySettings","show_previews":false,"silent":false}},{"_":"dialog","pinned":false,"unread_mark":false,"peer":{"_":"peerUser","user_id":5039444477},"top_message":32,"read_inbox_max_id":28,"read_outbox_max_id":0,"unread_count":0,"unread_mentions_count":0,"notify_settings":{"_":"peerNotifySettings","show_previews":false,"silent":false}},{"_":"dialog","pinned":false,"unread_mark":false,"peer":{"_":"peerUser","user_id":2041710625},"top_message":39,"read_inbox_max_id":37,"read_outbox_max_id":35,"unread_count":0,"unread_mentions_count":0,"notify_settings":{"_":"peerNotifySettings","show_previews":false,"silent":false}},{"_":"dialog","pinned":false,"unread_mark":false,"peer":{"_":"peerUser","user_id":93774339},"top_message":43,"read_inbox_max_id":43,"read_outbox_max_id":0,"unread_count":0,"unread_mentions_count":0,"notify_settings":{"_":"peerNotifySettings","show_previews":false,"silent":false}},{"_":"dialog","pinned":false,"unread_mark":false,"peer":{"_":"peerUser","user_id":876259902},"top_message":44,"read_inbox_max_id":41,"read_outbox_max_id":44,"unread_count":0,"unread_mentions_count":0,"notify_settings":{"_":"peerNotifySettings","show_previews":false,"silent":false}},{"_":"dialog","pinned":false,"unread_mark":false,"peer":{"_":"peerUser","user_id":1848940623},"top_message":53,"read_inbox_max_id":52,"read_outbox_max_id":53,"unread_count":0,"unread_mentions_count":0,"notify_settings":{"_":"peerNotifySettings","show_previews":false,"silent":false}},{"_":"dialog","pinned":false,"unread_mark":false,"peer":{"_":"peerChannel","channel_id":1684476125},"top_message":87,"read_inbox_max_id":87,"read_outbox_max_id":77,"unread_count":0,"unread_mentions_count":0,"notify_settings":{"_":"peerNotifySettings","show_previews":false,"silent":false},"pts":88},{"_":"dialog","pinned":false,"unread_mark":false,"peer":{"_":"peerChannel","channel_id":1169265319},"top_message":9355,"read_inbox_max_id":9351,"read_outbox_max_id":9355,"unread_count":4,"unread_mentions_count":0,"notify_settings":{"_":"peerNotifySettings","show_previews":false,"silent":false,"mute_until":2147483647},"pts":13627},{"_":"dialog","pinned":false,"unread_mark":false,"peer":{"_":"peerChannel","channel_id":1799678813},"top_message":25295,"read_inbox_max_id":25214,"read_outbox_max_id":25295,"unread_count":55,"unread_mentions_count":0,"notify_settings":{"_":"peerNotifySettings","show_previews":false,"silent":false,"mute_until":2147483647},"pts":29827},{"_":"dialog","pinned":false,"unread_mark":false,"peer":{"_":"peerChannel","channel_id":1140384370},"top_message":43789,"read_inbox_max_id":43762,"read_outbox_max_id":43789,"unread_count":24,"unread_mentions_count":0,"notify_settings":{"_":"peerNotifySettings","show_previews":false,"silent":false,"mute_until":2147483647},"pts":50625},{"_":"dialog","pinned":false,"unread_mark":false,"peer":{"_":"peerChannel","channel_id":1547872110},"top_message":9530,"read_inbox_max_id":9504,"read_outbox_max_id":9523,"unread_count":21,"unread_mentions_count":0,"notify_settings":{"_":"peerNotifySettings","show_previews":false,"silent":false,"mute_until":2147483647},"pts":11103},{"_":"dialog","pinned":false,"unread_mark":false,"peer":{"_":"peerChannel","channel_id":1567977272},"top_message":35942,"read_inbox_max_id":35894,"read_outbox_max_id":35942,"unread_count":48,"unread_mentions_count":0,"notify_settings":{"_":"peerNotifySettings","show_previews":false,"silent":false,"mute_until":2147483647},"pts":37344},{"_":"dialog","pinned":false,"unread_mark":false,"peer":{"_":"peerChannel","channel_id":1169378196},"top_message":196441,"read_inbox_max_id":196335,"read_outbox_max_id":196441,"unread_count":105,"unread_mentions_count":0,"notify_settings":{"_":"peerNotifySettings","show_previews":false,"silent":false,"mute_until":2147483647},"pts":221474},{"_":"dialog","pinned":false,"unread_mark":false,"peer":{"_":"peerChannel","channel_id":1333135242},"top_message":68459,"read_inbox_max_id":68379,"read_outbox_max_id":68459,"unread_count":61,"unread_mentions_count":0,"notify_settings":{"_":"peerNotifySettings","show_previews":false,"silent":false,"mute_until":2147483647},"pts":80655},{"_":"dialog","pinned":false,"unread_mark":false,"peer":{"_":"peerChannel","channel_id":1549630026},"top_message":22361,"read_inbox_max_id":22165,"read_outbox_max_id":22359,"unread_count":190,"unread_mentions_count":0,"notify_settings":{"_":"peerNotifySettings","show_previews":false,"silent":false,"mute_until":2147483647},"pts":28054},{"_":"dialog","pinned":false,"unread_mark":false,"peer":{"_":"peerChannel","channel_id":1763516356},"top_message":26344,"read_inbox_max_id":26187,"read_outbox_max_id":26344,"unread_count":53,"unread_mentions_count":0,"notify_settings":{"_":"peerNotifySettings","show_previews":false,"silent":false,"mute_until":2147483647},"pts":34504},{"_":"dialog","pinned":false,"unread_mark":false,"peer":{"_":"peerChannel","channel_id":1326094773},"top_message":51751,"read_inbox_max_id":51653,"read_outbox_max_id":51751,"unread_count":85,"unread_mentions_count":0,"notify_settings":{"_":"peerNotifySettings","show_previews":false,"silent":false,"mute_until":2147483647},"pts":67838},{"_":"dialog","pinned":false,"unread_mark":false,"peer":{"_":"peerChannel","channel_id":1183328887},"top_message":52304,"read_inbox_max_id":52209,"read_outbox_max_id":52304,"unread_count":80,"unread_mentions_count":0,"notify_settings":{"_":"peerNotifySettings","show_previews":false,"silent":false,"mute_until":2147483647},"pts":61026}]');
    
    //Get full dialogs
    $fullDialogs = self::getFullDialogs($login);

    //Filter user dialogs
    $usersDialogs = [];
    foreach ($fullDialogs as $key => $dialog) {
      if($dialog['peer']->_ == 'peerUser') array_push($usersDialogs, $dialog);
    }

    return $usersDialogs;
  
  }

  public static function getUnreadUsers($login){
    
    $usersDialogs = self::getUsersDialogs($login);

    //Filter unread dialogs
    $unreadDialogs = [];
    foreach ($usersDialogs as $key => $dialog) {
      if($dialog['unread_count'] > 0) array_push($unreadDialogs, $dialog);
    }

    return $unreadDialogs;

  }

  public static function getHistory($login, $peer, $limit){

    // 'peer' => 517183883,

    $params = [
      'work' => 'getHistory',
      'login' => $login,
      'peer' => $peer,
      'limit' => $limit
    ];

    $request = Http::get(self::getUrl(), $params);
    $response = (string) $request->getBody();

    $dialogs = self::resultDecode($response);

    return json_decode($dialogs)->messages;
  }

  public static function readHistory($login, $peer){

    // 'peer' => 517183883,

    $params = [
      'work' => 'readHistory',
      'login' => $login,
      'peer' => $peer,      
    ];

    $request = Http::get(self::getUrl(), $params);
    $response = (string) $request->getBody();

    $history = self::resultDecode($response);

    return json_decode($history);

  }

  public static function forwardMessages($login, $messageIds, $fromPeer, $toPeer){

    $params = [
      'work' => 'forwardMessages',
      'login' => $login,
      'from_peer' => $fromPeer,
      'to_peer' => $toPeer,
      'id' => $messageIds,
    ];

    $request = Http::get(self::getUrl(), $params);
    $response = (string) $request->getBody();

    $forward = self::resultDecode($response);

    $dForward = json_decode($forward);
    if($dForward && $dForward->updates && is_array($dForward->updates) && isset($dForward->updates[1])){
      return true;
    }

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

  public static function test($params){

    $params['work'] = 'test';

    $request = Http::get(self::getUrl(), $params);
    $response = (string) $request->getBody();

    dd($response);

  }
  
  public static function resultDecode($response){
    
    $response = \str_replace("\n","",$response);

    {//Decode response
      $matches = [];
      preg_match(
        "~{\"result\":.,\"text\":`(.*)`}~",
        $response,
        $matches
      );

      if(isset($matches[1])){        
        //Try json decode
        if(json_decode($matches[1])) return json_decode($matches[1]);

        //Just return
        return $matches[1];
      } 
    }
    
    return false;
  }

  public function metas(){
    return $this->morphMany(Meta::class, 'metable');
  }  

}
