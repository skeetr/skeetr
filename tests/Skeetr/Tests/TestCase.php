<?php
namespace Skeetr\Tests;
use Skeetr\Overrides\Cookie;
use Skeetr\Overrides\Header;
use Skeetr\Overrides\Session;

class TestCase extends \PHPUnit_Framework_TestCase {   
    public function setUp() {
        Header::reset();
        Cookie::reset();
        Session::reset();
    }

    public static function getResource($path) {
        return file_get_contents(__DIR__ . '/../../Resources/' . $path);
    }
   
}
