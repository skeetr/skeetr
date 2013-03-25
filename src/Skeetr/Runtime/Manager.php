<?php
namespace Skeetr\Runtime;

class Manager {
    static private $registered = array();
    static private $functions = array();

    static public function auto($pattern = null) {
        if ( !$pattern ) $pattern = __DIR__ . '/Overrides/*.php';

        foreach ( glob($pattern) as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $file = str_replace(__DIR__, '', $file);

            $base = substr($file, 0, strlen($file) - strlen($ext) - 1);
            $class = '\\'. __NAMESPACE__ . str_replace(DIRECTORY_SEPARATOR, '\\', $base);
            static::register($class);
        }
    }

    static public function register($class) {
        if ( static::registered($class) ) {
            throw new \InvalidArgumentException('Override already loaded');
        }

        $reflection = new \ReflectionClass($class);
        if ( !$reflection->isSubclassOf('\Skeetr\Runtime\Override') ) {
            throw new \InvalidArgumentException(
                sprintf('%s not implements OverrideInterface', $class)
            );
        }

        $functions = array();
        foreach( $reflection->getMethods(\ReflectionMethod::IS_FINAL) as $method) {
            $function = $method->getName();
            if ( !function_exists($function) ) continue;

            $call = static::getCall($class, $function);
            if ( !skeetr_override_function(
                $call['function'], 
                $call['args'], 
                $call['code']
            )) {
                throw new \RuntimeException(
                    sprintf('Unable to override builtin function %s', $call['function'])
                );
            }

            $functions[$function] = 1;
        }
        
        if ( count($functions) == 0 ) {
            throw new \InvalidArgumentException(sprintf(
                'The class "%s" not contains any override function',
                $class
            ));
        }

        $class::reset();

        static::$functions = array_merge(static::$functions, $functions);
        static::$registered[$class] = 1;
    }

    static public function overrided($function) {
         if ( isset(static::$functions[$function]) ) return true;
        return false;       
    }

    static public function registered($class) {
        if ( isset(static::$registered[$class]) ) return true;
        return false;
    }

    static public function reset($class = null) {
        if ( $class && isset(static::$registered[$class]) ) return $class::reset();
        else if ( $class ) return false;

        foreach(static::$registered as $class => $registered) $class::reset();
        return true;
    }

    static public function values($class = null) {
        if ( $class && isset(static::$registered[$class]) ) {
            $tmp = explode('\\', $class);
            $key = strtolower(end($tmp));
            return array($key => $class::values());
        } else if ( $class ) return false;

        $values = array();
        foreach(static::$registered as $class => $registered) {
            $tmp = explode('\\', $class);
            $key = strtolower(end($tmp));
            $values[$key] = $class::values();
        }

        return $values;
    }

    static protected function getCall($class, $method) {
        $call = array();
        $args = array();

        foreach(static::readMethod($class, $method) as $arg) {
            $call[] = sprintf('$%s', $arg['name']);
            if ( !isset($arg['default']) ) $args[] = sprintf('$%s', $arg['name']);
            else $args[] = sprintf('$%s = %s', $arg['name'], $arg['default']);
        }

        return array(
            'function' => $method,
            'args' => implode(', ', $args),
            'code' => sprintf('return %s::%s(%s);', $class, $method, implode(', ', $call))
        );
    }

    static protected function readMethod($class, $method) {
        $reflection = new \ReflectionClass($class);
        $method = $reflection->getMethod($method);

        $args = array();
        foreach( $method->getParameters() as $param ) {
            $arg = array();
            $arg['name'] = $param->getName();
            if ( $param->isOptional() ) {
                $arg['default'] = var_export($param->getDefaultValue(), true);
            }

            $args[] = $arg;
        }

        return $args;
    }
}