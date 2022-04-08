<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use App\Models\WorkProperty;

class Work extends Model
{
  use HasFactory;

  public static function updateActuals(){
    //
  }

  public static function getActualWorks(){
    return Work::jugeGet(
      [
        'status' => 1,
        'noSentAt' => 1,
      ]
    );
  }

  public static function new($account, $function, $priority = 1000, $properties = false){
    
    try{
      //Start DB
      DB::beginTransaction();

      //Work
      $work = new Work;
      $work->account    = $account;
      $work->function   = $function;
      $work->priority   = $priority;
      $work->save();

      //Properties
      if($properties){
        foreach ($properties as $key => $value) {
          
          $prop = new WorkProperty;
          $prop->work_id = $work->id;
          $prop->name = $key;
          $prop->value = $value;
          $prop->save();
        }
      }

      //Store to DB
      DB::commit();
    } catch (Exception $e){
      // Rollback from DB
      DB::rollback();
      return false;
    }

    return true;
  }

  public static function jugeGet($request = []) {
    //Model
    $query = new self;
  
    {//With
      $query = $query->with('properties');
    }
  
    {//Where
      $query = JugeCRUD::whereSearches($query,$request);

      //Status
      if(isset($request['status'])){
        $query = $query->where('status', $request['status']);
      }

      if(isset($request['noSentAt'])){
        $query = $query->whereNull('sent_at');
      }

    }
  
    //Order by
    $query = $query->OrderBy('priority', 'DESC');
  
    //Get
    $data = JugeCRUD::get($query,$request);
  
    //Single
    if(isset($request['id']) && isset($data[0])){$data = $data[0];}
  
    //Return
    return $data;
  }

  //Relations
  public function properties(){
    return $this->hasMany('App\Models\WorkProperty');
  }   

}
