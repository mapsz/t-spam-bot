<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

use App\Models\JugeFileUpload;

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

  public static function getImages(){
    //
  }

  public static function whereSearches($query,$request){
    if(isset($request['id'])) $query = $query->where('id', $request['id']);
    return $query;
  }

  public static function autoPut($model, $data){

    //Get inputs
    $modelInputs = $model->jugeGetInputs();
    
    {//Set Data Type
      $files = [];
      $db = [];
      foreach ($data as $k => $v) {
  
        //Id
        if($k == 'id') continue;
  
        //Files
        foreach ($modelInputs as $input) {
          if($input['name'] == $k){
            if($input['type'] == 'file'){
              $files[$k] = $v;
              continue 2;
            }
          }
        }
  
        //DB
        $db[$k] = $v;
      }
    }   

    {//Setup model to put
      foreach ($db as $k => $v) {
        $model->$k = $v;
      }
    }   

    try{
      //Start DB
      DB::beginTransaction();
      
      //Save DB
      $save = $model->save();

      
      {//Save Files

        $id = $model->id;

        {//get class name
          $arr = explode('\\',get_class($model));
          $modelName = strtolower ($arr[count($arr)-1]);
        }


               

        //Save
        foreach ($files as $k => $fs) {
          //Make path
          $path = public_path() . '/' . $modelName . '/' . $k . '/';

          
          foreach ($fs as $file) {
            //Make name
            $fileName = JugeFileUpload::generateFileName($path, $id);

            //Save
            if(!JugeFileUpload::saveFile($file, $path.$fileName)) return false;
          }
          
        }
      }      
      
      //Store to DB
      DB::commit();
    } catch (Exception $e){
      // Rollback from DB
      DB::rollback();
    }

    if($save) return $model->jugeGet(['id' => $model->id]);

    return false;
  }

  public static function autoPost($model, $data){

    //Get
    $model = $model->where('id', $data['id'])->first();

    //Edit
    foreach ($data as $k => $v) {
      if($k == 'id') continue;
      $model->$k = $v;
    }

    //Save
    $save = $model->save();
    if($save) return $model->jugeGet(['id' => $data['id']]);

    return false;
  }

  public static function autoDelete($model, $id){
    return ($model::find($id))->delete();
  }

}
