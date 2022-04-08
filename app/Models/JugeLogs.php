<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JugeLogs extends Model
{
  use HasFactory;

  public $guarded = [];

  public static function log($code, $body = ""){

    if(gettype($body) == 'object' || gettype($body) == 'array') $body = json_encode($body);
    if($body == null) $body = 'null';

    $log = new JugeLogs;
    $log->code = $code;
    $log->body = $body;
    $log->save();
    return $log;
  }

  public static function code($log, $code){
    return self::where('id', $log->id)->update(['code' => $code]);
  }
}
