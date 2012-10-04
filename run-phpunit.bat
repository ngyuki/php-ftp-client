@echo off

pushd %~dp0\tests

call phpunit -c phpunit.win.xml --coverage-html ../report/

popd

start report\index.html
