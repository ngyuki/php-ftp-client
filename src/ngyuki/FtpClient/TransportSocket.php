<?php
namespace ngyuki\FtpClient;

/**
 * @package   ngyuki\FtpClient
 * @copyright 2012 ngyuki <ngyuki.ts@gmail.com>
 * @author    ngyuki <ngyuki.ts@gmail.com>
 * @license   http://www.opensource.org/licenses/mit-license.php  MIT License
 * @link      https://github.com/ngyuki/php-ftp-client
 */
class TransportSocket implements TransportInterface
{
    /**
     * @var resource ソケットリソース
     */
    private $_socket;

    /**
     * @var string 受信バッファ
     */
    private $_buffer = "";

    /**
     * デストラクタ
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * ソケット例外
     *
     * @param string $func
     * @param resource $socket
     * @throws TransportException
     */
    private static function raiseSocketException($func, $socket = null)
    {
        if (is_resource($socket))
        {
            $errno = socket_last_error($socket);
        }
        else
        {
            $errno = socket_last_error();
        }

        if ($errno == 0)
        {
            throw new TransportException("$func(): unknown error");
        }
        else
        {
            $errstr = socket_strerror($errno);
            throw new TransportException("$func(): [$errno] $errstr");
        }
    }

    /**
     * 接続する
     *
     * @param string $host
     * @param int    $port
     * @param int    $timeout
     *
     * @throws TransportException
     */
    public function connect($host, $port, $timeout)
    {
        ASSERT(' is_string($host) && strlen($host) ');
        ASSERT(' is_int($port)    && ($port    > 0) ');
        ASSERT(' is_int($timeout) && ($timeout > 0) ');

        $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if (is_resource($socket) == false)
        {
            // @codeCoverageIgnoreStart
            self::raiseSocketException('socket_create');
            // @codeCoverageIgnoreEnd
        }

        try
        {
            if (@socket_set_block($socket) == false)
            {
                // @codeCoverageIgnoreStart
                self::raiseSocketException('socket_set_block', $socket);
                // @codeCoverageIgnoreEnd
            }

            if (@socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0)) == false)
            {
                // @codeCoverageIgnoreStart
                self::raiseSocketException('socket_set_option', $socket);
                // @codeCoverageIgnoreEnd
            }

            if (@socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => $timeout, 'usec' => 0)) == false)
            {
                // @codeCoverageIgnoreStart
                self::raiseSocketException('socket_set_option', $socket);
                // @codeCoverageIgnoreEnd
            }

            if (@socket_connect($socket, $host, $port) == false)
            {
                self::raiseSocketException('socket_connect', $socket);
            }
        }
        catch (\Exception $ex)
        {
            socket_close($socket);
            throw $ex;
        }

        $this->_socket = $socket;
    }

    /**
     * 接続済なら true を返す
     *
     * @return boolean
     */
    public function connected()
    {
        return is_resource($this->_socket);
    }

    /**
     * 接続を閉じる
     */
    public function close()
    {
        if ($this->_socket)
        {
            socket_close($this->_socket);
            $this->_socket = null;
        }
    }

    /**
     * データを全て受信する
     *
     * @return string
     *
     * @throws TransportException
     */
    public function recvall()
    {
        ASSERT('is_resource($this->_socket)');

        $data = array($this->_buffer);
        $this->_buffers = "";

        while (true)
        {
            $len = @socket_recv($this->_socket, $recv, 1024, 0);

            if ($len === false)
            {
                self::raiseSocketException('socket_recv', $this->_socket);
            }

            if ($len === 0)
            {
                break;
            }

            $data[] = $recv;
        }

        return implode("", $data);
    }

    /**
     * データを一行受信する
     *
     * @return string
     *
     * @throws TransportException
     */
    public function recvline()
    {
        ASSERT('is_resource($this->_socket)');

        $data = array();

        while (true)
        {
            if (strlen($this->_buffer))
            {
                $recv = $this->_buffer;
                $this->_buffer = "";
            }
            else
            {
                $len = @socket_recv($this->_socket, $recv, 1024, 0);

                if ($len === false)
                {
                    self::raiseSocketException('socket_recv', $this->_socket);
                }

                if ($len === 0)
                {
                    break;
                }
            }

            $pos = strpos($recv, "\n");

            if ($pos === false)
            {
                $data[] = $recv;
            }
            else
            {
                $data[] = substr($recv, 0, $pos + 1);
                $this->_buffer = substr($recv, $pos + 1);

                break;
            }
        }

        return implode("", $data);
    }

    /**
     * データを送信する
     *
     * @param string $data
     *
     * @throws TransportException
     */
    public function send($data)
    {
        ASSERT('is_resource($this->_socket)');
        ASSERT(' is_string($data) ');

        while (strlen($data))
        {
            $len = @socket_send($this->_socket, $data, strlen($data), 0);

            if ($len == 0)
            {
                self::raiseSocketException('socket_send', $this->_socket);
            }

            $data = substr($data, $len);
        }
    }
}
