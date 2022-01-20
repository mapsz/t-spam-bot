<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load composer
require __DIR__ . '/vendor/autoload.php';
$libFolder = __DIR__ . "/lib/";

{//Validator
  //Work
  if(!isset($_GET['work'])){echo 'no work';exit;}
  //Login
  if(!isset($_GET["login"])){echo "no login";exit;}
  if(strpos($_GET["login"], '+') === false) $_GET["login"] = '+'.$_GET["login"];
}

{//Setup settings
  $settings = new \danog\MadelineProto\Settings\AppInfo;
  // $settings->setApiId("14348073");
  // $settings->setApiHash("4ab1ffcc419a6a614ba95db5a14c0707");
  $settings->setApiId("11284432");
  $settings->setApiHash("f94fe34d1d0d5d968955508f3b91b3c4");
    
  //Setup session
  $session = __DIR__ . "/sessions/" . $_GET["login"] . ".madeline";
}

echo 'Work - ' . $_GET['work'] . "\n\n";


//Start/continue session
$MadelineProto = new \danog\MadelineProto\API($session, $settings);
$MadelineProto->async(false);

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