<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JugeFileUpload;

class JugeFileUploadController extends Controller
{
  public function fileUpload(Request $request){

    $file = $request->file('file');

    //Catch file
    $fileUpload = new JugeFileUpload;
    $fileEnc = $fileUpload->cacheFile($file);

    //Response
    if(!$fileEnc)
      //Error
      return response('Could not save file', 501)
                ->header('Content-Type', 'text/plain');
    else{
      //Success
      return response($fileEnc, 200)
                ->header('Content-Type', 'text/plain');
    }
  }  

  public function fileDelete(Request $request){


    $r = JugeFileUpload::deleteFile($request->file);

    if($r)
      return response()->json($r, 200);
    else
      return response()->json($r, 512);
  }
}
