#!/bin/sh
# pre-commit.sh
direct=`pwd`;
if [ -z `echo $direct | grep 'tests$'` ]; then
    cd tests;
fi
if [ "$1" = 'report' ]; then
echo 'Generating report';
phpunit --coverage-html ./report .
exit;
fi
phpunit .
RESULT=$?
[ $RESULT -ne 0 ] && exit 1
exit 0
