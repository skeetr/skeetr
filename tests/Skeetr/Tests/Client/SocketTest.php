<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <mcuadros@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr\Tests;

use Skeetr\Client\Socket;

class SocketTest extends TestCase
{
    public function testConnect()
    {
        $file = '/tmp/' . rand(0,10000) . '.sock';

        $socket = new Socket($file);
        $socket->connect();

    }
}
