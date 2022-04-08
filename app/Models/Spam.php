<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use App\Models\Madeline;
use App\Models\TAcc;
use App\Models\Meta;
use App\Models\Work;
use App\Models\ForwardSpam;
use App\Models\Spams;
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
      'type'=>"select",
      'list'=> [/* */],
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


  public static function getActualWork(){
    // 
  }

  public static function pDoJoins(){
    
    {//Get accs with groups to join
      $q = new TAcc;
      $q = $q->where('status', 1);
      $q = $q->whereHas('spams', function($q1){
        $q1->whereNull('group_joined_at');
      });
      $q = $q->with('spams', function($q1){
        $q1->whereNull('group_joined_at');
      });
      $tAccs = $q->get();
    }
    
    {//To join
      $toJoin = [];      
      foreach ($tAccs as $acc) {
        $toJoin[$acc->phone] = [];
        foreach ($acc->spams as $spam) {
          array_push($toJoin[$acc->phone], $spam->peer);
        }
      }
    }

    {//Make works
      
      {//Get existing
        //Get accs
        $tAccs = [];
        foreach ($toJoin as $tAcc => $v){
          array_push($tAccs, $tAcc);
        }

        //get works
        $preWorksExists = Work::with('properties')->where('function', 'join_chat')->whereIn('account', $tAccs)->whereNull('done_at')->get();
        
        {//Handle works
          $worksExists = [];
          foreach ($preWorksExists as $work) {
            $chat_id = false;
            foreach ($work->properties as $key => $property) {
              if($property->name == 'chat_id'){
                $chat_id = $property->value;
                continue;
              }
            }
            if($chat_id) array_push($worksExists, ['account' => $work->account, 'chat_id' => $chat_id]);
          }
        }
      }
      
      {//Make new
        foreach ($toJoin as $tAcc => $peers) {
          foreach ($peers as $peer) {
            foreach ($worksExists as $workExists) {
              if($workExists['account'] == $tAcc && $workExists['chat_id'] == $peer) continue 2;
            }
            dump('Add work join_chat - ' . $tAcc . ' - ' . $peer);
            Work::new($tAcc, 'join_chat', 500, ['chat_id' => $peer]);
          }        
        }
      }

    }
    
    return true;
  }

  public static function pDoSends(){
    
    {//Get spams
      {// Get spams
        $q = new Spam;
        $q = $q->where('status', 1);
        $q = $q->whereNotNull('group_joined_at');
        $q = $q->orderBy('sent_at', 'ASC');
        $q = $q->whereHas('tAcc', function($q1){
          $q1 = $q1->where('status',1);
        });
        $dbSpams = $q->get();
      }
      
      {//Get actuals
        $actualSpams = [];
        $now = now();
        foreach ($dbSpams as $kspam => $spam) {
          $toSendAt = Carbon::parse($spam->sent_at)->add($spam->delay,'minutes');
          if( $toSendAt < $now || $spam->sent_at == NULL ){
            array_push($actualSpams, $spam);
          }
        }
      }

      $spams = $actualSpams;
    }
    
    {//Get works
      $tAccs = [];
      foreach($dbSpams->unique('t_acc_phone') as $spam) array_push($tAccs, $spam->t_acc_phone);
  
      $q = new Work;
      $q = $q->with('properties'); 
      $q = $q->where('function', 'send_message'); 
      $q = $q->where(function($q1){
          $q1->where('status', 1) 
          ->orWhere('status', 2);
      });
      $q = $q->whereIn('account', $tAccs);
  
      $preWorksExists = $q->get();
    }

    {//Handle works
      $worksExists = [];
      foreach ($preWorksExists as $work) {
        $chat_id = false;
        foreach ($work->properties as $key => $property) {
          if($property->name == 'spam_id'){
            $spam_id = $property->value;
            continue;
          }
        }
        if($spam_id) array_push($worksExists, ['account' => $work->account, 'spam_id' => $spam_id]);
      }
    }    
              
    {//Make new
      $add = 0;
      foreach ($spams as $spam) {
        foreach ($worksExists as $workExists) {
          if(intval($spam->id) == intval($workExists['spam_id'])) continue 2;
        }
        dump('Add work send_message - ' . $spam->t_acc_phone . ' - ' . $spam->peer);
        Work::new($spam->t_acc_phone, 'send_message', 1000, ['chat_id' => $spam->peer, 'text' => $spam->text, 'spam_id' => $spam->id]); 
        $add++; 
      }  
    }

    return $add;
  }

  public static function setSend($id){
    return Spam::where('id', $id)->update(['sent_at' => now()]);
  }


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

  public static function doForwards(){
    
    dump('Do Forwards');
    
    {//Get acc
      $accQuery = new TAcc;    
      $accQuery = $accQuery->with('metas');
      $accQuery = $accQuery->with('forward');
      $accQuery = $accQuery->where('status', 1);
      $accQuery = $accQuery->whereNull('work_at');
      $accQuery = $accQuery->whereHas('forward', function($q){
        $q->where('status', 1);
      });
      $t = now()->add(-(config('forward.checkDialogsDelay')),'seconds');
      $accQuery = $accQuery->where(function($q) use ($t){
        $q = $q->whereDoesntHave('metas', function($q1){
          $q1->where('name', 'checkDialogs');
        });
        $q = $q->orWhereHas('metas', function($q1) use ($t){
          $q1->where('name', 'checkDialogs')
            ->where('value', '<', $t);
        });
      });


      $acc = $accQuery->first();
    }

    if(!$acc && !isset($acc->forward->acc)){
      dump('not now 1');
      return false;
    }

    {//Set start work
      $acc->work_at = now();
      $acc->save();
    }

    $fs = new ForwardSpam($acc->forward->acc, $acc->forward->to_peer);

        
    {//Set work done
      $acc->work_at = null;
      $acc->save();
    }

    return $fs->do();

  }

  public static function doSends($recursive = true){

    dump('Do Sends');

    $acc = TAcc::getWithActualSpams();

    //Exit if no phone
    if(!isset($acc->phone)){
      dump($acc);
      dump('no phone');
      dump('no actual?');
      return false;
    }

    dump($acc->phone);

    //Exit if no actual
    if(!isset($acc->spams)){
      dump('no actual');
      return false;
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
      return false;
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

    // if($recursive) Self::doSends();

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
      if($input['name'] == 't_acc_phone'){
        $accs = TAcc::getByCurrentUser();
        foreach ($accs as $key => $v) {
          array_push($input['list'], ['id'=>$v,    'name'=>$v]);
        }
      }
      array_push($fInputs, $input);
    }
    return $fInputs;
  }
  public function jugeGetPostInputs()   {
    $inputs = $this->inputs;
    $fInputs = [];
    foreach ($inputs as $key => $input) {
      if($input['name'] == 't_acc_phone') continue;
      array_push($fInputs, $input);
    }
    return $fInputs;
  }
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

  //Relations
  public function tAcc(){
    return $this->belongsTo('App\Models\tAcc', 't_acc_phone', 'phone');
  }   

}
