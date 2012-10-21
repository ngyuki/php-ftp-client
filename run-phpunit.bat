@echo off

pushd %~dp0

call phing phpunit

rem cd tests
rem call phpunit --coverage-html ../reports/coverage

popd

pause
