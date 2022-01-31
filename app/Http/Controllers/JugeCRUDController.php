<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\JugeCRUD;

class JugeCRUDController extends Controller
{
  public function get(Request $request){
    if(!isset($request->model)) return false;

    //Set model
    $modelName = "App\Models\\".ucfirst($request->model);
    $model = new $modelName;

    //Set request
    $request = $request->all();
    unset($request['model']);

    //Get data
    $data = $model->jugeGet($request);

    return response()->json($data);
  }

  public function getKeys(Request $request){
    //No model
    if(!isset($request['model']) && $request['model'] == ''){
      return response(['code' => 'lc1','text' => 'no model name'], 512)->header('Content-Type', 'text/plain');
    }
    
    //Get params
    $modelName = $request['model'];
    $userId = Auth::user()->id;

    //Set model
    $model = "App\Models\\".ucfirst($request->model);
    $model = new $model;

    //Get model keys
    $modelKeys = [];
    if(method_exists ( $model , 'jugeGetKeys' )){
      $modelKeys = $model->jugeGetKeys();
    }
    
    //Get user keys
    $userKeys = JugeCRUD::where('model',$modelName)->where('user_id',$userId)->orderBy('position')->get()->toArray();
       
    //No keys
    if(count($modelKeys) == 0){
      $data = $model->jugeGet([]);
      if(gettype($data[0]) == "object"){
        $arr = $data[0]->toArray();
      }else{
        $arr = $data[0];
      }
      $arrKeys = [];
      foreach (array_keys($arr) as $key) {
        array_push($arrKeys, ['key' => $key]);
      }
      $modelKeys = $arrKeys;
    }

    foreach ($modelKeys as $k => $m) {
      $modelKeys[$k]['active'] = true;
      $modelKeys[$k]['position'] = null;
      if(!isset($m['sortable'])) $modelKeys[$k]['sortable'] = true;
    }

    //No settings
    if(count($userKeys) < 1){
      foreach ($modelKeys as $k => $m) {
        $modelKeys[$k]['active'] = true;
      }
      return response()->json($modelKeys);
    }

    //Set user settings
    foreach ($modelKeys as $k => $m) {
      foreach ($userKeys as $u) {
        if($m['key'] == $u['name']){
          $modelKeys[$k]['active'] = $u['active'] ? true : false;
          $modelKeys[$k]['position'] = $u['position'];
        }
      }
    }

    //Sort by position
    usort($modelKeys, function($a, $b) {
      return $a['position'] <=> $b['position'];
    });

    return response()->json($modelKeys);
  }

  public function getInputs(Request $request){
    //No model
    if(!isset($request['model']) && $request['model'] == ''){
      return response(['code' => 'jugei1','text' => 'no model name'], 512)->header('Content-Type', 'text/plain');
    }    

    //Get params
    $modelName = $request['model'];

    //Set model
    $model = "App\Models\\".ucfirst($request->model);
    $model = new $model;

    //Get model inputs
    $modelInputs = [];
    if(method_exists ( $model , 'jugeGetInputs' )){
      $modelInputs = $model->jugeGetInputs();
    }       
    
    return response()->json($modelInputs);

  }

  public function getPostInputs(Request $request){
    //No model
    if(!isset($request['model']) && $request['model'] == ''){
      return response(['code' => 'jugepi1','text' => 'no model name'], 512)->header('Content-Type', 'text/plain');
    }    

    //Get params
    $modelName = $request['model'];

    //Set model
    $model = "App\Models\\".ucfirst($request->model);
    $model = new $model;

    //Get model inputs
    $modelInputs = [];
    if(method_exists ( $model , 'jugeGetPostInputs' )){
      $modelInputs = $model->jugeGetPostInputs();
    }elseif(method_exists ( $model , 'jugeGetInputs' )){
      $modelInputs = $model->jugeGetInputs();
    }
    
    return response()->json($modelInputs);
  }

  public function put(Request $request){

    {// Check model exists
      //No model
      if(!isset($request['model']) && $request['model'] == ''){
        return response(['code' => 'jugep1','text' => 'no model name'], 512)->header('Content-Type', 'text/plain');
      }
    }

    {//Get model
      $modelName = $request['model'];
      $model = "App\Models\\".ucfirst($modelName);
      $model = new $model;
    }

    //Get data
    $data = $request->data;

    {//Pre Validate Edits
      if(method_exists ( $model , 'jugePutPreValidateEdits' )){
        $data = $model->jugePutPreValidateEdits($data);
      } 
    }

    {//Validate
      if(method_exists ( $model , 'jugePutValidate' )){
        $validate = $model->jugePutValidate($data);
      } 
    }
    
    {//Put
      $post = false;
      if(method_exists ( $model , 'jugePut' )){
        $post = $model->jugePut($data);
      }else{
        $post = JugeCRUD::autoPut($model, $data);
      }  
    }     
    
    return response()->json($post);
  }

  public function post(Request $request){
    
    {// Check model/id exists
      //No model
      if(!isset($request['model']) && $request['model'] == ''){
        return response(['code' => 'jugep1','text' => 'no model name'], 512)->header('Content-Type', 'text/plain');
      }    

      //No id
      if(!isset($request['data']['id']) && $request['data']['id'] == '' && $request['data']['id'] == false){
        return response(['code' => 'jugep2','text' => 'no id'], 512)->header('Content-Type', 'text/plain');
      } 
    }
    
    {//Get model
      $modelName = $request['model'];
      $model = "App\Models\\".ucfirst($modelName);
      $model = new $model;
    }

    //Get data
    $data = $request->data;

    {//Pre Validate Edits
      if(method_exists ( $model , 'jugePostPreValidateEdits' )){
        $data = $model->jugePostPreValidateEdits($data);
      } 
    }

    {//Validate
      if(method_exists ( $model , 'jugePostValidate' )){
        $validate = $model->jugePostValidate($data);
      } 
    }
    
    {//Post
      $post = false;
      if(method_exists ( $model , 'jugePost' )){
        $post = $model->jugePost($data);
      }else{
        $post = JugeCRUD::autoPost($model, $data);
      }  
    }     
    
    return response()->json($post);
  }

  public function delete(Request $request){
    //No model
    if(!isset($request['model']) && $request['model'] == ''){
      return response(['code' => 'jugep1','text' => 'no model name'], 512)->header('Content-Type', 'text/plain');
    }

    //No id
    if(!isset($request['id']) && $request['id'] == '' && $request['id'] == false){
      return response(['code' => 'jugep2','text' => 'no id'], 512)->header('Content-Type', 'text/plain');
    } 

    //Get mode
    $modelName = $request['model'];
    $model = "App\Models\\".ucfirst($modelName);
    $model = new $model;

    //Get model inputs
    $delete = false;
    if(method_exists ( $model , 'jugeDelete' )){
      $delete = $model->jugeDelete($request['id']);
    }else{
      $delete = JugeCRUD::autoDelete($model, $request['id']);
    }  
    
    return response()->json($delete);

  }

  public function postConfig(Request $request){

    $listConfig = new JugeCRUD;

    $model = $request['model'];
    $keys = $request['keys'];
    $userId = Auth::user()->id;

    try {
      $ListConfig = new JugeCRUD;
      DB::beginTransaction();

      //Delete all keys
      foreach ($keys as $key) {
        $ListConfig::where('model',$model)->where('user_id',$userId)->delete();
      }

      //Save active keys
      foreach ($keys as $key) {
        if(isset($key['active'])){
          $add = new JugeCRUD;
          $add->model = $model;
          $add->name = $key['key'];
          $add->position = $key['position'];
          $add->user_id = $userId;
          $add->active = $key['active'];
          $add->save();
        }
      }      
      
      //Store to DB
      DB::commit();    
    } catch (Exception $e) {          
      // Rollback from DB
      DB::rollback();
      return response(['code' => 'lc2','text' => 'error post config'], 512)->header('Content-Type', 'text/plain');
    }

    return response()->json(1);
  }
}
