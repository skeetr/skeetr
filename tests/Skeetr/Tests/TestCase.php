<?php
namespace Skeetr\Tests;

class TestCase extends \PHPUnit_Framework_TestCase
{   
    public static function getResource($path) {
        return file_get_contents(__DIR__ . '/../../Resources/' . $path);
    }
   
}
