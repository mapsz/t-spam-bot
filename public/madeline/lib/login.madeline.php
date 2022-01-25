<?php

//Check login
if(checkLogin($MadelineProto)) done("already log in");

{//Login  
  
  //Log in
  {
    
    //Phone Log in
    if(!isset($_GET["type"]) || $_GET["type"] != "code"){
      echo "Phone log in \n";

      $auth = $MadelineProto->phoneLogin($_GET["login"]);

      echo "Phone log in - done \n";

      if(!isset($auth["_"])) done("Login error");
    }

    //Code Confirm
    if(isset($_GET["type"]) && $_GET["type"] == "code"){
      echo "Code Confirm \n";

      // $auth = function() use ($MadelineProto){
      //   $res = yield $MadelineProto->completePhoneLogin($_GET["code"]);
      //   return $res;      
      // };
      // foreach ($auth as $key => $value) {
      //   echo $value;
      // }

      $auth = function() use ($MadelineProto){

        $auth = ( yield completePhoneLogin($MadelineProto, $login) );
        return $auth;
      };
      var_dump($auth);


      echo "Code Confirm - done\n";
      if(!isset($auth["_"])) done("Code error");

      done(json_encode($auth));
      exit;
    }

    echo "----------------\n";
    echo "Auth check\n";
    var_dump($auth);
    echo "----------------\n";

    switch ($auth["_"]) {
      case "auth.sentCode":
        done("need code");
        break;
      case "account.noPassword":
        done("2FA is enabled but no password is set!");
        break;
      case "account.password":
        done("Please enter your password!");
        //Todo @@@ if needed
        // $MadelineProto->complete2falogin($password);
        break;
      case "account.needSignup":
        done("no account!");
        break;      
      default:
        done("Bad auth");
        break;
    }
    

  }

  exit;
  
  
  var_dump($me);
}


function phoneLogin($MadelineProto, $login){
  return yield $MadelineProto->phoneLogin($login);
}

function completePhoneLogin($MadelineProto, $login){
  return yield $MadelineProto->completePhoneLogin($login);
}
