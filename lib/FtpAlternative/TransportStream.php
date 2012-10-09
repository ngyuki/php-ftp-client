<?php
/**
 * @package   FtpAlternative
 * @copyright 2012 tsyk goto
 * @author    tsyk goto
 * @license   http://www.opensource.org/licenses/mit-license.php  MIT License
 * @link      https://github.com/ngyuki/FtpAlternative
 */

/**
 * @package   FtpAlternative
 * @copyright 2012 tsyk goto
 * @author    tsyk goto
 * @license   http://www.opensource.org/licenses/mit-license.php  MIT License
 * @link      https://github.com/ngyuki/FtpAlternative
 */
class FtpAlternative_TransportStream implements FtpAlternative_TransportInterface
{
	/**
	 * @var resource ソケットストリームリソース
	 */
	private $_stream;
	
	/**
	 * @var boolean EOF
	 */
	private $_eof = false;
	
	/**
	 * デストラクタ
	 */
	public function __destruct()
	{
		$this->close();
	}
	
	/**
	 * 接続する
	 *
	 * @param string $host
	 * @param int    $port
	 * @param int    $timeout
	 *
	 * @throws RuntimeException
	 */
	public function connect($host, $port, $timeout)
	{
		ASSERT(' is_string($host) && strlen($host) ');
		ASSERT(' is_int($port)    && ($port    > 0) ');
		ASSERT(' is_int($timeout) && ($timeout > 0) ');
		
		$handler = new FtpAlternative_ErrorHandler();
		
		$errno = 0;
		$errstr = "";
		
		// stream_socket_client → RST でもタイムアウトまで待ってしまう・・・
		$url = "tcp://$host:$port";
		$stream = stream_socket_client($url, $errno, $errstr, $timeout);
		
		if (is_resource($stream) == false)
		{
			// @codeCoverageIgnoreStart
			if ($errno === 0)
			{
				throw new RuntimeException("stream_socket_client(): unknown error");
			}
			else
			{
				throw new RuntimeException("stream_socket_client(): [$errno] $errstr");
			}
			// @codeCoverageIgnoreEnd
		}
		
		if (stream_set_blocking($stream, true) == false)
		{
			// @codeCoverageIgnoreStart
			throw new RuntimeException("stream_set_blocking(): unknown error");
			// @codeCoverageIgnoreEnd
		}
		
		if (stream_set_timeout($stream, $timeout) == false)
		{
			// @codeCoverageIgnoreStart
			throw new RuntimeException("stream_set_timeout(): unknown error");
			// @codeCoverageIgnoreEnd
		}
		
		$this->_stream = $stream;
		$this->_eof = false;
	}
	
	/**
	 * 接続済なら true を返す
	 *
	 * @return boolean
	 */
	public function connected()
	{
		return is_resource($this->_stream);
	}
	
	/**
	 * 接続を閉じる
	 */
	public function close()
	{
		if ($this->_stream)
		{
			fclose($this->_stream);
			$this->_stream = null;
			$this->_eof = false;
		}
	}
	
	/**
	 * fgets wrapper
	 *
	 * @return string|null 受信データ または null (EOF)
	 */
	private function _fgets()
	{
		if ($this->_eof)
		{
			throw new RuntimeException("fgets(): end of stream");
		}
		
		if (feof($this->_stream))
		{
			$this->_eof = true;
			return null;
		}
		
		$recv = fgets($this->_stream, 1024);
		
		if ($recv === false)
		{
			$meta = stream_get_meta_data($this->_stream);
			
			if (is_array($meta))
			{
				if (isset($meta['timed_out']) && $meta['timed_out'])
				{
					throw new RuntimeException("fgets(): timeout");
				}
				
				if (isset($meta['eof']) && $meta['eof'])
				{
					$this->_eof = true;
					return null;
				}
			}
			
			throw new RuntimeException("fgets(): unknown error");
		}
		
		return $recv;
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
		ASSERT('is_resource($this->_stream)');
		
		$handler = new FtpAlternative_ErrorHandler();
		
		$data = array();
		
		for(;;)
		{
			// stream_get_contents → サーバが応答無い場合にタイムアウトせずに待ち続ける
			// fgets → たまに EOF に達した時に false が返る？
			//       → タイムアウトでも false が返るので eof と timeout を判断して処理する
			
			$recv = $this->_fgets();
			
			if ($recv === null)
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
	 * @throws RuntimeException
	 */
	public function recvline()
	{
		ASSERT('is_resource($this->_stream)');
		
		$handler = new FtpAlternative_ErrorHandler();
		
		$data = array();
		
		for(;;)
		{
			$recv = $this->_fgets();
			
			if ($recv === null)
			{
				break;
			}
			
			$data[] = $recv;
			
			if (strpos($recv, "\n") !== false)
			{
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
	 * @throws RuntimeException
	 */
	public function send($data)
	{
		ASSERT(' is_resource($this->_stream) ');
		ASSERT(' is_string($data) ');
		
		$handler = new FtpAlternative_ErrorHandler();
		
		$pos = 0;
		$len = strlen($data);
		
		while ($pos < $len)
		{
			$n = fwrite($this->_stream, substr($data, $pos));
			
			if ($n == 0)
			{
				// @codeCoverageIgnoreStart
				throw new RuntimeException("fwrite(): unknown error");
				// @codeCoverageIgnoreEnd
			}
			
			$pos += $n;
		}
	}
}