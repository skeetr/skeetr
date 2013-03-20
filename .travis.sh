#!/bin/bash
printf "\n"| pecl install pecl_http

sudo apt-get install libgearman-dev
wget http://pecl.php.net/get/gearman-1.0.2.tgz
tar -xzf gearman-1.0.2.tgz
sh -c "cd gearman-1.0.2 && phpize && ./configure && sudo make install"
echo "extension=gearman.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`

wget https://github.com/skeetr/php-skeetr/archive/master.zip
unzip master
sh -c "cd php-skeetr-master && phpize && ./configure && sudo make install"
echo "extension=skeetr.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`

