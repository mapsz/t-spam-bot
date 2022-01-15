<?php


if(!checkLogin($MadelineProto)) done('login first!');

//Send message
if($_GET['work'] == 'sendMessage'){
  //Check login exists
  if(!isset($_GET["peer"]))done("no peer");
  if(!isset($_GET["message"]))done("no message");


  echo "send message  - {$_GET['peer']}:  {$_GET['message']}";
  $updates = $MadelineProto->messages->sendMessage(['peer' => $_GET["peer"], 'message' => $_GET["message"]]);
  echo 'send message - done';

  done(json_encode($updates));
  
  exit;
}

if($_GET['work'] == 'forwardMessages'){
  //Check login exists
  if(!isset($_GET["from_peer"]))done("no from Peer");
  if(!isset($_GET["to_peer"]))done("no to Peer");
  if(!isset($_GET["id"]))done("no id");

  //Start/continue session
  $MadelineProto = new \danog\MadelineProto\API($session, $settings);
  $MadelineProto->async(false);

  //Check already login
  $me = $MadelineProto->getSelf();
  if(!$me)done("login first!");

  echo "forward message ";
  $Updates = $MadelineProto->messages->forwardMessages([
    'from_peer' => $_GET["from_peer"], 
    'id' => $_GET["id"], 
    'to_peer' => $_GET["to_peer"],
  ]);
  echo 'forward message - done';

  // var_dump($Updates);
  done(json_encode($Updates));
  
  exit;
}

if($_GET['work'] == 'getHistory'){

  //Check login exists
  if(!isset($_GET["peer"]))done("no peer");
  if(!isset($_GET["limit"]))done("no limit");
  
  //Start/continue session
  $MadelineProto = new \danog\MadelineProto\API($session, $settings);
  $MadelineProto->async(false);

  //Check already login
  $me = $MadelineProto->getSelf();
  if(!$me)done("login first!");

  echo "Get History  - {$_GET['peer']}";
  $history = $MadelineProto->messages->getHistory(
    ['peer' => $_GET["peer"], 'offset_id' => 0, 'offset_date' => 0, 'add_offset' => 0, 'limit' => $_GET["limit"], 'max_id' => 0, 'min_id' => 0]
  );
  echo 'Get History - done';

  done(json_encode($history));

  exit;

}

if($_GET['work'] == 'readHistory'){

  //Check login exists
  if(!isset($_GET["peer"]))done("no peer");
  
  //Start/continue session
  $MadelineProto = new \danog\MadelineProto\API($session, $settings);
  $MadelineProto->async(false);

  //Check already login
  $me = $MadelineProto->getSelf();
  if(!$me)done("login first!");

  echo "Read History  - {$_GET['peer']}";
  $update = $MadelineProto->messages->readHistory(['peer' => $_GET["peer"], 'max_id' => 0, ]);
  echo 'Read History - done';

  done(json_encode($update));

  exit;

}