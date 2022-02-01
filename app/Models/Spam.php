<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use App\Models\Madeline;
use App\Models\TAcc;
use App\Models\Meta;
use Carbon\Carbon;

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
      -1 => 'бан ⛱️',
      3 => 'бан ⛱️',
      1 => 'да',
      0 => 'нет',
    ]],
    ['key'    => 'sent_at','label' => 'Last Send At', 'type' => 'moment', 'moment' => 'lll'],
    ['key'    => 'created_at','label' => 'Создан', 'type' => 'moment', 'moment' => 'lll'],
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

  public static function removeWorks(){

    dump('Remove Works');

    $accs = new TAcc;

    $t = now()->add('-60','minutes');
    $accs = $accs->where('work_at', '<', $t);
    $accs = $accs->get();
    
    foreach ($accs as $key => $acc) {

      dump($acc->phone);

      //Get work reset
      $meta = Meta::where([
        'metable_id' => $acc->id, 
        'metable_type' => 'App\Models\TAcc', 
        'name' => 'workReset'
      ])->first();

      dump($meta);

      if($meta !== null){
        $meta->value = $meta->value + 1;
        $meta->save();
      }else{
        //Make new work reset
        $meta = new Meta;
        $meta->metable_id = $acc->id;
        $meta->metable_type = 'App\Models\TAcc';
        $meta->name = 'workReset';
        $meta->value = 1;
        $meta->save();
      }

      $acc->work_at = null;
      $acc->save();
    }

    dd('done');
  }

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

  public static function doSends($recursive = true){

    dump('Do actual');

    $acc = TAcc::getWithActualSpams();

    //Exit if no phone
    if(!isset($acc->phone)){
      dump($acc);
      dump('no phone');
      dump('no actual?');
      exit;
    }

    dump($acc->phone);

    //Exit if no actual
    if(!isset($acc->spams)){
      dump('no actual');
      exit;
    }
    
    {//Set start work
      $acc->work_at = now();
      $acc->save();
    }

    //Madeline
    $madeline = new Madeline($acc->phone);
          
    //Check login
    if(!$madeline->checkLogin()){
      {//Set work done
        $acc->work_at = null;
        $acc->save();
      }
      dump('not login');
      exit;
    }
    
    //Send
    foreach ($acc->spams as $key => $spam) {
      self::send($madeline, $spam);
    }
        
    {//Set work done
      $acc->work_at = null;
      $acc->save();
    }

    dump('actual done');

    if($recursive) Self::doSends();

  }

  public static function doJoins($mass = true, $recursive = true){

    {//Set joins
      if($mass){
        //Set joins
        self::setMassJoins();
      }
    }

    {// Get tAcc with spams
      $accQuery = new TAcc;
      
      $t = now()->add('-5','minutes');
      $accQuery = $accQuery->where(function($q) use ($t){
        $q = $q->whereDoesntHave('metas', function($q1){
          $q1->where('name', 'doJoins');
        });
        $q = $q->orWhereHas('metas', function($q1) use ($t){
          $q1->where('name', 'doJoins')
             ->where('value', '<', $t);
        });
      });
         
      $accQuery = $accQuery->with('metas');

      $accQuery = $accQuery->with(['spams' => function($q){
        $q->whereNull('group_joined_at');
      }]);
      $accQuery = $accQuery->whereHas('spams', function($q){
        $q->whereNull('group_joined_at');
      });
      $accQuery = $accQuery->whereNull('work_at');
  
      $acc = $accQuery->first();
    }

    //Check no joins
    if(!$acc || !isset($acc->id)){
      dump('no joins');
      return false;
    }
    
    TAcc::startWork($acc);

    dump('Start Join ' . $acc->phone);
    
    //Madeline
    $madeline = new Madeline($acc->phone);

    //Set joins
    $meta = Meta::updateOrCreate(
      [
        'metable_id' => $acc->id, 
        'metable_type' => 'App\Models\TAcc', 
        'name' => 'doJoins'
      ],
      ['value' => now()]
    );

    //Join
    $joined = 0;
    foreach ($acc->spams as $key => $spam) {

      if($joined > 1){
        dump('to many joins');
        break;
      }

      dump('Join ' . $acc->phone . " " . $spam->peer);
      $didJoin = $madeline->joinChannel($spam->peer);
      dump($didJoin);
      if($didJoin){
        $spam->group_joined_at = now();
        $spam->save();
        $joined++;
      }

    }
    
    TAcc::stopWork($acc);

    if($recursive) self::doJoins(false);

    return true;

  }

  public static function setJoins($acc){

    $login = $acc->phone;

    dump('Check Joins ' . $login);

    {//Check Resent Joins
      $checkJoins = Meta::where([
          'metable_id' => $acc->id, 
          'metable_type' => 'App\Models\TAcc', 
          'name' => 'checkJoins'
      ]);
      $checkJoins = $checkJoins->first();

      if(isset($checkJoins->value)){
        if(Carbon::parse($checkJoins->value)->add('30','minutes')->diffInMinutes(now()) < 30){
          dump('not now');
          return false;
        }
      }
    }
    
    {//Update Joins
      dump('Update Joins ');
      $meta = Meta::updateOrCreate(
        [
          'metable_id' => $acc->id, 
          'metable_type' => 'App\Models\TAcc', 
          'name' => 'checkJoins'
        ],
        ['value' => now()]
      );
    }
         
    {//Get Spams
      $spams = Spam::where('t_acc_phone', $login)->get();

      {//Check exists
        if(!$spams){
          dump('get spams fail');
          return false;
        }
        if(count($spams) == 0){
          dump('no chats');
          return false;
        }
      }

    }
   
    {//Get Chats
      dump('Get chats ');
      $madeline = new Madeline($login);
      $madelineChats = $madeline->getAllChats();
      
      //Check fails
      if(!is_array($madelineChats)){
        dump($madelineChats);
        dump('get chats fail');
        return false;
      }
    } 
        
    {//Set Joins
      dump('Set Joins');
      foreach ($spams as $kSpamChat => $spam) {
            
        {//Set joins

          //Trim peer
          $peer = str_replace('http://t.me/', '', $spam->peer);
          $peer = str_replace('https://t.me/', '', $peer);

          dump($peer);
  
          {//Check already joined
            dump('Check joined');
            $joined = false;
            foreach ($madelineChats as $kMadelineChat => $madelineChat){
              if( 
                (isset($madelineChat->username)) && 
                ($madelineChat->username == $peer)
              ){
                $joined = true;
                break;
              }
            }
          }
          
          {//Set join
            if($joined){
              dump('Joined ' . $peer . " " . $login);
              $spam->group_joined_at = now();
            }else{
              dump('Not Joined ' . $peer . " " . $login);
              $spam->group_joined_at = NULL;
            }
    
            $spam->save();
          }
        }  
  
      }
    }

    return true;

  }

  public static function setMassJoins(){
    
    {//Accs
      $accQuery = new TAcc;    
      $accQuery = $accQuery->with('metas');
      $accQuery = $accQuery->where('status', 1);
      $accQuery = $accQuery->whereNull('work_at');
      $t = now()->add('-60','minutes');
      $accQuery = $accQuery->where(function($q) use ($t){
        $q = $q->whereDoesntHave('metas', function($q1){
          $q1->where('name', 'checkJoins');
        });
        $q = $q->orWhereHas('metas', function($q1) use ($t){
          $q1->where('name', 'checkJoins')
            ->where('value', '<', $t);
        });
      });
      $fAcc = $accQuery->first();    
    }


    if(!$fAcc){
      dump('No acc to join');
      return false;
    } 

    TAcc::startWork($fAcc);
    self::setJoins($fAcc);
    TAcc::stopWork($fAcc);

    self::setMassJoins();
    
  }

  public static function joinAllChats($madeline, $login){
    
    //Get Chats
    dump('Get chats ' . $login);
    $chats = $madeline->getAllChats();

    if(!$chats){
      dump('join fail');
      return false;
    }

    $spams = Spam::where('status', '1')->where('t_acc_phone', $login)->get();

    $joined = 0;
    foreach ($spams as $key => $spam) {

      dump('Join ' . $spam->id);

      if($joined > 1){
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
          dump('alreay joined');
          break;
        } 
      }     

      //Join
      if($join){
        dump('join - ' . $peer);
        $didJoin = $madeline->joinChannel($spam->peer);
        dump($didJoin);
        if($didJoin) $joined++;        
      }

    }
    
  }

  public static function send($madeline, $spam){

    dump('send ' . $spam->t_acc_phone . " - " . $spam->id);

    dump($madeline->sendMessage($spam->peer, $spam->text));

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
        if($user && $user->id != 1) $query = $query->where('owner_id', $user->id);        
      }

    }

    //Order by
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
