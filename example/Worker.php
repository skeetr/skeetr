<?php
use Skeetr\Client;
use Skeetr\Gearman\Worker;
use Skeetr\HTTP\Response;
use Skeetr\Overrides\Session;
use Skeetr\Overrides\Header;
use Skeetr\Overrides\Cookie;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require __DIR__ . '/../vendor/autoload.php';

Header::register();
Cookie::register();
Session::register();

$worker = new Worker();
$worker->addServer('front-1.iunait.es', 4730);

$logger = new Logger('debugger');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));

$client = new Client($logger, $worker);
$client->setCallback(function($request) use ($logger) { 
    session_start();
    if ( !isset($_SESSION['count']) ) $_SESSION['count'] = 0;
    $_SESSION['count']++;

    $response = new Response();
    $response->setContentType('text/html');
    $response->setBody('test' . $_SESSION['count']);

    Session::configure($response);
    Cookie::configure($response);
    Header::configure($response);
    return (string)$response;
});


$client->work();