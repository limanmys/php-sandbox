<?php
ob_start();
require_once(__DIR__ . "/vendor/autoload.php");

use eftec\bladeone\BladeOne as Blade;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use mervick\aesEverywhere\AES256;
use Liman\Toolkit\RemoteTask\TaskManager;

function customErrorHandler($exception, $err_str = null, $errfile = null, $errline = null, $errcontext = [])
{
    if ($err_str) {
        if ($exception == 8) {
            return;
        }

        $message = __($err_str);
        //$message = __($err_str) . " \n Error File: " . $errfile . " \n Error Line: " . $errline . " \n Stacktrace: " . json_encode($errcontext);
    } else {
        $message = __($exception->getMessage());
        //$message = __($exception->getMessage()). " \n Error File: " . $exception->getFile() . " \n Error Line: " . $exception->getLine(). " \n Stacktrace: " . $exception->getTraceAsString();
    }

    abort($message, 201);
}

set_exception_handler('customErrorHandler');
set_error_handler('customErrorHandler');

$decrypted = AES256::decrypt($argv[2], shell_exec('cat ' . $argv[1]));

$limanData = json_decode((string) $decrypted, true, 512);

foreach ($limanData as $key => $item) {
    if (is_string($item)) {
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

function extensionDb($target, $set = null)
{
    global $limanData;
    if ($set) {
        $limanData["settings"][$target] = $set;

        return renderEngineRequest('', 'setExtensionDb', [
            "target" => $target,
            "new_param" => $set
        ]);
    }

    if (isset($limanData["settings"][$target])) {
        if (stringIsJson($limanData["settings"][$target])) {
            return json_decode($limanData["settings"][$target], true);
        }
        return $limanData["settings"][$target];
    }

    return "";
}

function extension()
{
    return json_decode(file_get_contents(getPath("db.json")), true);
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

$cachedTranslations = [];

function __($str, $attributes = [])
{
    global $limanData, $cachedTranslations;
    if ($cachedTranslations === null) {
        return $str;
    }

    if ($cachedTranslations == []) {
        $translationFiles = [
            [
                "folder" => dirname(dirname((string) $limanData["functionsPath"])) . "/lang",
            ],
            [
                "folder" => __DIR__ . "/lang",
            ]
        ];
        foreach ($translationFiles as $translationFile) {
            $file = $translationFile['folder'] . "/" . $limanData["locale"] . ".json";
            if (is_dir($translationFile['folder']) && is_file($file)) {
                $cachedTranslations = array_merge(
                    $cachedTranslations,
                    json_decode(file_get_contents($file), true)
                );
            }
        }
    }

    return makeReplacements((array_key_exists($str, $cachedTranslations)) ? $cachedTranslations[$str] : $str, $attributes);
}

function makeReplacements($line, array $replace)
{
    if (empty($replace)) {
        return $line;
    }

    $replace = sortReplacements($replace);

    foreach ($replace as $key => $value) {
        $line = str_replace(
            [':' . $key, ':' . Str::upper($key), ':' . Str::ucfirst($key)],
            [$value, Str::upper($value), Str::ucfirst($value)],
            (string) $line
        );
    }

    return $line;
}

function sortReplacements(array $replace)
{
    return (new Collection($replace))->sortBy(static function ($value, $key) {
        return mb_strlen($key) * -1;
    })->all();
}

function request($target = null)
{
    global $limanData;
    $tempRequest = $limanData["requestData"];
    if ($target) {
        if (isset($tempRequest[$target])) {
            return html_entity_decode((string) $tempRequest[$target]);
        } else {
            return "";
        }
    }

    return $tempRequest;
}

function API($target)
{
    return "/engine/" . $target;
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
        "status" => (int) $code
    ]);
}

function abort($message, $code = "200")
{
    global $limanData;
    ob_clean();
    if (!(bool) $limanData["ajax"]) {
        echo view('alert', [
            "type" => (int) $code == 200 ? "success" : "danger",
            "title" => (int) $code == 200 ? __("Başarılı") : __("Hata"),
            "message" => $message
        ]);
    } else {
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
            $args .= sprintf('&%s=%s', $key, $param);
        }
    }

    return str_replace("//", "/", (string) $limanData["navigationRoute"] . '/' . $name . $args);
}

function view($name, $params = [])
{
    global $limanData;
    $path = "/tmp/" . $limanData["extension"]["id"];
    if (!is_dir($path)) {
        mkdir($path);
    }

    $blade = new Blade([dirname((string) $limanData["functionsPath"]), __DIR__ . "/views/"], $path);
    return $blade->run($name, $params);
}

function limanInternalRequest($url, $data, $server_id = null, $extension_id = null)
{
    global $limanData;
    $client = new Client([
        'verify' => false
    ]);
    $extraParams = [];
    foreach ($data as $key => $value) {
        $extraParams[] = [
            "name" => $key,
            "contents" => $value
        ];
    }

    $server_id = ($server_id) ? $server_id : server()->id;
    $extension_id = ($extension_id) ? $extension_id : $limanData["extension"]["id"];
    try {
        $response = $client->request('POST', sprintf('https://127.0.0.1/lmn/private/%s', $url), [
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
    } catch (GuzzleException $guzzleException) {
        if ($guzzleException->getResponse() && $guzzleException->getResponse()->getStatusCode() > 400) {
            $message =
                json_decode((string) $guzzleException->getResponse()->getBody()->getContents())
                ->message;
        } else {
            $message = $guzzleException->getMessage();
        }

        abort($message, 201);
    }
}

function requestReverseProxy($hostname, $port)
{
    return limanInternalRequest('reverseProxyRequest', [
        "hostname" => $hostname,
        "port" => $port
    ]);
}

function dispatchJob($function_name, $parameters = [])
{
    return renderEngineRequest($function_name, "backgroundJob", $parameters);
}

function renderEngineRequest($function, $url, $parameters = [], $server_id = null, $extension_id = null, $type = 'POST')
{
    global $limanData;
    $client = new Client(['verify' => false]);
    $parameters["server_id"] = $server_id ? $server_id : server()->id;
    $parameters["extension_id"] = $extension_id ? $extension_id : $limanData["extension"]["id"];
    $parameters["token"] = $limanData["token"];
    $parameters["lmntargetFunction"] = $function;

    try {
        $response = $client->request($type, sprintf('https://127.0.0.1:2806/%s', $url), [
            "form_params" => $parameters,
        ]);
        return $response->getBody()->getContents();
    } catch (GuzzleException $guzzleException) {
        abort($guzzleException->getMessage(), 201);
    }
}

function externalAPI($target, $target_extension_name, $target_server_id, $params = [])
{
    return renderEngineRequest($target, "externalAPI", $params, $target_server_id, $target_extension_name);
}

// @deprecated
function getJobList($function_name)
{
    return [];
}

function publicPath($path)
{
    global $limanData;
    return $limanData["publicPath"] . $path;
}

function getLicense()
{
    global $limanData;
    return $limanData["license"];
}

function runCommand($command)
{
    return renderEngineRequest('', 'command', [
        "command" => $command
    ]);
}

function runScript($name, $parameters = " ", $sudo = true)
{
    return renderEngineRequest('', 'script', [
        "local_path" => getPath(sprintf('scripts/%s', $name)),
        "root" => $sudo ? "yes" : "no",
        "parameters" => trim((string) $parameters) !== '' && trim((string) $parameters) !== '0' ? $parameters : " "
    ]);
}

function putFile($localPath, $remotePath)
{
    return renderEngineRequest('', 'putFile', [
        "local_path" => $localPath,
        "remote_path" => $remotePath,
    ]);
}

function executeOutsideCommand($connectionType, $username, $password, $remote_host, $remote_port, $command, $disconnect = false)
{
    return renderEngineRequest('', 'outsideCommand', [
        "connection_type" => $connectionType,
        "username" => $username,
        "password" => $password,
        "remote_host" => $remote_host,
        "remote_port" => $remote_port,
        "command" => $command,
        "disconnect" => $disconnect
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

    if ($limanData["key_type"] == "ssh_certificate") {
        return "sudo ";
    }

    return 'sudo -p "liman-pass-sudo" ';
}


function getFile($localPath, $remotePath)
{
    return renderEngineRequest('', 'getFile', [
        "local_path" => $localPath,
        "remote_path" => $remotePath,
    ]);
}

function openTunnel($remote_host, $socket_port, $username, $password, $ssh_port = 22)
{
    return renderEngineRequest('', 'openTunnel', [
        "remote_host" => $remote_host,
        "remote_port" => $socket_port,
        "username" => $username,
        "password" => $password,
        "ssh_port" => $ssh_port,
    ]);
}

function keepTunnelAlive($remote_host, $remote_port, $username)
{
    return renderEngineRequest('', 'keepTunnelAlive', [
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
    return dirname(dirname((string) $limanData["functionsPath"])) . "/" . $filename;
}

function getVariable($key)
{
    global $limanData;

    if (isset($limanData["variables"]) && isset($limanData["variables"][$key])) {
        return $limanData["variables"][$key];
    } else {
        return "";
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

function sendNotification($title, $message, $type = "notify")
{
    return limanInternalRequest('sendNotification', [
        "title" => $title,
        "message" => $message,
        "type" => $type
    ]);
}

function sendMail($to, $subject, $content, array $attachments = [], bool $templated = false)
{
    return limanInternalRequest('sendMail', [
        "to" => json_encode($to),
        "subject" => $subject,
        "content" => base64_encode((string) $content),
        "attachments" => json_encode($attachments),
        "templated" => $templated
    ]);
}

function sendLog($title, $message, $data = [])
{
    global $limanData;
    if ($message == null) {
        abort("Mesaj boş olamaz!", 504);
    }

    if ($title == "MAIL_TAG") {
        $message = $limanData["extension"]["id"] . "-" . server()->id . "-" . $message;
    }

    return renderEngineRequest('', 'sendLog', [
        "log_id" => $limanData["log_id"],
        'message' => base64_encode((string) $message),
        'title' => base64_encode((string) $title),
        'data' => json_encode($data)
    ]);
}

function runTask()
{
    $taskName = request('name');
    $attributes = (array) json_decode((string) request('attributes'));
    return respond(TaskManager::get($taskName, $attributes)->run());
}

function checkTask()
{
    $taskName = request('name');
    $attributes = (array) json_decode((string) request('attributes'));
    return respond(TaskManager::get($taskName, $attributes)->check());
}

function download($path)
{
    global $limanData;
    $object = [
        "server_id" => server()->id,
        "extension_id" => $limanData["extension"]["id"],
        "token" => $limanData["token"],
        "path" => $path
    ];
    return "/engine/download/?" . http_build_query($object);
}

function stringIsJson(string $string)
{
    if ($string[0] == "{" || $string[0] == "[") {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
    return false;
}

if (is_file($limanData["functionsPath"])) {
    include($limanData["functionsPath"]);
}

if (is_file(getPath('vendor/autoload.php'))) {
    require_once getPath('vendor/autoload.php');
    if (class_exists('App\\App') && method_exists('App\\App', 'init')) {
        (new App\App())->init();
    }
}

if (function_exists($limanData["function"])) {
    echo call_user_func($limanData["function"]);
} elseif (is_file(getPath("routes.php"))) {
    $routes = include getPath('routes.php');
    if (isset($routes[$limanData["function"]])) {
        $destination = explode('@', (string) $routes[$limanData["function"]]);
        $class = 'App\\Controllers\\' . $destination[0];
        if (!class_exists($class)) {
            $class = $destination[0];
        }

        echo (new $class())->{$destination[1]}();
    } else {
        abort("İstediğiniz sayfa bulunamadı", 504);
    }
} else {
    abort("İstediğiniz sayfa bulunamadı", 504);
}

function indexCronjobs()
{
    $response = renderEngineRequest('', 'cronjobs', [], null, null, 'GET');

    return (array) json_decode($response);
}   

function deleteCronjob($id)
{
    $response = renderEngineRequest('', sprintf('cronjobs/%s', $id), [], null, null, 'DELETE');
    
    return str_replace('"', '', $response);
}

function createCronjob($payload, $day, $time, $target)
{
    $params = [
        'payload' => $payload,
        'user_id' => user()->id,
        'day' => $day,
        'time' => $time,
        'target' => $target,
    ];

    $response = renderEngineRequest($target, 'cronjobs', $params);

    return str_replace('"', '', $response); 
}

function indexQueue($queueType)
{
    global $limanData;
    $params = [
        'user_id' => user()->id,
        'server_id' => server()->id,
        'extension_id' => $limanData['extension']['id'],
        'queue_type' => $queueType,
    ];
    $response = renderEngineRequest('', 'queue', $params, null, null, 'GET');

    return (array) json_decode($response);
}

function deleteQueue($id, $queueType)
{
    global $limanData;
    $params = [
        'user_id' => user()->id,
        'server_id' => server()->id,
        'extension_id' => $limanData['extension']['id'],
        'queue_type' => $queueType,
    ];
    
    $response = renderEngineRequest('', sprintf('queue/%s', $id), $params, null, null, 'DELETE');
    
    return str_replace('"', '', $response);
}

function createQueue($type, $payload, $target)
{
    global $limanData;

    $params = [
        'type' => $type,
        'payload' => $payload,
        'user_id' => user()->id,
        'server_id' => server()->id,
        'extension_id' => $limanData['extension']['id'],
        'target' => $target,
    ];

    $response = renderEngineRequest($target, 'queue', $params);

    return str_replace('"', '', $response); 
}