<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sopamo\LaravelFilepond\Filepond;
use Illuminate\Support\Facades\File;
use Image;

class JugeFileUpload extends Model
{
  public static function cacheFile($file){
    $filepond = new Filepond();
    //Get temp path
    $tempPath = config('filepond.temporary_files_path');   
    //Check path exists
    if (!file_exists($tempPath)){
      mkdir($tempPath, 0755, true);
    }    
    //Make unique name
    $filePath = tempnam($tempPath, 'laravel-filepond');
    //Add extension
    $filePath .= '.' . $file->extension();      
    $filePathParts = pathinfo($filePath);
    //Attemp to move
    if (!$file->move($filePathParts['dirname'], $filePathParts['basename'])) {
      return false;
    }
    //Get file encription
    return $filepond->getServerIdFromPath($filePath);
  }

  public static function saveFile($enc,$pathName,$resize=false){
    $filepond = new Filepond();
    //Get cache file path
    $cacheFilePath = $filepond->getPathFromServerId($enc);
    //Get File ext
    $ext = pathinfo($cacheFilePath)['extension'];
    // jpeg to jpg
    if($ext == 'jpeg') $ext = 'jpg';
    //Check save path exists
    if (!file_exists(pathinfo($pathName)['dirname'])){
      mkdir(pathinfo($pathName)['dirname'], 0755, true);
    }   
    //Full save path
    $fullPath = $pathName . '.' . $ext;
    //Move file
    if(\File::move($cacheFilePath, $fullPath)){
      if($resize){
        // $img->save($savePath.'90/' . $img->filename.'.jpg',90,'jpg');
        $img =Image::make($fullPath);
        File::delete($fullPath);        
        $img->resize($resize['w'], $resize['h'])->save($pathName.'.jpg',90,'jpg');
        
      }
      return true;
    }else
      return false;
  }

  public static function getEncFilePath($enc){
    $filepond = new Filepond();       
    return $filepond->getPathFromServerId($enc);
  }

  public static function deleteFile($file){
    if(\File::delete(public_path().$file)){
      return true;
    }else
      return false;
  }

  public static function generateFileName($path, $id){

    //Make dir
    if(!is_dir($path)){dump($path); mkdir ($path,0777,1);} 
                   

    //Get folder files
    $folderFiles = scandir($path);

    //filter files
    $files = [];
    $max_file = 0;
    foreach ($folderFiles as $k => $file) {
      if(strpos($file,strval($id)) !== false){
        array_push($files,$file);
      }
    }

    if(count($files) > 0){
      //Sort files
      rsort($files);

      //Generate new file name
      $last_file = $files[0];
      $max_file = substr($last_file, strpos($last_file,'_')+1, strpos($last_file,'_') - strpos($last_file,'.')-2);
      $max_file++;
    }


    return $id . '_' . $max_file;
  }



}
