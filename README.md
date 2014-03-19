# PHP FTP Client

[![Build Status](https://travis-ci.org/ngyuki/php-ftp-client.png)](https://travis-ci.org/ngyuki/php-ftp-client)
[![Coverage Status](https://coveralls.io/repos/ngyuki/php-ftp-client/badge.png?branch=next)](https://coveralls.io/r/ngyuki/php-ftp-client?branch=next)

FTP client library that does not depend on FTP extension.

## Install

```console
$ php composer.phar require "ngyuki/php-ftp-client:*"
```

## Requirements

 - PHP 5.3.0 or later

## Example

```php
<?php
require __DIR__ . '/../vendor/autoload.php';

use ngyuki\FtpClient\FtpClient;
use ngyuki\FtpClient\FtpException;
use ngyuki\FtpClient\TransportException;

$ftp = new FtpClient();

try
{
    $ftp->connect("example.net", 21, 10);
    $ftp->login("hoge", "piyo");

    echo "nlist...\n";
    echo implode("\n", $ftp->nlist("."));
    echo "\n\n";

    echo "put...\n";
    $ftp->put("test.txt", "testing");

    $ftp->quit();

    echo "done.\n";
}
catch (FtpException $ex)
{
    echo "FtpException: {$ex->getResponse()->getResponseLine()}\n";
}
catch (TransportException $ex)
{
    echo "TransportException: {$ex->getMessage()}\n";
}
```
