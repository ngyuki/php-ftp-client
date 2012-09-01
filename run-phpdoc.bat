@echo off

call phpdoc -d class -t phpdoc --template=abstract
start phpdoc/index.html
