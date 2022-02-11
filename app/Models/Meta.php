<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\TAcc;
use Carbon\Carbon;

class Meta extends Model
{
  use HasFactory;  public $guarded = [];

  public static function beautify($metas){

    $fMetas = [];
    foreach ($metas as $key => $value) {
      $fMetas[$key] = $value;
    }
    
    return $fMetas;

  }

  public static function get($metas, $toGet, $full = false){
    foreach ($metas as $key => $meta) {
      if($meta['name'] == $toGet){
        if($full){
          return $meta;
        }else{
          return $meta['value'];
        }        
      } 
    }
  }

  public static function setDelay($acc, $job){

    if(str_contains($acc, '+')) {
      $TAcc = TAcc::where('phone', $acc)->first();
      if(isset($TAcc->id)) $acc = $TAcc->id;
    }

    //Set joins
    $meta = Meta::updateOrCreate(
      [
        'metable_id' => $acc,
        'metable_type' => 'App\Models\TAcc', 
        'name' => $job
      ],
      ['value' => now()]
    );
  }  
  
  public static function getDelay($acc, $job, $delay){
    if(str_contains($acc, '+')) {
      $TAcc = TAcc::where('phone', $acc)->first();
      if(isset($TAcc->id)) $acc = $TAcc->id;
    }

    $meta = Meta::where(
      [
        'metable_id' => $acc,
        'metable_type' => 'App\Models\TAcc', 
        'name' => $job
      ]
    )->first();

    if(!isset($meta->value)) return false;

    dump(Carbon::parse($meta->value)->diffInSeconds(now()));

    return Carbon::parse($meta->value)->diffInSeconds(now()) < $delay;
  }
  
  public function metable(){
    return $this->morphTo();
  }
}
