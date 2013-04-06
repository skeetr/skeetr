Skeetr [![Build Status](https://travis-ci.org/mcuadros/cli-array-editor.png?branch=master)](https://travis-ci.org/skeetr/skeetr)
==============================

PHP Application Server based on Gearmand + Nginx, your PHP faster than ever. 


Requirements
------------

* PHP 5.3.23
* Unix system
* PECL http 
* PECL gearman
* [hp-skeetr](https://github.com/skeetr/php-skeetr)


Installation
------------

The recommended way to install CLIArrayEditor is [through composer](http://getcomposer.org).
You can see [package information on Packagist.](https://packagist.org/packages/mcuadros/cli-array-editor)

```JSON
{
    "require": {
        "skeetr/skeetr": "dev"
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