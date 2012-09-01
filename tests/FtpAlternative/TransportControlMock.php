<?php
/**
 * @author ng
 */
class FtpAlternative_TransportControlMock implements FtpAlternative_TransportInterface
{
	private $_connected = false;
	
	private $_onclose;
	
	private $_host;
	private $_port;
	
	private $_recvlines = array();
	private $_patterns = array();
	
	public function clear()
	{
		$this->_connected = false;
	
		$this->_onclose = null;
	
		$this->_host = null;
		$this->_port = null;
	
		$this->_recvlines = array();
		$this->_patterns = array();
	}
	
	public function onClose($callback)
	{
		$this->_onclose = $callback;
	}
	
	public function setListen($host, $port)
	{
		$this->_host = $host;
		$this->_port = $port;
	}
	
	public function addPattern($pattern, $line)
	{
		$this->_patterns[] = array($pattern, $line);
	}
	
	public function addRecvline($line)
	{
		$this->_recvlines[] = $line;
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
		if ($this->_host !== $host)
		{
			throw new RuntimeException("connect host mismatch");
		}
		
		if ($this->_port !== $port)
		{
			throw new RuntimeException("connect port mismatch");
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
		throw new RuntimeException("recvall no support");
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
		ASSERT(' $this->_connected ');
		
		$line = array_shift($this->_recvlines);
		
		if ($line === null)
		{
			throw new RuntimeException("recvline is empty");
		}
		
		return $line;
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
		if (count($this->_patterns) === 0)
		{
			throw new RuntimeException("patterns is empty");
		}
		
		list($pattern, $line) = reset($this->_patterns);
		
		if (preg_match($pattern, $data) == 0)
		{
			throw new RuntimeException("patterns mismatch");
		}
		
		array_shift($this->_patterns);
		
		if ($line !== null)
		{
			$this->_recvlines[] = $line;
		}
	}
}
