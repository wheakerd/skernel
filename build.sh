#!/bin/bash
bin/php -c bin/php.ini bin/composer dump-autoload -o
bin/php bin/run build
echo
./dist/skernel