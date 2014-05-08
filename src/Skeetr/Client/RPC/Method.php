<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <mcuadros@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr\Client\RPC;

abstract class Method
{
    /**
     * Gets called when a request for the registered method name is submitted
     *
     * @param  Request $job
     * @return array
     */
    abstract public function execute(Request $request);
}
