<?php
namespace ngyuki\FtpClient;

/**
 * @package   ngyuki\FtpClient
 * @copyright 2012 ngyuki <ngyuki.ts@gmail.com>
 * @author    ngyuki <ngyuki.ts@gmail.com>
 * @license   http://www.opensource.org/licenses/mit-license.php  MIT License
 * @link      https://github.com/ngyuki/php-ftp-client
 */
interface TransportInterface
{
    /**
     * 接続する
     *
     * @param string $host
     * @param int    $port
     * @param int    $timeout
     *
     * @throws TransportException
     */
    public function connect($host, $port, $timeout);


    /**
     * 接続済なら true を返す
     *
     * @return boolean
     */
    public function connected();

    /**
     * 接続を閉じる
     */
    public function close();

    /**
     * データを全て受信する
     *
     * @return string
     *
     * @throws TransportException
     */
    public function recvall();

    /**
     * データを一行受信する
     *
     * @return string
     *
     * @throws TransportException
     */
    public function recvline();

    /**
     * データを送信する
     *
     * @param string $data
     *
     * @throws TransportException
     */
    public function send($data);
}
