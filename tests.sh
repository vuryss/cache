#!/usr/bin/env bash

./vendor/bin/phpunit
php /home/user/phpcs.phar -s
php /home/user/phpmd.phar --exclude vendor,tests . text codesize,unusedcode,naming,controversial,cleancode,design
php /home/user/phan.phar
