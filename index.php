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
set_exception_handler('customErrorHandler');
set_error_handler('customErrorHandler');

use Jenssegers\Blade\Blade;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Cookie\CookieJar;

$decrypted = openssl_decrypt($argv[2], 'aes-256-cfb8', shell_exec('cat ' . $argv[1]));

$limanData = json_decode(base64_decode(substr($decrypted, 16)), false, 512);

foreach ($limanData as $key => $item) {
    @$json = json_decode($item, true);
    $limanData[$key] = (json_last_error() == JSON_ERROR_NONE) ? $json : $item;
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
    return $limanData[5][$target];
}

function extension()
{
    $json = json_decode(file_get_contents(getPath("db.json")), true);
    return $json;
}

function server()
{
    global $limanData;
    return (object) $limanData[3];
}

// Translation disabled for now.
function __($str)
{
    global $limanData;
    $folder = dirname(dirname($limanData[0])) . "/lang";
    $file = $folder . "/" . $limanData[14] . ".json";
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
    $tempRequest = $limanData[7];
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
    global $limanData;
    return $limanData[9] . "/" . $target;
}

function respond($message, $code = "200")
{
    return json_encode([
        "message" => $message,
        "status" => $code
    ]);
}

function abort($message, $code = "200")
{
    global $limanData;
    ob_clean();
    if($limanData[2] != null){
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
    return $limanData[10] . '/' . $name . $args;
}

function view($name, $params = [])
{
    global $limanData;
    $blade = new Blade([dirname($limanData[0]), __DIR__ . "/views/"], "/tmp");
    return $blade->render($name, $params);
}

function requestReverseProxy($hostname,$port)
{
    global $limanData;
    $client = new Client([
        'verify' => false
    ]);
    try {
        $response = $client->request('POST', 'https://127.0.0.1/lmn/private/reverseProxyRequest', [
            'headers' => [
                'Accept'     => 'application/json',
            ],
            "multipart" => [
                [
                    "name" => "hostname",
                    "contents" => $hostname,
                ],
                [
                    "name" => "port",
                    "contents" => $port
                ],
                [
                    "name" => "token",
                    "contents" => $limanData[11]
                ]
            ],
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

function dispatchJob($function_name,$parameters = [])
{
    global $limanData;
    $client = new Client([
        'verify' => false
    ]);
    try {
        $response = $client->request('POST', 'https://127.0.0.1/lmn/private/dispatchJob', [
            'headers' => [
                'Accept'     => 'application/json',
            ],
            "multipart" => [
                [
                    "name" => "server_id",
                    "contents" => server()->id,
                ],
                [
                    "name" => "extension_id",
                    "contents" => $limanData[12]
                ],
                [
                    "name" => "function_name",
                    "contents" => $function_name,
                ],
                [
                    "name" => "parameters",
                    "contents" => json_encode($parameters)
                ],
                [
                    "name" => "token",
                    "contents" => $limanData[11]
                ]
            ],
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

function getJobList($function_name)
{
    global $limanData;
    $client = new Client([
        'verify' => false
    ]);
    try {
        $response = $client->request('POST', 'https://127.0.0.1/lmn/private/getJobList', [
            'headers' => [
                'Accept'     => 'application/json',
            ],
            "multipart" => [
                [
                    "name" => "server_id",
                    "contents" => server()->id,
                ],
                [
                    "name" => "extension_id",
                    "contents" => $limanData[12]
                ],
                [
                    "name" => "function_name",
                    "contents" => $function_name,
                ],
                [
                    "name" => "token",
                    "contents" => $limanData[11]
                ]
            ],
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

function publicPath($path)
{
    global $limanData;
    return str_replace("eklenti2", "eklenti", $limanData[9]) . "/public/" . base64_encode($path);
}

function externalAPI($target, $extension_id, $server_id = null, $params=[])
{
    global $limanData;
    $client = new Client([
        'verify' => false
    ]);
    $extraParams = [];
    foreach($params as $key => $value){
        $extraParams[] = [
            "name" => $key,
            "contents" => $value
        ];
    }
    try {
        $response = $client->request('POST', 'https://127.0.0.1/lmn/private/extensionApi', [
            'headers' => [
                'Accept'     => 'application/json',
            ],
            "multipart" => array_merge($extraParams, [
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

function runCommand($command)
{
    global $limanData;
    $client = new Client([
        'verify' => false
    ]);
    try {
        $response = $client->request('POST', 'https://127.0.0.1/lmn/private/runCommandApi', [
            'headers' => [
                'Accept'     => 'application/json',
            ],
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
        return trim($response->getBody()->getContents());
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

function runScript($name,$parameters = "",$sudo = true)
{
    global $limanData;
    $client = new Client([
        'verify' => false
    ]);
    try {
        $response = $client->request('POST', 'https://127.0.0.1/lmn/private/runScriptApi', [
            'headers' => [
                'Accept'     => 'application/json',
            ],
            "multipart" => [
                [
                    "name" => "scriptName",
                    "contents" => $name,
                ],
                [
                    "name" => "server_id",
                    "contents" => server()->id,
                ],
                [
                    "name" => "extension_id",
                    "contents" => $limanData[12],
                ],
                [
                    "name" => "runAsRoot",
                    "contents" => $sudo ? "yes" : "no",
                ],
                [
                    "name" => "parameters",
                    "contents" => $parameters
                ],
                [
                    "name" => "token",
                    "contents" => $limanData[11]
                ]
            ],
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

function putFile($localPath, $remotePath)
{
    global $limanData;
    $client = new Client([
        'verify' => false
    ]);
    try {
        $response = $client->request('POST', 'https://127.0.0.1/lmn/private/putFileApi', [
            'headers' => [
                'Accept'     => 'application/json',
            ],
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

function sudo()
{
    $pass64 = base64_encode(extensionDb("clientPassword")."\n");
    return 'echo ' . $pass64 .' | base64 -d | sudo -S -p " " id 2>/dev/null 1>/dev/null; sudo ';
}


function session($key = null)
{
    global $limanData;
    if(array_key_exists($key,$limanData[16])){
        return $limanData[16][$key];
    }
    if($key == null){
        return $limanData[16];
    }
    return null;
}

function putSession($key,$value)
{
    global $limanData;
    $cookieJar = CookieJar::fromArray([
        'liman_session' => $limanData[15]
    ], '127.0.0.1');
    $client = new Client([
        'verify' => false,
        'cookies' => $cookieJar
    ]);
    try {
        $response = $client->request('POST', 'https://127.0.0.1/lmn/private/putSession', [
            'headers' => [
                'Accept'     => 'application/json',
            ],
            "multipart" => [
                [
                    "name" => "session_key",
                    "contents" => $key,
                ],
                [
                    "name" => "value",
                    "contents" => json_encode($value)
                ]
            ],
        ]);
        $limanData[16][$key] = $value;
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

function getFile($localPath, $remotePath)
{
    global $limanData;
    $client = new Client([
        'verify' => false
    ]);
    try {
        $response = $client->request('POST', 'https://127.0.0.1/lmn/private/getFileApi', [
            'headers' => [
                'Accept'     => 'application/json',
            ],
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

function openTunnel($remote_host, $remote_port, $username, $password)
{
    global $limanData;
    $client = new Client([
        'verify' => false
    ]);
    try {
        $response = $client->request('POST', 'https://127.0.0.1/lmn/private/openTunnel', [
            'headers' => [
                'Accept'     => 'application/json',
            ],
            "multipart" => [
                [
                    "name" => "server_id",
                    "contents" => server()->id,
                ],
                [
                    "name" => "remote_host",
                    "contents" => $remote_host
                ],
                [
                    "name" => "remote_port",
                    "contents" => $remote_port
                ],
                [
                    "name" => "username",
                    "contents" => $username
                ],
                [
                    "name" => "password",
                    "contents" => $password
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

function stopTunnel($remote_host, $remote_port)
{
    global $limanData;
    $client = new Client([
        'verify' => false
    ]);
    try {
        $response = $client->request('POST', 'https://127.0.0.1/lmn/private/stopTunnel', [
            'timeout' => 0.1,
            'headers' => [
                'Accept'     => 'application/json',
            ],
            "multipart" => [
                [
                    "name" => "server_id",
                    "contents" => server()->id,
                ],
                [
                    "name" => "remote_host",
                    "contents" => $remote_host
                ],
                [
                    "name" => "remote_port",
                    "contents" => $remote_port
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
    return dirname(dirname($limanData[0])) . "/" . $filename;
}

function can($name)
{
    global $limanData;
    if ($limanData[13] == "admin") {
        return true;
    }
    return in_array($name, $limanData[13]);
}

$functions = get_defined_functions();
$keys = array_keys($functions['user']);
$last_index = array_pop($keys);

// Functions PHP
if (is_file($limanData[0])) {
    include($limanData[0]);
}

$functions = get_defined_functions();
$new_functions = array_slice($functions['user'], $last_index + 1);
if ($limanData[2] == null) {
    set_error_handler(function () {
        return "error";
    });
    echo call_user_func($limanData[8]);
    restore_error_handler();
} else {
    shell_exec("mkdir /tmp/liman" . $limanData[12]);
    $blade = new Blade([
        dirname($limanData[0]),
        __DIR__ . "/views/"
    ], "/tmp/liman" . $limanData[12]);
    echo $blade->render($limanData[2], [
        "data" => $limanData[6]
    ]);
}
