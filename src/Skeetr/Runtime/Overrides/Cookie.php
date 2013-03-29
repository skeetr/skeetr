<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <maximo@yunait.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr\Runtime\Overrides;
use Skeetr\Runtime\Override;
use Skeetr\HTTP\Response;

/**
 * PHP impementation of setcookie and setrawcookie functions
 * 
 * Built-in functions:
 * [+] setcookie — Send a cookie
 * [+] setrawcookie — Send a cookie without urlencoding the cookie value
 *
 * [+] = Implemented [-] = Original 
 */
class Cookie extends Override {
    final static public function setcookie(
        $name, $value, $expire = 0, $path = null, 
        $domain = null, $secure = false, $httponly = false
    ) {
        $value = rawurlencode($value);
        return Cookie::setrawcookie(
            $name, $value, $expire, $path, $domain, $secure, $httponly
        );
    }

    final static public function setrawcookie(
        $name, $value, $expire = 0, $path = null, 
        $domain = null, $secure = false, $httponly = false
    ) {
        $cookie = http_build_cookie(array(
            'cookies' => array($name => $value),
            'expires' => $expire,
            'path' => $path, 
            'domain' => $domain
        ));

        Header::header(sprintf('Set-Cookie: %s', $cookie), false);
    }
}