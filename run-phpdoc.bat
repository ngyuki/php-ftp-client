@echo off

pushd %~dp0

call phpdoc -d class -t phpdoc --template=abstract
start phpdoc/index.html

popd
