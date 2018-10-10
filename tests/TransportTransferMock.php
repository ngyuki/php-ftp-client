<?php
namespace ngyuki\FtpClient\Tests;

use ngyuki\FtpClient\TransportInterface;

/**
 * @author ngyuki
 */
class TransportTransferMock implements TransportInterface
{
    private $_connected = false;

    private $_onconnect;
    private $_onclose;
    private $_onsend;
    private $_onrecvall;

    public function clear()
    {
        $this->_connected = false;

        $this->_onclose = null;
        $this->_onclose = null;
        $this->_onsend = null;
        $this->_onrecvall = null;
    }

    public function onConnect($callback)
    {
        $this->_onconnect = $callback;
    }

    public function onSend($callback)
    {
        $this->_onsend = $callback;
    }

    public function onRecvAll($callback)
    {
        $this->_onrecvall = $callback;
    }

    public function onClose($callback)
    {
        $this->_onclose = $callback;
    }

    /**
     * @see \ngyuki\FtpClient\TransportInterface::connect()
     *
     * @param $host
     * @param $port
     * @param $timeout
     */
    public function connect($host, $port, $timeout)
    {
        if ($this->_onconnect)
        {
            call_user_func($this->_onconnect, $host, $port, $timeout);
        }

        $this->_connected = true;
    }

    /**
     * @see \ngyuki\FtpClient\TransportInterface::connected()
     */
    public function connected()
    {
        return $this->_connected;
    }

    /**
     * @see \ngyuki\FtpClient\TransportInterface::close()
     */
    public function close()
    {
        if ($this->_connected)
        {
            if ($this->_onclose)
            {
                call_user_func($this->_onclose);
            }
        }

        $this->_connected = false;
    }

    /**
     * @see \ngyuki\FtpClient\TransportInterface::recvall()
     */
    public function recvall()
    {
        if ($this->_onrecvall)
        {
            return call_user_func($this->_onrecvall);
        }

        return "";
    }

    /**
     * @see \ngyuki\FtpClient\TransportInterface::recvline()
     */
    public function recvline()
    {
        throw new \BadMethodCallException ("not support recvline");
    }

    /**
     * @see \ngyuki\FtpClient\TransportInterface::send()
     *
     * @param $data
     */
    public function send($data)
    {
        if ($this->_onsend)
        {
            call_user_func($this->_onsend, $data);
        }
    }
}
