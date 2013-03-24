<?php
namespace Skeetr\Tests\Runtime;
use Skeetr\Tests\TestCase;
use Skeetr\Runtime\Manager;
use Skeetr\Runtime\OverrideInterface;

class ManagerTest extends TestCase {
    public function testRegister() {
        Manager::register('\Skeetr\Tests\Runtime\ExampleOverride');

        $function = new \ReflectionFunction('natcasesort');
        $this->assertFalse($function->isInternal());

        $this->assertSame('foo', natcasesort('foo'));
        $this->assertTrue(Manager::overrided('natcasesort'));
        $this->assertSame(1, ExampleOverride::$test);

        ExampleOverride::$test = 0;
        Manager::reset();

        $this->assertSame(1, ExampleOverride::$test);
   
        ExampleOverride::$test = 0;
        Manager::reset('\Skeetr\Tests\Runtime\ExampleOverride');

        $this->assertSame(1, ExampleOverride::$test);

        $this->assertTrue(false === Manager::reset('NotExists'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRegisterTwice() {
        Manager::register('\Skeetr\Runtime\Overrides\Cookie');
        Manager::register('\Skeetr\Runtime\Overrides\Cookie');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRegisterOther() {
        Manager::register('\Skeetr\Tests\TestCase');
    }

    /**
     * @expectedException ReflectionException
     */
    public function testRegisterMissing() {
        Manager::register('\Skeetr\Runtime\Overrides\Missing');
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
    static public $test = 0;

    static public function reset() {
        self::$test = 1;
    }

    final static function natcasesort($mandatory, $optionalNull = null, $optionalString = '2') {
        return $mandatory;
    }
}