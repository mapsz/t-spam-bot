<?php

//Check login exists
if(!isset($_GET["channel"]))done("no channel");

//Check login
if(!checkLogin($MadelineProto)) done('login first!');

switch ($_GET['work']) {
  case 'joinChannel':
    //Join
    echo 'join channel ' . $_GET["channel"];
    $updates = $MadelineProto->channels->joinChannel(['channel' => $_GET["channel"]]);
    echo 'join channel - done';
  break;  
  case 'leaveChannel':
    //Leave
    echo 'Leave channel ' . $_GET["channel"];
    $updates = $MadelineProto->channels->leaveChannel(['channel' => $_GET["channel"]]);
    echo 'Leave channel - done';
  break;
  
  default:
    echo 'bad work';
  break;
}


done($updates);