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
