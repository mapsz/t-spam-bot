<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JugeCRUD extends Model
{
  use HasFactory;

  public $table = 'juge_cruds';
  
  public static function get($query,$request = []){
    //Pagginate limit
    if(isset($request['limit']) && $request['limit']){
      $limit = $request['limit'];
    }else{
      $limit = 100;
    }

    //Get
    if(!isset($request['page'])){
      $data = $query->get();
    }else{
      $data = $query->paginate($limit);
    }  

    if(isset($request['test'])){
      dd($data->toArray());
      dd($data);
    }


    return $data;
  }

  public static function setMetas($data){
    foreach ($data as $row) {
      //Metas
      if(isset($row['metas'])){
        foreach ($row['metas'] as $meta) {
          $row[$meta->name] = $meta->value;
        }
        unset($row['metas']);
      }
      //Long Metas
      if(isset($row['longMetas'])){
        foreach ($row['longMetas'] as $row) {
          $row[$meta->name] = $meta->value;
        }
        unset($row['longMetas']);
      }
    }
    return $data;
  }


}
