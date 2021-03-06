<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <mcuadros@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr\Runtime\Overrides;

use Skeetr\Runtime\Override;
use http;

/**
 * PHP impementation of setcookie and setrawcookie functions
 *
 * Built-in functions:
 * [+] setcookie — Send a cookie
 * [+] setrawcookie — Send a cookie without urlencoding the cookie value
 *
 * [+] = Implemented [-] = Original
 */
class Cookie extends Override
{
    /**
     * Defines a cookie to be sent along with the rest of the HTTP headers.
     *
     * @link http://www.php.net/manual/en/function.setcookie.php
     *
     * @param  string  $name     The name of the cookie.
     * @param  string  $value    (optional) The value of the cookie.
     * @param  integer $expire   (optional) The time the cookie expires. This is a Unix timestamp
     * @param  string  $path     (optional) The path on the server in which the cookie will be available on.
     * @param  string  $domain   (optional) The domain that the cookie is available to.
     * @param  boolean $secure   (optional) Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client.
     * @param  boolean $httponly (optional) When TRUE the cookie will be made accessible only through the HTTP protocol.
     * @return boolean
     */
    final public static function setcookie(
        $name, $value, $expire = 0, $path = null,
        $domain = null, $secure = false, $httponly = false
    ) {
        $value = rawurlencode($value);

        return Cookie::setrawcookie(
            $name, $value, $expire, $path, $domain, $secure, $httponly
        );
    }

    /**
     * Send a cookie without urlencoding the cookie value
     *
     * @link http://www.php.net/manual/en/function.setrawcookie.php
     *
     * @param  string  $name   The name of the cookie.
     * @param  string  $value  (optional) The value of the cookie.
     * @param  integer $expire (optional) The time the cookie expires. This is a Unix timestamp
     * @param  string  $path   (optional) The path on the server in which the cookie will be available on.
     * @param  string  $domain (optional) The domain that the cookie is available to.
     * @param  boolean $secure (optional) Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client.
     * @param  boolean $secure (optional) When TRUE the cookie will be made accessible only through the HTTP protocol.
     * @return boolean
     */
    final public static function setrawcookie(
        $name, $value, $expire = 0, $path = null,
        $domain = null, $secure = false, $httponly = false
    ) {
        $cookie = new http\Cookie();
        $cookie->addCookie($name, $value);
        $cookie->setExpires($expire);
        $cookie->setPath($path);
        $cookie->setDomain($domain);
        $flags = 0;
        if ($secure) {
            $flags = Cookie::SECURE;
        }

        if ($httponly) {
            $flags = $flags | Cookie::HTTPONLY;
        }

        $cookie->setFlags($flags);
        Header::header(sprintf('Set-Cookie: %s', $cookie), false);

        return true;
    }
}
