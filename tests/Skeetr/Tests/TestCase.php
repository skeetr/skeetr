<?php
namespace Skeetr\Tests;
use Skeetr\Runtime\Manager;

class TestCase extends \PHPUnit_Framework_TestCase {   
    public function setUp() {
        Manager::reset();
    }

    public static function getResource($path) {
        return file_get_contents(__DIR__ . '/../../Resources/' . $path);
    }
}
