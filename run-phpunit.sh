#!/bin/bash

pushd tests

if [ "$#" -eq 0 ]; then
	exec phpunit --coverage-html report/
else
	exec phpunit --coverage-html report/ "$@"
fi

popd
