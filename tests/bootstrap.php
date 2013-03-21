<?php
$loader = require __DIR__.'/../vendor/autoload.php';
$loader->add('Skeetr\Tests', __DIR__);
$loader->add('Skeetr\Mocks', __DIR__);

\Skeetr\Overrides\Header::register();
\Skeetr\Overrides\Cookie::register();
\Skeetr\Overrides\Session::register();