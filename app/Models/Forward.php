<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Madeline;
use App\Models\Meta;

// $a = new App\Models\Forward('+37128885282'); $a->madelineDialogs();

class Forward extends Model{

  protected $keys = [
    ['key'    => 'id','label' => 'ID'],
    ['key'    => 'acc','label' => 'Аккаунт'],
    ['key'    => 'to_peer','label' => 'Получатель'],
    ['key'    => 'status', 'label' => 'Активена','type' => 'intToStr', 'intToStr' =>[
      1 => 'да',
      0 => 'нет',
    ]],
    ['key'    => 'created_at','label' => 'Создан', 'type' => 'moment', 'moment' => 'lll'],
  ];

  protected $inputs = [
    [
      'name' => 'status',
      'caption' => 'Активен',
      'type' => 'checkbox'
    ],
    [
      'name' => 'acc',
      'caption' => 'Аккаунт',
      'type'=>"select",
      'list'=> [/* */],
    ],
    [
      'name' => 'to_peer',
      'caption' => 'Получатель',
      'type' => 'text'
    ],
  ];


  

  //JugeCRUD  
  public function jugeGetInputs(){
    $inputs = $this->inputs;
    $fInputs = [];
    foreach ($inputs as $key => $input) {
      if($input['name'] == 'status') continue;
      if($input['name'] == 'acc'){
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
      if($input['name'] == 'acc') continue;
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

}
