@echo off

call phpunit -c tests/phpunit-win.xml --coverage-html report/ tests/
start report/index.html
