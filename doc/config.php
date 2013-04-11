<?php
require __DIR__ . '/../vendor/autoload.php';

if ( !class_exists('\Sami\Sami') ) exit();

use Sami\Sami;
use Symfony\Component\Finder\Finder;

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->in(__DIR__ . '/../src/');

return new Sami($iterator, array(
    'title' => 'Skeetr API',
    'build_dir' => __DIR__ . '/build',
    'cache_dir' => __DIR__ . '/cache',
    'default_opened_level' => 2,
));
