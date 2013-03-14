#!/bin/bash
sudo apt-get install libgearman-dev
wget http://pecl.php.net/get/gearman-1.1.1.tgz 
tar -xzf gearman-1.1.1.tgz
sh -c "cd gearman-1.1.1 && phpize && ./configure && sudo make install"
echo "extension=gearman.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`

printf "\n"| pecl install pecl_http