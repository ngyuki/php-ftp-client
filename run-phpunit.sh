#!/bin/bash

if [ "$#" -eq 0 ]; then
	exec phpunit -c tests/phpunit.xml --coverage-html report/ tests/
else
	exec phpunit -c tests/phpunit.xml --coverage-html report/ "$@"
fi
