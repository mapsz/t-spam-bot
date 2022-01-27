<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load composer
require __DIR__ . '/vendor/autoload.php';
$libFolder = __DIR__ . "/lib/";

//Decrypt
$get = decrypt($_GET['enc']);

{//Validator
  //Work
  if(!isset($get['work'])){echo 'no work';exit;}
  //Login
  if(!isset($get["login"])){echo "no login";exit;}
  $get["login"] = trim($get["login"]);
  if(strpos($get["login"], '+') === false) $get["login"] = '+'.$get["login"];
}

{//Data
  $work = $get['work'];
  $login = $get['login'];
  $params = (array) $get['params'];
  $params = count($params) == 0 ? null : $params;
}

{//Setup settings/session
  $settings = new \danog\MadelineProto\Settings\AppInfo;
  // $settings->setApiId("14348073");
  // $settings->setApiHash("4ab1ffcc419a6a614ba95db5a14c0707");
  $settings->setApiId("11284432");
  $settings->setApiHash("f94fe34d1d0d5d968955508f3b91b3c4");
    
  //Setup session
  $session = __DIR__ . "/sessions/" . $login . ".madeline";
}

echo 'Work - ' . $work . "\n";

//Start/continue session
$MadelineProto = new \danog\MadelineProto\API($session, $settings);
$MadelineProto->async(false);

//Query
try {
  
  $update = null;
  $fail = false;
  $dot = false;

  //Dot
  if(strpos($work, '.') !== false){
    $works = explode('.',$work);
    $work1 = $works[0];
    $work2 = $works[1];
    $dot = true;
  }

  if($dot){
    echo "Dot - $work1 $work2}";
    //Dot
    switch ($work) {
      case 'value':
        # code...
      break;      
      default:
        $update = $params == null ? $MadelineProto->$work1->$work2() : $MadelineProto->$work1->$work2($params);
      break;
    }

  }else{
    //No Dot
    switch ($work) {
      case 'phoneLogin':
        $update = $MadelineProto->phoneLogin($login);
      break;
      //Single parameter
      case 'completePhoneLogin':
        $update = $MadelineProto->$work($params['code']);
      break;    
      default:
        $update = $params == null ? $MadelineProto->$work() : $MadelineProto->$work($params);
      break;
    }

  }



} catch (Exception $e) {
  $fail = true;
  $update = (string) $e;
}

echo "\n\nWork DONE " . ($fail ? 'fail' : 'success') . "\n";

if($fail) fail($update);

done($update);




//Do work
switch ($_GET['work']) {
  case 'getInfo':
  case 'getAllChats':
  case 'getFullDialogs':
  case 'testMessage':
    require $libFolder . 'lib.madeline.php';
    break;
  case 'login':
    require $libFolder . 'login.madeline.php';
    break;
  case 'joinChannel':
  case 'leaveChannel':
    require $libFolder . 'channels.madeline.php';
    break;
  case 'sendMessage':
  case 'getHistory':
  case 'readHistory':
  case 'forwardMessages':
    require $libFolder . 'messages.madeline.php';
    break;
  case 'test':
    done( $MadelineProto->channels->getChannels(['_' => 'chatId', 'id' => 778310890]) );
  break;
  default:
    echo 'bad work';
}


function done($text){
  if(gettype($text) == 'object' || gettype($text) == 'array') $text = json_encode($text);
  echo "\n" . '{"result":1,"text":`'.$text.'`}';
  exit;
}
function fail($text){
  if(gettype($text) == 'object' || gettype($text) == 'array') $text = json_encode($text);
  echo "\n" . '{"result":0,"text":`'.$text.'`}';
  exit;
}

function checkLogin($MadelineProto){

  echo "Check Login - {$_GET["login"]}\n";

  $user = $MadelineProto->getSelf();
  
  echo "Check Login - done\n";

  echo "full\n";
  // echo var_dump($user);
  echo gettype($user) == 'object' || gettype($user) == 'array' ? json_encode($user) : $user;
  // echo $user["status"]["_"];
  echo "\n";
  
  if((isset($user['_']) || isset($user->_))  && isset($user['phone']) &&  '+'.$user['phone'] == $_GET["login"]){
    echo "Loged in 🐸";
    echo "\n\n";
    return true;
  }else{
    echo "Not Loged in 🐙";
    echo "\n\n";
    return false;    
  }



}

function decrypt(string $encrypted){
  $key = 'pSUmlYgwbfAu57cH@yH4Ky9z6KHC9OJa';

  $decoded = base64_decode($encrypted);
  $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
  $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
  
  $plain = sodium_crypto_secretbox_open(
      $ciphertext,
      $nonce,
      $key
  );
  if (!is_string($plain)) {
      throw new Exception('Invalid MAC');
  }
  sodium_memzero($ciphertext);
  sodium_memzero($key);
  return (array) json_decode($plain);
}