<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <maximo@yunait.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr\Runtime;

abstract class Override {
    static public function reset() { }
    static public function values() {
        return get_class_vars(get_called_class());
    }
}