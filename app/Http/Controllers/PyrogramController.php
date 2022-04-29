<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

use App\Models\Pyrogram;
use App\Models\Work;
use App\Models\WorkProperty;
use App\Models\Meta;
use App\Models\tAcc;
use App\Models\Spam;
use App\Models\JugeLogs;

class PyrogramController extends Controller
{
  public static function setToLogin(Request $request){
    return response()->json(Pyrogram::setToLogin($request->phone));    
  }

  public static function sendCode(Request $request){

    $data = $request->all();

    //Validate
    Validator::make($data, [
      'account'               => 'required',
      'code'                  => 'required|min:2',
    ])->validate();

    //Send code
    {

      try{
        //Start DB
        DB::beginTransaction();
          {//Get phone_code_hash
            //Get acc id
            $tAcc = tAcc::where('phone', $data['account'])->first();
            $meta = Meta::where('metable_type', 'App\Models\TAcc')->where('name', 'GetCode')->where('metable_id', $tAcc->id)->first();
            $hash = $meta->value;
          }
          
          {//Make work
            Work::new($data['account'], 'signIn', 100, ['code' => $data['code'], 'phone_code_hash' => $hash]);
          }
          
          {//Change Account

            //Remove bad code
            Meta::where('metable_id', $tAcc->id)->where('metable_type', 'App\Models\TAcc')->where('name', 'BadCode')->delete();

            //Get data
            $tAcc = tAcc::where('phone', $data['account'])->first();

            //Meta
            $meta = new Meta;
            $meta->name = 'CodeSend';
            $meta->value = $data['code'];

            //Attach
            $tAcc->metas()->save($meta);
          }
        //Store to DB
        DB::commit();
      } catch (Exception $e){
        // Rollback from DB
        DB::rollback();
      }


            

    }

    return response()->json(1);    
  }

  public static function api(Request $request){

    $data = $request;

    if(!isset($data->api)) return response()->json('no api');

    //Get
    if($data->api == 'get'){
      return response()->json(Pyrogram::api());
    }

    //Got
    if($data->api == 'got'){
      $ids = json_decode($data->workIds);
      $update = Work::whereIn('id', $ids)->update(['status' => 2, 'sent_at' => now()]);
      return response()->json($update);
    }

    //Done
    if($data->api == 'done'){
      //Log
      $log = JugeLogs::log(-1, json_encode($data->all()));

      //Set work done
      $update = Work::where('id', $data->id)->update(['status' => 3, 'done_at' => Carbon::parse(intval($data->done_at))]);

      
      {//Get data
        $work = Work::with('properties')->where('id', $data->id)->first();
        $phone = $work->account;
      }
      
      {//Handle response

        //Get response
        $response = json_decode($data->response);
        if(!$response) $response = $data->response;
      
        {// "_" 
          if(isset($response->_)){
            // Send Code
            if($response->_ == 'SentCode'){
  
              //Get data
              $tAcc = tAcc::where('phone', $work->account)->first();
  
              //Meta
              $meta = new Meta;
              $meta->name = 'GetCode';
              $meta->value = $response->phone_code_hash;
  
              //Attach
              $tAcc->metas()->save($meta);  
              
              //done
              JugeLogs::code($log, 1);
              return response()->json($update);
            }
            // Chat
            if($response->_ == 'Chat'){
              
              //Get data
              $chatId = WorkProperty::where('work_id', $work->id)->where('name', 'chat_id')->first()->value;
              $tAcc = tAcc::where('phone', $work->account)->first();

              //Set spam joined
              $spams = Spam::where('t_acc_phone', $tAcc->phone)->where('peer', $chatId)->update(['group_joined_at' => now()]);

              //done
              JugeLogs::code($log, 1);
              return response()->json($update);

            }    
            // Message
            if($response->_ == 'Message'){
              //Sign In
              if($work->function == "send_message"){

                //Get spam id
                $spamId = $work->properties[array_search('spam_id', array_column($work->properties->toArray(), 'name'))]->value;

                //Set send
                Spam::setSend($spamId);

                //done
                JugeLogs::code($log, 1);
                return response()->json(1);
              }
            }
            //User
            if($response->_ == 'User'){

              //Sign In
              if($work->function == "signIn"){
                Pyrogram::setLoged($work->account);
                //done
                JugeLogs::code($log, 1);
                return response()->json(1);
              }

            }
          }
        }

        // Already login
        if($response == 'already login'){
          Pyrogram::setLoged($work->account);
          //done
          JugeLogs::code($log, 1);
          return response()->json(1);
        }
        
        {// Errors
          if(gettype($response) == 'string'){
            if(strpos($response, "[401 SESSION_REVOKED]") !== false || strpos($response, "[401 AUTH_KEY_UNREGISTERED]") !== false){
              $tAcc = tAcc::where('phone', $work->account)->update(['status' => 0]);
              //done
              JugeLogs::code($log, 2);
              return response()->json($update);
            }
            elseif(strpos($response, "[400 PHONE_CODE_INVALID]") !== false){

              try{
                //Start DB
                DB::beginTransaction();
                
                  //Get acc
                  $tAcc = tAcc::where('phone', $work->account)->first();

                  //Meta
                  $meta = new Meta;
                  $meta->name = 'BadCode';
                  $meta->value = 1;
      
                  //Attach
                  $tAcc->metas()->save($meta);  

                //Store to DB
                DB::commit();
              } catch (Exception $e){
                // Rollback from DB
                DB::rollback();
              }

              //done
              JugeLogs::code($log, 2);
              return response()->json($update);
            }
            elseif(strpos($response, "[403 CHAT_WRITE_FORBIDDEN]") !== false){

              //Set bad group ban
              Pyrogram::setGroupBan($work->id, $phone);

              //Stop
              tAcc::setStop($phone, 5);

              //done
              JugeLogs::code($log, 2);
              return response()->json($update);
            }
            elseif(strpos($response, "[400 USER_BANNED_IN_CHANNEL]") !== false){

              //Set bad
              tAcc::where('phone', $phone)->update(['status' => -1]);

              //done
              JugeLogs::code($log, 2);
              return response()->json($update);
            }
            elseif(strpos($response, "[400 USERNAME_NOT_OCCUPIED]") !== false || strpos($response, "Username not found:") !== false){

              //Set bad group name
              Pyrogram::setBadGroupName($work->id);

              //Stop
              tAcc::setStop($phone, 5);

              //done
              JugeLogs::code($log, 2);
              return response()->json($update);
            }
            elseif(strpos($response, "[WinError 10053]") !== false){

              //Stop acc
              tAcc::setStop($phone, 240);

              //done
              JugeLogs::code($log, -2);
              return response()->json(0);

            }
            elseif(strpos($response, "Connection lost") !== false){

              //Stop acc
              tAcc::setStop($phone, 5);

              //done
              JugeLogs::code($log, -2);
              return response()->json(0);
              
            }
          }
        }

        
      }

      {//Unknown response

        //Send message
        if($work->function == "send_message"){

          //Get spam id
          $spamId = $work->properties[array_search('spam_id', array_column($work->properties->toArray(), 'name'))]->value;

          //Set send
          Spam::setSend($spamId);

        }

        tAcc::setStop($phone, 15);
      }


      return response()->json($update);
    }

    return response()->json('bad api');
    
  }
}
