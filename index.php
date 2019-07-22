<?php

require_once(__DIR__ . "/vendor/autoload.php");
use Jenssegers\Blade\Blade;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

$tempExt = json_decode(str_replace('*m*','"', $argv[5]));
$tempSrv = json_decode(str_replace('*m*','"', $argv[4]));
$tempDb = json_decode(str_replace('*m*','"', $argv[6]),true);
$data = json_decode(str_replace('*m*','"', $argv[7]),true);
$tempRequest = json_decode(str_replace('*m*','"', $argv[8]),true);
$permissions = json_decode(str_replace('*m*','"', $argv[14]),true);

function extensionDb($target){
    global $tempDb;
    return $tempDb[$target];
}

function extension(){
    $json = json_decode(file_get_contents(getPath("db.json")),true);
    return $json;
}

function server(){
    global $tempSrv;
    return $tempSrv;
}

// Translation disabled for now.
function __($str){
    return $str;
}

function request($target = null){
    global $tempRequest;
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
    global $argv;
    return $argv[10] . "/" . $target;
}

function respond($message,$code = "200"){
    return json_encode([
        "message" => $message,
        "status" => $code
    ]);
}

function navigate($name,$params = []){
    global $argv;
    $args = '';
    if($params != []){
    $args = '?&';
        foreach($params as $key=>$param){
            $args = $args . "&$key=$param";
        }
    }
    return $argv[11] . '/' . $name . $args;
}

function view($name,$params = []){
    $blade = new Blade([__DIR__ . "/views/"],"/tmp");
    return $blade->render($name,$params);
}

function externalAPI($target, $extension_id, $server_id = null){
    global $argv;
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
                    "contents" => $argv[12]
                ],
                [
                    "name" => "extension_id",
                    "contents" => $argv[13],
                ]
            ],
        ]);
        return $response->getBody()->getContents();
    }catch(GuzzleException $exception){
        return $exception->getResponse()->getBody()->getContents();
    }
}

function runCommand($command){
    global $argv;
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
                    "contents" => $argv[13],
                ],
                [
                    "name" => "command",
                    "contents" => $command
                ],
                [
                    "name" => "token",
                    "contents" => $argv[12]
                ]
            ],
        ]);
        return $response->getBody()->getContents();
    }catch(GuzzleException $exception){
        return $exception->getResponse()->getBody()->getContents();
    }
}

function putFile($localPath,$remotePath){
    global $argv;
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
                    "contents" => $argv[12]
                ],
                [
                    "name" => "extension_id",
                    "contents" => $argv[13],
                ]
            ],
        ]);
        return "ok";
    }catch(GuzzleException $exception){
        return $exception->getResponse()->getBody()->getContents();
    }
}

function getFile($localPath,$remotePath){
    global $argv;
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
                    "contents" => $argv[12]
                ],
                [
                    "name" => "extension_id",
                    "contents" => $argv[13],
                ]
            ],
        ]);
        return $response->getBody()->getContents();
    }catch(GuzzleException $exception){
        return $exception->getResponse()->getBody()->getContents();
    }
}

function getPath($filename = null){
    global $argv;
    return dirname(dirname($argv[1])) . "/" . $filename;
}

function can($name){
    global $permissions;
    global $argv;
    if($argv[14] == "admin"){
        return true;
    }
    return in_array($name,$permissions);
}

$functions = get_defined_functions();
$keys = array_keys($functions['user']);
$last_index = array_pop($keys);

// Functions PHP
if(is_file($argv[1])){
    include($argv[1]);
}

$functions = get_defined_functions();
$new_functions = array_slice($functions['user'], $last_index + 1);

if($argv[3] == "null"){
    set_error_handler(function(){
        return "error";
    });
    echo call_user_func($argv[9]);
    restore_error_handler();
}else{
    shell_exec("mkdir /tmp/" . $argv[13]);
    $blade = new Blade([
        dirname($argv[1]),
        __DIR__ . "/views/"
    ],"/tmp/" . $argv[13]);
    echo $blade->render($argv[3],[
        "data" => $data
    ]);
}