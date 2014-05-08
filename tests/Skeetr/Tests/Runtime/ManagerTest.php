<?php
namespace Skeetr\Tests\Runtime;

use Skeetr\Tests\TestCase;
use Skeetr\Runtime\Manager;
use Skeetr\Runtime\Override;

class ManagerTest extends TestCase
{
    public function testAuto()
    {
        ManagerMock::$loaded = false;
        ManagerMock::$registered = array();
        ManagerMock::auto();

        $this->assertSame(3, count(ManagerMock::$registered));
        $this->assertTrue(class_exists(key(ManagerMock::$registered)));
    }

    public function testRegisterResetAndValues()
    {
        $this->assertFalse(Manager::overridden('natcasesort'));

        Manager::register('\Skeetr\Tests\Runtime\Example');

        $function = new \ReflectionFunction('natcasesort');
        $this->assertFalse($function->isInternal());

        $this->assertSame('foo', natcasesort('foo'));
        $this->assertTrue(Manager::overridden('natcasesort'));

        $this->assertSame(1, Example::$test);

        Example::$test = 0;
        Manager::reset();

        $this->assertSame(1, Example::$test);

        Example::$test = 0;
        Manager::reset('\Skeetr\Tests\Runtime\Example');

        $this->assertSame(1, Example::$test);
        $this->assertTrue(false === Manager::reset('NotExists'));

        $expected = array('example' => array('test' => 1));
        $this->assertSame($expected, Manager::values('\Skeetr\Tests\Runtime\Example'));

        $values = Manager::values('\Skeetr\Tests\Runtime\Example');
        $this->assertSame(1, $values['example']['test']);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRegisterTwice()
    {
        Manager::register('\Skeetr\Runtime\Overrides\Cookie');
        Manager::register('\Skeetr\Runtime\Overrides\Cookie');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRegisterOther()
    {
        Manager::register('\Skeetr\Tests\TestCase');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRegisterEmpty()
    {
        Manager::register('\Skeetr\Tests\Runtime\ExampleEmpty');
    }

    /**
     * @expectedException ReflectionException
     */
    public function testRegisterMissing()
    {
        Manager::register('\Skeetr\Runtime\Overrides\Missing');
    }

    public function testReadMethod()
    {
        $args = ManagerMock::readMethod(
            '\Skeetr\Tests\Runtime\Example', 'natcasesort'
        );

        $this->assertSame('mandatory', $args[0]['name']);
        $this->assertSame('optionalNull', $args[1]['name']);
        $this->assertSame('NULL', $args[1]['default']);
        $this->assertSame('optionalString', $args[2]['name']);
        $this->assertSame("'2'", $args[2]['default']);
    }

    public function testGetCall()
    {
        $call = ManagerMock::getCall(
            '\Skeetr\Tests\Runtime\Example', 'natcasesort'
        );

        $this->assertSame('natcasesort', $call['function']);

        $args = '$mandatory, $optionalNull = NULL, $optionalString = \'2\'';
        $this->assertSame($args, $call['args']);

        $code = 'return \Skeetr\Tests\Runtime\Example::natcasesort($mandatory, $optionalNull, $optionalString);';
        $this->assertSame($code, $call['code']);
    }
}

class ManagerMock extends Manager
{
    public static $registered = array();
    public static $loaded = false;

    public static function register($class)
    {
        if ( self::registered($class) ) {
            throw new \InvalidArgumentException('Override already loaded');
        }

        static::$registered[$class] = 1;

        return;
    }

    public static function readMethod($class, $method)
    {
        return parent::readMethod($class, $method);
    }

    public static function getCall($class, $method)
    {
        return parent::getCall($class, $method);
    }
}

class Example extends Override
{
    public static $test = 0;

    public static function reset()
    {
        self::$test = 1;
    }

    final public static function natcasesort($mandatory, $optionalNull = null, $optionalString = '2')
    {
        return $mandatory;
    }
}

class ExampleEmpty extends Override
{
    public static $test = 0;

    public static function reset()
    {
        self::$test = 1;
    }
}
