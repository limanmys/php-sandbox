<?php
ob_start();
require_once(__DIR__ . "/vendor/autoload.php");

function customErrorHandler($exception, $err_str=null) {
    if($err_str){
        if($exception == 8){
            return;
        }
        $message = __($err_str);
    }else{
        $message = __($exception->getMessage());
    }
    abort($message, 201);
}

set_exception_handler('customErrorHandler');
set_error_handler('customErrorHandler');

use Jenssegers\Blade\Blade;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use mervick\aesEverywhere\AES256;

$decrypted = AES256::decrypt($argv[2], shell_exec('cat ' . $argv[1]));

$limanData = json_decode($decrypted, true, 512);

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

$cachedLMNTranslations = [];

function __($str, $attributes = [])
{
    global $limanData, $cachedLMNTranslations;
    if ($cachedLMNTranslations === null){
        return $str;
    } 

    if ($cachedLMNTranslations == []){
        $translationFiles = [
            [
                "folder" => dirname(dirname($limanData["functionsPath"])) . "/lang",
            ],
            [
                "folder" => __DIR__ . "/lang",
            ]
        ];
        foreach ($translationFiles as $translationFile){
            $file = $translationFile['folder'] . "/" . $limanData["locale"] . ".json";
            if (is_dir($translationFile['folder']) && is_file($file)) {
                $cachedLMNTranslations = array_merge(
                    $cachedLMNTranslations,
                    json_decode(file_get_contents($file), true)
                );
            }
        }
    }
    
    return makeReplacements((array_key_exists($str, $cachedLMNTranslations)) ? $cachedLMNTranslations[$str] : $str, $attributes);
}

function makeReplacements($line, array $replace)
{
    if (empty($replace)) {
        return $line;
    }

    $replace = sortReplacements($replace);

    foreach ($replace as $key => $value) {
        $line = str_replace(
            [':'.$key, ':'.Str::upper($key), ':'.Str::ucfirst($key)],
            [$value, Str::upper($value), Str::ucfirst($value)],
            $line
        );
    }

    return $line;
}

function sortReplacements(array $replace)
{
    return (new Collection($replace))->sortBy(function ($value, $key) {
        return mb_strlen($key) * -1;
    })->all();
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

function getToken()
{
    global $limanData;
    return $limanData["token"];
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
    return $limanData["navigationRoute"] . '/' . $name . $args;
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
    return renderEngineRequest($function_name,"backgroundJob",$parameters);
}

function renderEngineRequest($function,$url,$parameters = [], $server_id = null, $extension_id = null)
{
    global $limanData;
    $client = new Client(['verify' => false ]);
    $parameters["server_id"] = $server_id ? $server_id : server()->id;
    $parameters["extension_id"] = $extension_id ? $extension_id : $limanData["extension"]["id"];
    $parameters["token"] = $limanData["token"];
    $parameters["lmntargetFunction"] = $function;

    try {
        $response = $client->request('POST', "https://127.0.0.1:5454/$url", [
            "form_params" => $parameters,
        ]);
        return $response->getBody()->getContents();
    } catch (GuzzleException $exception) {
        abort($exception->getMessage(), 201);
    }
}

function externalAPI($target, $target_extension_name, $target_server_id,  $params = [])
{
    return renderEngineRequest($target,"externalAPI",$params, $target_server_id,$target_extension_name);
}

// @deprecated
function getJobList($function_name)
{
    return [];
}

function publicPath($path)
{
    global $limanData;
    return $limanData["publicPath"] . base64_encode($path);
}

function getLicense() {
    global $limanData;
    return $limanData["license"];
}

function runCommand($command)
{
    return renderEngineRequest('','runCommand',[
        "command" => $command
    ]);
}

function runScript($name,$parameters = " ",$sudo = true)
{
    return renderEngineRequest('','runScript',[
        "local_path" => getPath("scripts/$name"),
        "root" => $sudo ? "yes" : "no",
        "parameters" => trim($parameters) ? $parameters : " "
    ]);
}

function putFile($localPath, $remotePath)
{
    return renderEngineRequest('','putFile',[
        "local_path" => $localPath,
        "remote_path" => $remotePath,
    ]);
}

function executeOutsideCommand($connectionType, $username,$password,$remote_host,$remote_port,$command)
{
    return renderEngineRequest('','runOutsideCommand',[
        "connection_type" => $connectionType,
        "username" => $username,
        "password" => $password,
        "remote_host" => $remote_host,
        "remote_port" => $remote_port,
        "command" => $command
    ]);
}


function getServerKeyType()
{
    global $limanData;
    return $limanData["key_type"];
}

function serverHasKey()
{
    global $limanData;
    return $limanData["key_type"] != "";
}

function sudo()
{
    global $limanData;
    if($limanData["key_type"] == "ssh_certificate"){
        return "sudo ";
    } else if ($limanData["key_type"] == "ssh"){
        $pass64 = base64_encode(extensionDb("clientPassword")."\n");
        return 'echo ' . $pass64 .' | base64 -d | sudo -S -p " " id 2>/dev/null 1>/dev/null; sudo ';
    }
    return "";
}


function getFile($localPath, $remotePath)
{
    return renderEngineRequest('','getFile',[
        "local_path" => $localPath,
        "remote_path" => $remotePath,
    ]);
}

function openTunnel($remote_host, $remote_port, $username, $password)
{
    return renderEngineRequest('','openTunnel',[
        "remote_host" => $remote_host,
        "remote_port" => $remote_port,
        "username" => $username,
        "password" => $password
    ]);
}

function keepTunnelAlive($remote_host,$remote_port,$username)
{
    return renderEngineRequest('','keepTunnelAlive',[
        "remote_host" => $remote_host,
        "remote_port" => $remote_port,
        "username" => $username
    ]);
}

// @deprecated
function stopTunnel($remote_host, $remote_port)
{
    return true;
}

function getPath($filename = null)
{
    global $limanData;
    return dirname(dirname($limanData["functionsPath"])) . "/" . $filename;
}

function getVariable($key)
{
    global $limanData;
    if(array_key_exists("variables",$limanData)){
        return $limanData["variables"][$key];
    }else{
        return null;
    }
}

function can($name)
{
    global $limanData;
    if ($limanData["user"]["status"] == "1") {
        return true;
    }
    return in_array($name, $limanData["permissions"]);
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
    global $limanData;
    if($message == null){
        abort("Mesaj boş olamaz!",504);
    }
    if($title == "MAIL_TAG"){
        $message = $limanData["extension"]["id"] . "-" . server()->id . "-" . $message;
    }

    return renderEngineRequest('','sendLog',[
        "log_id" => $limanData["log_id"],
        'message' => base64_encode($message),
        'title' => base64_encode($title),
    ]);
}

if (is_file($limanData["functionsPath"])) {
    include($limanData["functionsPath"]);
}

if (is_file(getPath('vendor/autoload.php'))) {
    require_once getPath('vendor/autoload.php');
    if(class_exists('App\\App') && method_exists('App\\App', 'init')){
        (new App\App())->init();
    }
}

if(function_exists($limanData["function"])){
    echo call_user_func($limanData["function"]);
}else if(is_file(getPath("routes.php"))){
    $routes = include getPath('routes.php');
    if(isset($routes[$limanData["function"]])){
        $destination = explode('@', $routes[$limanData["function"]]);
        $class = 'App\\Controllers\\' . $destination[0];
        if (!class_exists($class)) {
			$class = $destination[0];
        }
        echo (new $class())->{$destination[1]}();
    }else{
        abort("İstediğiniz sayfa bulunamadı",504);
    }
}else{
    abort("İstediğiniz sayfa bulunamadı",504);
}
