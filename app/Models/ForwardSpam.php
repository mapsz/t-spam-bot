<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Madeline;
use App\Models\Meta;

class ForwardSpam extends Model{

  private $login;
  private $sendTo;
  private $madeline;
  private $dialogs;
  private $userDialogs;
  private $unreadUserDialogs;
  private $checkDialogsAt;
  private $checkDialogsDelay;
  private $checkHistoryDelay;
  private $maxHistoryQueries;
  private $histories;
  private $toForwards;
  
  private function setLogin($v){return $this->login = $v;}
  private function getLogin(){return $this->login;}
  private function getSendTo($v){return $this->sendTo = $v;}
  private function setSendTo(){return $this->sendTo;}
  private function setMadeline($v){return $this->madeline = $v;}
  private function getMadeline(){return $this->madeline;}
  private function setCheckDelay($v){return $this->checkDelay = $v;}
  private function getCheckDelay(){return $this->checkDelay;}
  private function setDialogs($v){return $this->dialogs = $v;}
  private function getDialogs(){return $this->dialogs;}
  private function setUserDialogs($v){return $this->userDialogs = $v;}
  private function getUserDialogs(){return $this->userDialogs;}  
  private function setUnreadUserDialogs($v){return $this->UnreadUserDialogs = $v;}
  private function getUnreadUserDialogs(){return $this->UnreadUserDialogs;}
  private function setCheckHistoryDelay($v){return $this->checkHistoryDelay = $v;}
  private function getCheckHistoryDelay(){return $this->checkHistoryDelay;}
  private function setMaxHistoryQueries($v){return $this->maxHistoryQueries = $v;}
  private function getMaxHistoryQueries(){return $this->maxHistoryQueries;}
  private function setHistories($v){return $this->histories = $v;}
  private function getHistories(){return $this->histories;}
  private function setToForwards($v){return $this->toForwards = $v;}
  private function getToForwards(){return $this->toForwards;}

  public function __construct($login, $sendTo = false) {
    $this->setLogin($login);
    $this->setSendTo($sendTo);
    $this->setMadeline(new Madeline($this->getLogin()));    
    $this->setCheckDelay(config('forward.checkDialogsDelay'));
    $this->setCheckHistoryDelay(config('forward.checkHistoryDelay'));
    $this->setMaxHistoryQueries(config('forward.maxHistoryQueries'));
  }

  public function do(){

    if(!$this->madelineDialogs()){
      exit;
    }
    $this->madelineHistoriesFromUnreadDialogs();
    $this->setToForwardFromHistories();

    $this->doForwads();

  }

  public function doForwads(){

    dump('Do Forwads');

    //Get to Forward
    $toForwards = $this->getToForwards();

    foreach ($toForwards as $peer => $toForward) {
      dump($peer);
      foreach ($toForward as $message) {

        dump($message);      

        {//Check non messages
          if($message['_'] == 'messageService'){        
            dump('skip');
            continue;
          }
          if($message['bot']){        
            dump('skip');
            break;
          }
        }

        //Forward
        $this->getMadeline()->forwardMessages([$message['messageId']], $message['peerId'], 'https://t.me/juge_playground_chat');
      }      
      //Set read
      $this->getMadeline()->readHistory($peer);
    }
    

  }

  public function setToForwardFromHistories(){
    //Get histories
    $histories = $this->getHistories();

    $toForwards = [];
    foreach ($histories as $history) {
      foreach ($history->messages as $message) {
        if(!isset($toForwards[$message->peer_id->user_id])) $toForwards[$message->peer_id->user_id] = [];
        array_push($toForwards[$message->peer_id->user_id],
          [
            'peerId' =>  $message->peer_id->user_id,
            'messageId' =>  $message->id,
            '_' =>  $message->_,
            'bot' =>  $history->users[0]->bot
          ]
        );
      }
    }

    $this->setToForwards($toForwards);
  }

  public function madelineDialogs(){

    //Check delay
    if(Meta::getDelay($this->getLogin(), 'checkDialogs', $this->getCheckDelay())){
      dump('not now');
      return false;
    }
    
    {//Set dialogs
      $dialogs = $this->getMadeline()->getFullDialogs();
      $this->setDialogs($dialogs);  
    }  

    //Set delay
    Meta::setDelay($this->getLogin(), 'checkDialogs');

    //Set user dialogs
    if(!$this->setUsersDialogsFromDialogs()){
      JugeLogs::log(180, 'bad user dialogs');
      return false;
    }

    //Set user dialogs
    if(!$this->setUnreadUserDialogsFromUserDialogs()){
      JugeLogs::log(181, 'bad unread user dialogs');
      return false;
    }

    return true;

  }

  private function madelineHistoriesFromUnreadDialogs(){

    $dialogs = $this->getUnreadUserDialogs();

    {//Get histories
      $queriesDone = 1;
      $histories = [];
      foreach ($dialogs as $key => $dialog) {
        //Check max queries
        if($queriesDone > $this->getMaxHistoryQueries()) break;
        
        //Get history
        $history = $this->getMadeline()->getHistory($dialog->peer->user_id, $dialog->unread_count);

        //Add queries counter
        $queriesDone++;

        //Check history
        if(!$history){
          dump($dialog->peer->user_id);
          continue;
        } 

        //Add history
        array_push($histories, $history);        
      }
    }

    $this->setHistories($histories);


  }
  
  private function setUsersDialogsFromDialogs(){

    $dialogs = $this->getDialogs();

    //To array
    $dialogs = (array) $dialogs;
    if(!is_array($dialogs)) return false;

    //Pick user dealogs
    $usersDialogs = [];
    foreach ($dialogs as $key => $dialog){
      if(isset($dialog->peer) && isset($dialog->peer->_) && $dialog->peer->_ == 'peerUser') array_push($usersDialogs, $dialog);
    }

    //Set user dialogs
    if(is_array($usersDialogs)){
      $this->setUserDialogs($usersDialogs);
      return true;
    } 

    return false;
    
  }

  private function setUnreadUserDialogsFromUserDialogs(){

    $dialogs = $this->getUserDialogs();

    //To array
    $dialogs = (array) $dialogs;
    if(!is_array($dialogs)) return false;

    //Pick user dealogs
    $unreadUserDialogs = [];
    foreach ($dialogs as $key => $dialog){
      if(isset($dialog->unread_count) && $dialog->unread_count > 0) array_push($unreadUserDialogs, $dialog);
    }

    //Set user dialogs
    if(is_array($unreadUserDialogs)){
      $this->setUnreadUserDialogs($unreadUserDialogs);
      return true;
    } 

    return false;
    
  }

}