<?php
namespace Skeetr\Tests\Runtime;
use Skeetr\Tests\TestCase;
use Skeetr\Runtime\Manager;
use Skeetr\Runtime\OverrideInterface;

class ManagerTest extends TestCase {
    public function testLoad() {
        Manager::load('\Skeetr\Tests\Runtime\ExampleOverride');

        $function = new \ReflectionFunction('natcasesort');
        $this->assertFalse($function->isInternal());

        $this->assertSame('foo', natcasesort('foo'));
    }

    public function testReadMethod() {
        $args = ManagerMock::readMethod(
            '\Skeetr\Tests\Runtime\ExampleOverride', 'natcasesort'
        );

        $this->assertSame('mandatory', $args[0]['name']);
        $this->assertSame('optionalNull', $args[1]['name']);
        $this->assertSame('NULL', $args[1]['default']);
        $this->assertSame('optionalString', $args[2]['name']);
        $this->assertSame("'2'", $args[2]['default']);
    }

    public function testGetCall() {
        $call = ManagerMock::getCall(
            '\Skeetr\Tests\Runtime\ExampleOverride', 'natcasesort'
        );

        $this->assertSame('natcasesort', $call['function']);

        $args = '$mandatory, $optionalNull = NULL, $optionalString = \'2\'';
        $this->assertSame($args, $call['args']);

        $code = 'return \Skeetr\Tests\Runtime\ExampleOverride::natcasesort($mandatory, $optionalNull, $optionalString);';
        $this->assertSame($code, $call['code']);

    }
}

class ManagerMock extends Manager {
    static public function readMethod($class, $method) {
        return parent::readMethod($class, $method);
    }

    static public function getCall($class, $method) {
        return parent::getCall($class, $method);
    }
}


class ExampleOverride implements OverrideInterface {
    final static function natcasesort($mandatory, $optionalNull = null, $optionalString = '2') {
        return $mandatory;
    }
}