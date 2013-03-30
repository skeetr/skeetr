<?php
use Skeetr\Client;
use Skeetr\Gearman\Worker;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require __DIR__ . '/../vendor/autoload.php';

$worker = new Worker();
$worker->addServer('front-1.iunait.es', 4730);

$logger = new Logger('debugger');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));

$client = new Client($logger, $worker);
$client->setCallback(function($request, $response) use ($logger) { 
    session_start();
    if ( !isset($_SESSION['count']) ) $_SESSION['count'] = 0;
    $_SESSION['count']++;

    header('Foo: boo');
    setcookie('foo', 'bar');
    setcookie('baz', 'qux');

    return 'test' . $_SESSION['count'];
});


$client->work();