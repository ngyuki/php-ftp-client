@echo off

pushd %~dp0

call phing phpdoc

rem call phpdoc -d ./ -t ../phpdoc --template=abstract
rem start ../phpdoc/index.html

popd

pause
