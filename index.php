<?php

require_once(__DIR__ . "/vendor/autoload.php");
use Jenssegers\Blade\Blade;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

$decrypted = openssl_decrypt($argv[2],'aes-256-cfb8',shell_exec('cat ' . $argv[1]));

$limanData = json_decode(base64_decode(substr($decrypted,16)),false,512);

foreach ($limanData as $key=>$item) {
    $json = json_decode($item,true);
    $limanData[$key] = (json_last_error() == JSON_ERROR_NONE) ? $json : $item;
}

function extensionDb($target){
    global $limanData;
    return $limanData[5][$target];
}

function extension(){
    $json = json_decode(file_get_contents(getPath("db.json")),true);
    return $json;
}

function server(){
    global $limanData;
    return (Object)$limanData[3];
}

// Translation disabled for now.
function __($str){
    global $limanData;
    $folder = dirname(dirname($limanData[0])) . "/lang";
    $file = $folder . "/" . $limanData[15] . ".json";
    if(!is_dir($folder) || !is_file($file)){
        return $str;
    }
    
    // Read JSON
    $json = json_decode(file_get_contents($file),true);
    return (array_key_exists($str,$json)) ? $json[$str] : $str;
}

function request($target = null){
    global $limanData;
    $tempRequest = $limanData[7];
    if($target){
        if(array_key_exists($target,$tempRequest)){
            return $tempRequest[$target];
        }else{
            return null;
        }
    }
    return $tempRequest;    
}

function API($target){
    global $limanData;
    return $limanData[9] . "/" . $target;
}

function respond($message,$code = "200"){
    return json_encode([
        "message" => $message,
        "status" => $code
    ]);
}

function navigate($name,$params = []){
    global $limanData;
    $args = '';
    if($params != []){
    $args = '?&';
        foreach($params as $key=>$param){
            $args = $args . "&$key=$param";
        }
    }
    return $limanData[10] . '/' . $name . $args;
}

function view($name,$params = []){
    $blade = new Blade([__DIR__ . "/views/"],"/tmp");
    return $blade->render($name,$params);
}

function externalAPI($target, $extension_id, $server_id = null){
    global $limanData;
    $client = new Client([
        'verify' => false,
        'cookies' => true
    ]);
    try{
        $response = $client->request('POST','https://127.0.0.1/lmn/private/extensionApi',[
            "multipart" => [
                [
                    "name" => "server_id",
                    "contents" => ($server_id) ? $server_id : server()->id,
                ],
                [
                    "name" => "extension_id",
                    "contents" => $extension_id
                ],
                [
                    "name" => "target",
                    "contents" => $target
                ],
                [
                    "name" => "token",
                    "contents" => $limanData[11]
                ],
                [
                    "name" => "extension_id",
                    "contents" => $limanData[12],
                ]
            ],
        ]);
        return $response->getBody()->getContents();
    }catch(GuzzleException $exception){
        return $exception->getResponse()->getBody()->getContents();
    }
}

function runCommand($command){
    global $limanData;
    global $tempExt;
    $client = new Client([
        'verify' => false,
        'cookies' => true
    ]);
    try{
        $response = $client->request('POST','https://127.0.0.1/lmn/private/runCommandApi',[
            "multipart" => [
                [
                    "name" => "server_id",
                    "contents" => server()->id,
                ],
                [
                    "name" => "extension_id",
                    "contents" => $limanData[12],
                ],
                [
                    "name" => "command",
                    "contents" => $command
                ],
                [
                    "name" => "token",
                    "contents" => $limanData[11]
                ]
            ],
        ]);
        return $response->getBody()->getContents();
    }catch(GuzzleException $exception){
        return $exception->getResponse()->getBody()->getContents();
    }
}

function putFile($localPath,$remotePath){
    global $limanData;
    $client = new Client([
        'verify' => false,
        'cookies' => true
    ]);
    try{
        $response = $client->request('POST','https://127.0.0.1/lmn/private/putFileApi',[
            "multipart" => [
                [
                    "name" => "server_id",
                    "contents" => server()->id,
                ],
                [
                    "name" => "localPath",
                    "contents" => $localPath
                ],
                [
                    "name" => "remotePath",
                    "contents" => $remotePath
                ],
                [
                    "name" => "token",
                    "contents" => $limanData[11]
                ],
                [
                    "name" => "extension_id",
                    "contents" => $limanData[12],
                ]
            ],
        ]);
        return "ok";
    }catch(GuzzleException $exception){
        return $exception->getResponse()->getBody()->getContents();
    }
}

function getFile($localPath,$remotePath){
    global $limanData;
    $client = new Client([
        'verify' => false,
        'cookies' => true
    ]);
    try{
        $response = $client->request('POST','https://127.0.0.1/lmn/private/getFileApi',[
            "multipart" => [
                [
                    "name" => "server_id",
                    "contents" => server()->id,
                ],
                [
                    "name" => "localPath",
                    "contents" => $localPath
                ],
                [
                    "name" => "remotePath",
                    "contents" => $remotePath
                ],
                [
                    "name" => "token",
                    "contents" => $limanData[11]
                ],
                [
                    "name" => "extension_id",
                    "contents" => $limanData[12],
                ]
            ],
        ]);
        return $response->getBody()->getContents();
    }catch(GuzzleException $exception){
        return $exception->getResponse()->getBody()->getContents();
    }
}

function getPath($filename = null){
    global $limanData;
    return dirname(dirname($limanData[0])) . "/" . $filename;
}

function can($name){
    global $limanData;
    if($limanData[13] == "admin"){
        return true;
    }
    return in_array($name,$limanData[13]);
}

$functions = get_defined_functions();
$keys = array_keys($functions['user']);
$last_index = array_pop($keys);

// Functions PHP
if(is_file($limanData[0])){
    include($limanData[0]);
}

$functions = get_defined_functions();
$new_functions = array_slice($functions['user'], $last_index + 1);
if($limanData[2] == null){
    set_error_handler(function(){
        return "error";
    });
    echo call_user_func($limanData[8]);
    restore_error_handler();
}else{
    shell_exec("mkdir /tmp/liman" . $limanData[12]);
    $blade = new Blade([
        dirname($limanData[0]),
        __DIR__ . "/views/"
    ],"/tmp/liman" . $limanData[12]);
    echo $blade->render($limanData[2],[
        "data" => $limanData[6]
    ]);
}