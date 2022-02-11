<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MadelineLog extends Model
{
    use HasFactory;

    // public $id;
    // public $acc;

    // private function setId($id){$this->$id = $id;}
    // private function getId(){return $this->$id;}
    // private function setAcc($acc){$this->$acc = $acc;}
    // private function getAcc(){return $this->$acc;}
      
    public function __construct($acc, $function) {
  
      $this->acc = $acc;
      $this->status = 3;
      $this->function = $function;
      $id = $this->save();


      return $id;
    }

    public function fail($result = NULL){

      if(gettype($result) == 'object' || gettype($result) == 'array') $result = json_encode($result);
      if($result == null) $result = 'null';

      $this->status = 0;
      $this->result = $result;
      return $this->save();
    }

    public function success($result = NULL){


      if(gettype($result) == 'object' || gettype($result) == 'array') $result = json_encode($result);
      if($result == null) $result = 'null';

      $this->status = 1;
      $this->result = $result;
      return $this->save();
    }

    public function info($result = NULL){

      if(gettype($result) == 'object' || gettype($result) == 'array') $result = json_encode($result);
      if($result == null) $result = 'null';

      $this->status = 2;
      $this->result = $result;
      return $this->save();
    }
}
