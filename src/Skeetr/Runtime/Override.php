<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <mcuadros@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr\Runtime;

abstract class Override
{
    public static function reset() { }
    public static function values()
    {
        return get_class_vars(get_called_class());
    }
}
