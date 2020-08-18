<?php
ob_start();
require_once(__DIR__ . "/vendor/autoload.php");

function customErrorHandler($exception, $err_str=null, $error_file=null, $error_line=null) {
    if($err_str){
        if($exception==8){
            return;
        }
        $message=$err_str." File: ".$error_file." Line: ".$error_line;
    }else{
        $message=$exception->getMessage();
    }
    abort($message, 201);
}

// set_exception_handler('customErrorHandler');
// set_error_handler('customErrorHandler');

use Jenssegers\Blade\Blade;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use mervick\aesEverywhere\AES256;

$decrypted = AES256::decrypt($argv[2], shell_exec('cat ' . $argv[1]));
// $decrypted = openssl_decrypt($argv[2], 'aes-256-cfb8', );

$limanData = json_decode($decrypted, true, 512);

/*
    0. functions.php path 
    1. target function
    2. server obj.
    3. extension obj.
    4. extensionDB
    5. requests array
    6. Api Route
    7. Navigation Route
    8. Token
    9. Permissions
    10. Locale
    11. User Obj
    12. Public Path Route
    13. Is Request Ajax
    14. Current Log Object Id
*/

foreach ($limanData as $key => $item) {
    if(is_string($item)){
        @$json = json_decode($item, true);
        $limanData[$key] = (json_last_error() == JSON_ERROR_NONE) ? $json : $item;
    }
}

function restoreHandler()
{
    restore_error_handler();
    restore_exception_handler();
}

function setHandler()
{
    set_exception_handler('customErrorHandler');
    set_error_handler('customErrorHandler');
}

function extensionDb($target)
{
    global $limanData;
    return $limanData["settings"][$target];
}

function extension()
{
    $json = json_decode(file_get_contents(getPath("db.json")), true);
    return $json;
}

function server()
{
    global $limanData;
    return (object) $limanData["server"];
}
 
function user()
{
    global $limanData;
    return (object) $limanData["user"];
}

// Translation disabled for now.
function __($str)
{
    global $limanData;
    $folder = dirname(dirname($limanData["functionsPath"])) . "/lang";
    $file = $folder . "/" . $limanData["locale"] . ".json";
    if (!is_dir($folder) || !is_file($file)) {
        return $str;
    }

    // Read JSON
    $json = json_decode(file_get_contents($file), true);
    return (array_key_exists($str, $json)) ? $json[$str] : $str;
}

function request($target = null)
{
    global $limanData;
    $tempRequest = $limanData["requestData"];
    if ($target) {
        if (array_key_exists($target, $tempRequest)) {
            return html_entity_decode($tempRequest[$target]);
        } else {
            return null;
        }
    }
    return $tempRequest;
}

function API($target)
{
    return "/extensionRun/" . $target;
}

function respond($message, $code = "200")
{
    return json_encode([
        "message" => $message,
        "status" => intval($code)
    ]);
}

function abort($message, $code = "200")
{
    global $limanData;
    ob_clean();
    if(!boolval($limanData["ajax"])){
        echo view('alert', [
            "type" => intval($code) == 200 ? "success" : "danger",
            "title" => intval($code) == 200 ? __("Başarılı") : __("Hata"),
            "message" => $message
        ]);
    }else{
        echo respond($message, $code);
    }
    exit();
}

function navigate($name, $params = [])
{
    global $limanData;
    $args = '';
    if ($params != []) {
        $args = '?&';
        foreach ($params as $key => $param) {
            $args = $args . "&$key=$param";
        }
    }
    return $limanData[7] . '/' . $name . $args;
}

function view($name, $params = [])
{
    global $limanData;
    $path = "/tmp/" . $limanData["extension"]["id"];
    if(!is_dir($path)){
        mkdir($path);
    }
    $blade = new Blade([dirname($limanData["functionsPath"]), __DIR__ . "/views/"], $path);
    return $blade->render($name, $params);
}

function limanInternalRequest($url,$data, $server_id = null,$extension_id = null)
{
    global $limanData;
    $client = new Client([
        'verify' => false
    ]);
    $extraParams = [];
    foreach($data as $key => $value){
        $extraParams[] = [
            "name" => $key,
            "contents" => $value
        ];
    }
    $server_id = ($server_id) ? $server_id : server()->id;
    $extension_id = ($extension_id) ? $extension_id : $limanData["extension"]["id"];
    try {
        $response = $client->request('POST', "https://127.0.0.1/lmn/private/$url", [
            'headers' => [
                'Accept'     => 'application/json',
            ],
            "multipart" => array_merge($extraParams, [
                [
                    "name" => "extension_id",
                    "contents" => $extension_id
                ],
                [
                    "name" => "server_id",
                    "contents" => $server_id,
                ],
                [
                    "name" => "token",
                    "contents" => $limanData["token"]
                ]
            ]),
        ]);
        return $response->getBody()->getContents();
    } catch (GuzzleException $exception) {
        if($exception->getResponse() && $exception->getResponse()->getStatusCode() > 400){
            $message = 
                json_decode($exception->getResponse()->getBody()->getContents())
                ->message;
        }else{
            $message = $exception->getMessage();
        }
        abort($message, 201);
    }
}

function requestReverseProxy($hostname,$port)
{
    return limanInternalRequest('reverseProxyRequest',[
        "hostname" => $hostname,
        "port" => $port
    ]); 
}

function dispatchJob($function_name,$parameters = [])
{
    global $limanData;
    $client = new Client([
        'verify' => false,
    ]);
    try {
        $response = $client->request('POST', 'https://127.0.0.1/lmn/private/dispatchJob', [
            "json" => [
                "server_id" => server()->id,
                "extension_id" => $limanData["extension"]->id,
                "function_name" => $function_name,
                "token" => $limanData["token"],
                "parameters" => $parameters
            ],
        ]);
        return $response->getBody()->getContents();
    } catch (GuzzleException $exception) {
        return $exception->getResponse()->getBody()->getContents();
    }
}

function getJobList($function_name)
{
    return limanInternalRequest('getJobList',[
        "function_name" => $function_name,
        "parameters" => json_decode($parameters)
    ]);
}

function publicPath($path)
{
    global $limanData;
    return $limanData[12] . "/" .  base64_encode($path);
}

function externalAPI($target, $target_extension_name, $target_server_id,  $params = [])
{
    return limanInternalRequest('extensionApi',[
        "target_function" => $target,
        "target_extension_name" => $target_extension_name,
        "target_server_id" => $target_server_id,
        "extra_params" => json_encode($params)
    ]);
}

function runCommand($command)
{
    return limanInternalRequest('runCommandApi',[
        "command" => $command
    ]);
}

function runScript($name,$parameters = "",$sudo = true)
{
    return limanInternalRequest('runScriptApi',[
        "scriptName" => $name,
        "runAsRoot" => $sudo ? "yes" : "no",
        "parameters" => $parameters
    ]);
}

function putFile($localPath, $remotePath)
{
    return limanInternalRequest('putFileApi',[
        "localPath" => $localPath,
        "remotePath" => $remotePath,
    ]);
}

function sudo()
{
    if(server()->type == "linux_certificate"){
        return "sudo ";
    }
    $pass64 = base64_encode(extensionDb("clientPassword")."\n");
    return 'echo ' . $pass64 .' | base64 -d | sudo -S -p " " id 2>/dev/null 1>/dev/null; sudo ';
}


function getFile($localPath, $remotePath)
{
    return limanInternalRequest('getFileApi',[
        "localPath" => $localPath,
        "remotePath" => $remotePath,
    ]);
}

function openTunnel($remote_host, $remote_port, $username, $password)
{
    return limanInternalRequest('openTunnel',[
        "remote_host" => $remote_host,
        "remote_port" => $remote_port,
        "username" => $username,
        "password" => $password
    ]);
}

function stopTunnel($remote_host, $remote_port)
{
    global $limanData;
    $client = new Client([
        'verify' => false
    ]);
    $extraParams = [
        [
            "name" => "remote_host",
            "contents" => $remote_host
        ],
        [
            "name" => "remote_port",
            "contents" => $remote_port
        ]
    ];
    try {
        $response = $client->request('POST', "https://127.0.0.1/lmn/private/stopTunnel", [
            'timeout' => 0.1,
            'headers' => [
                'Accept'     => 'application/json',
            ],
            "multipart" => array_merge($extraParams, [
                [
                    "name" => "extension_id",
                    "contents" => $limanData["extension"]->id
                ],
                [
                    "name" => "server_id",
                    "contents" => server()->id,
                ],
                [
                    "name" => "token",
                    "contents" => $limanData["token"]
                ]
            ]),
        ]);
        return $response->getBody()->getContents();
    } catch (GuzzleException $exception) {
        if($exception->getResponse() && $exception->getResponse()->getStatusCode() > 400){
            $message = 
                json_decode($exception->getResponse()->getBody()->getContents())
                ->message;
        }else{
            if($exception->getHandlerContext()["errno"] == 28){
                return "ok";
            }
            $message = $exception->getMessage();
        }
        abort($message, 201);
    }
}

function getPath($filename = null)
{
    global $limanData;
    return dirname(dirname($limanData["functionsPath"])) . "/" . $filename;
}

function can($name)
{
    return true;
    global $limanData;
    if ($limanData[9] == "admin") {
        return true;
    }
    return in_array($name, $limanData[9]);
}

function sendNotification($title,$message, $type = "notify")
{
    return limanInternalRequest('sendNotification',[
        "title" => $title,
        "message" => $message,
        "type" => $type
    ]);
}

function sendLog($title,$message)
{
    if($message == null){
        abort("Mesaj boş olamaz!",504);
    }
    global $limanData;
    return limanInternalRequest('sendLog',[
        "log_id" => $limanData[14],
        "message" => $message,
        "title" => $title
    ]);
}

if (is_file($limanData["functionsPath"])) {
    include($limanData["functionsPath"]);
}

if(!function_exists($limanData["function"])){
    abort("İstediğiniz sayfa bulunamadı",504);
}
echo call_user_func($limanData["function"]);