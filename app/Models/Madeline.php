<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class Madeline extends Model
{
  use HasFactory;

  public static function getUrl(){
    return getenv('APP_URL') . 'public/madeline/';;
  }

  public static function login($login){
    $url = self::getUrl();
    $params = [
      'work' => 'login', 
      'login' => $login
    ];

    $request = Http::get($url, $params);
    $response = (string) $request->getBody();

    $result = self::resultDecode($response);

    if($result == 'need code'){
      return 'need code';
    }

    return $response;
  }

  public static function loginSendCode($login, $code){

    $url = getenv('APP_URL') . 'public/madeline/';
    $params = [
      'work' => 'login', 
      'login' => $login,
      'type' => 'code',
      'code' => $code,
    ];

    $request = Http::get($url, $params);
    $response = (string) $request->getBody();

    dump($response);

  }

  public static function loginSendCodeAndTest($login, $code){

    self::loginSendCode($login, $code);
    self::testMessage($login);


  }

  public static function joinChannel($login, $channel){
 
    $params = [
      'work' => 'joinChannel', 
      'login' => $login,
      'channel' => $channel
    ];


    $request = Http::get(self::getUrl(), $params);
    $response = (string) $request->getBody();

    return $response;


  }
  
  public static function leaveChannel($login, $channel){
 
    $params = [
      'work' => 'leaveChannel', 
      'login' => $login,
      'channel' => $channel
    ];

    $request = Http::get(self::getUrl(), $params);
    $response = (string) $request->getBody();

    return $response;
  }

  public static function getAllChats($login){

    //Get chats
    $request = Http::get(self::getUrl(),[
      'work' => 'getAllChats',
      'login' => $login,
    ]);
    $response = (string) $request->getBody();  
    $chats = json_decode(self::resultDecode($response));

    //Check chats exists 
    if(
      !$chats || 
      !isset($chats->_) || 
      $chats->_ != "messages.chats" || 
      !isset($chats->chats) || 
      !is_array($chats->chats) || 
      !isset($chats->chats[0])
    ) return [];


    return $chats->chats;
  }

  public static function sendMessage($login, $peer, $text){
 
    $params = [
      'work' => 'sendMessage', 
      'login' => $login,
      'peer' => $peer,
      'message' => $text
    ];


    $request = Http::get(self::getUrl(), $params);
    $response = (string) $request->getBody();

    dump($response);

  }

  public static function getFullDialogs($login){

    $params = [
      'work' => 'getFullDialogs',
      'login' => $login,
    ];

    $request = Http::get(self::getUrl(), $params);
    $response = (string) $request->getBody();

    $dialogs = json_decode(self::resultDecode($response));


    // //Check Dialogs exists 
    if(
      !$dialogs || 
      gettype($dialogs) != "object"
    ) return [];

    $outDialogs = [];
    foreach ($dialogs as $key => $dialog) {
      array_push($outDialogs,(array)$dialog);
    }

    return $outDialogs;

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

  public static function testMessage($login){

    
    $params = [
      'work' => 'testMessage',
      'login' => $login, 
    ];

    $request = Http::get(self::getUrl(), $params);
    $response = (string) $request->getBody();

    $history = self::resultDecode($response);

    // return json_decode($history);

    $chats = self::getAllChats($login);

    $join = true;;
    foreach ($chats as $key => $chat) {
      if($chat->id == 778310890){
        $join = false;
        break;  
      }
    }

    // dd($chats);

    if($join) self::joinChannel($login, 'https://t.me/+2bE5sADRxpI5ZWY0');

    dump(self::sendMessage($login, 'https://t.me/+2bE5sADRxpI5ZWY0', '👺🦆'));

    self::leaveChannel($login, 778310890);

  }

  public static function test($params){

    $params['work'] = 'test';

    $request = Http::get(self::getUrl(), $params);
    $response = (string) $request->getBody();

    dd($response);

  }
  
  public static function resultDecode($response){

    {//Decode response
      $matches = [];
      preg_match(
        "~{\"result\":1,\"text\":`(.*)`}~",
        $response,
        $matches
      );
      if(isset($matches[1])){
        return $matches[1];
        // $result = json_decode($matches[0]);
        // dump($result);
        // $result = isset($result->text) ? $result->text : false;
      } 
    }
    
    return false;
  }
}
