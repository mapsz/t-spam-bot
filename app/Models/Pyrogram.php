<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

use App\Models\Meta;
use App\Models\Work;
use App\Models\WorkProperty;
use App\Models\tAcc;
use App\Models\Spam;

class Pyrogram extends Model
{
  use HasFactory;

  public static function setGroupBan($workId, $phone){

    $name = WorkProperty::where('work_id', $workId)->where('name', 'chat_id')->first()->value;
    Spam::where('peer', $name)->where('t_acc_phone', $phone)->update(['status' => -1]);

    return true;

  }

  public static function setBadGroupName($workId){

    $name = WorkProperty::where('work_id', $workId)->where('name', 'chat_id')->first()->value;
    Spam::where('peer', $name)->update(['status' => -3]);

    return true;
  }

  public static function api(){

    // Spam::pDoSends();
    // Spam::pDoJoins();


    $works = Work::getActualWorks();

    $fWorks = [];
    foreach ($works as $key => $work) {
      $dict = $work->toArray();
      //To timestamp
      $dict['created_at'] = Carbon::parse($dict['created_at'])->timestamp;
      $dict['updated_at'] = Carbon::parse($dict['updated_at'])->timestamp;
      array_push($fWorks, $dict);
    }

    return $fWorks;
    
  }

  public static function setToLogin($tAcc){

    //Get Acc
    $acc = tAcc::where('phone',$tAcc)->first();

    if(!$acc) return false;

    try {

      //Make to login Meta
      // $meta = new Meta;
      // $meta->metable_id =$acc->id;
      // $meta->metable_type = "App\Models\TAcc";
      // $meta->name = 'toLogin';
      // $meta->value = 0;
      // $meta->save();

      //Make Work
      Work::new($acc->phone, 'login', 100);

      //Set T Acc status
      $acc->status = 2;
      $acc->save();

    } catch (\Throwable $th) {
      return false;
    }

    return true;

  }

  public static function setLoged($phone){

    try{
      //Start DB
      DB::beginTransaction();

      //Get account
      $tAcc = tAcc::where('phone', $phone)->first();

      //Update status
      tAcc::where('phone', $phone)->update(['status' => 1]);

      //Meta
      Meta::where('metable_id', $tAcc->id)->where('metable_type', 'App\Models\TAcc')->where('name', 'GetCode')->delete();
      Meta::where('metable_id', $tAcc->id)->where('metable_type', 'App\Models\TAcc')->where('name', 'CodeSend')->delete();
      Meta::where('metable_id', $tAcc->id)->where('metable_type', 'App\Models\TAcc')->where('name', 'BadCode')->delete();

      
      //Store to DB
      DB::commit();
    } catch (Exception $e){
      // Rollback from DB
      DB::rollback();
    }

  }

}
