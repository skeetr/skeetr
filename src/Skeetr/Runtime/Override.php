<?php
namespace Skeetr\Runtime;

abstract class Override {
    static public function reset() { }
    static public function values() {
        return get_class_vars(get_called_class());
    }
}