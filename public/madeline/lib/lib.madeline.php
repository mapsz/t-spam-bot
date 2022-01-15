<?php


//Check login
if(!checkLogin($MadelineProto)) done('login first!');

switch ($_GET['work']) {
  case 'getInfo':
    echo "Get Info";

    echo $_GET["login"];

    //Validate
    if(!isset($_GET["peer"]))done("no peer");
        
    //Get info
    $info = $MadelineProto->getInfo($_GET["peer"]);
    echo "Get Info - done";
    done(json_encode($info));
  break;
  case 'getAllChats':
    echo "Get All Chats";
            
    //Get info
    $info = $MadelineProto->messages->getAllChats();
    echo "Get All Chats - done";
    done(json_encode($info));
  break;
  case 'getFullDialogs':
    echo "Get Full Dialogs";
            
    //Get info
    $info = $MadelineProto->getFullDialogs();
    echo "Get Full Dialogs - done";
    done(json_encode($info));
  break;
  
  default:
    done('bad work!');
    break;
}

