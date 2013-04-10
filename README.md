Skeetr [![Build Status](https://travis-ci.org/skeetr/skeetr.png?branch=master)](https://travis-ci.org/skeetr/skeetr)
==============================

PHP Application Server based on Gearmand + Nginx, your PHP faster than ever. 


Requirements
------------

* PHP 5.3.23
* Unix system
* PECL http 
* PECL gearman
* [php-skeetr](https://github.com/skeetr/php-skeetr)


Installation
------------

The recommended way to install skeetr/skeetr is [through composer](http://getcomposer.org).
You can see [package information on Packagist.](https://packagist.org/packages/skeetr/skeetr)

```JSON
{
    "require": {
        "skeetr/skeetr": "dev-master"
    }
}
```


Tests
-----

Tests are in the `tests` folder.
To run them, you need PHPUnit.
Example:

    $ phpunit --configuration phpunit.xml.dist


License
-------

MIT, see [LICENSE](LICENSE)
