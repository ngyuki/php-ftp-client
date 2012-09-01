<?php
/**
 * @author ng
 */
class FtpAlternative_TransportTransferMock implements FtpAlternative_TransportInterface
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
	 * 接続
	 *
	 * @param string $host
	 * @param int    $port
	 * @param int    $timeout
	 *
	 * @throws RuntimeException
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
	 * 接続済なら true を返す
	 *
	 * @return boolean
	 */
	public function connected()
	{
		return $this->_connected;
	}
	
	/**
	 * 接続を閉じる
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
	 * データを全て受信する
	 *
	 * @return string
	 *
	 * @throws RuntimeException
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
	 * データを一行受信する
	 *
	 * @return string
	 *
	 * @throws RuntimeException
	 */
	public function recvline()
	{
		throw new RuntimeException("not support recvline");
	}
	
	/**
	 * データを送信する
	 *
	 * @param string $data
	 *
	 * @throws RuntimeException
	 */
	public function send($data)
	{
		if ($this->_onsend)
		{
			call_user_func($this->_onsend, $data);
		}
	}
}
