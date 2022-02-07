<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Forward extends Model
{
  use HasFactory;



  public static function getUsersDialogsFromDialogs($usersDialogs){
    if(!is_array($usersDialogs)) return false;
    foreach ($fullDialogs as $key => $dialog){
      if(isset($dialog['peer']) && isset($dialog['peer']->_) && $dialog['peer']->_ == 'peerUser') array_push($usersDialogs, $dialog);
    }
    return $usersDialogs;
  }
  
}
