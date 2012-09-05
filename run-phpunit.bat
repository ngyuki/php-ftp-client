@echo off

pushd %~dp0

call phpunit -c tests/phpunit-win.xml --coverage-html report/ tests/
start report/index.html

popd
