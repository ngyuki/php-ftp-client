<?php
declare(ticks = 1);

/**
 * @author ng
 */
class DummyServer
{
	/**
	 * 子プロセスのコンテキストで実行するアクション
	 *
	 * @var mixed
	 */
	private $_action;
	
	/**
	 * 子プロセス
	 *
	 * @var ProcessFork
	 */
	private $_fork;
	
	/**
	 * デストラクタ
	 */
	public function __destruct()
	{
		$this->term();
	}
	
	/**
	 * サーバの実行
	 *
	 * @throws Exception
	 */
	public function run($port, $action)
	{
		$stream = $this->listen($port);
		
		$this->_fork = new ProcessFork();
		
		$self = $this;
		
		$this->_fork->fork(function () use ($self, $stream, $action) {
			$self->main($stream, $action);
		});
		
		fclose($stream);
		
	}
	
	/**
	 * サーバの強制終了
	 */
	public function term()
	{
		if ($this->_fork)
		{
			$this->_fork->term();
		}
	}
	
	private function listen($port)
	{
		$stream = stream_socket_server("tcp://127.0.0.1:$port", $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN);
		
		if ($stream === false)
		{
			throw new RuntimeException("stream_socket_server(): [$errno] $errstr");
		}
		
		if (stream_set_blocking($stream, 1) == false)
		{
			throw new RuntimeException("stream_set_blocking(): unknown error");
		}
		
		return $stream;
	}
	
	public function main($stream, $action)
	{
		$client = stream_socket_accept($stream, 600);
		
		if ($client === false)
		{
			throw new RuntimeException("stream_socket_accept(): unknown error");
		}
		
		try
		{
			$action($client);
			
			if (is_resource($client))
			{
				fclose($client);
			}
		}
		catch (Exception $ex)
		{
			if (is_resource($client))
			{
				fclose($client);
			}
			
			throw $ex;
		}
	}
}
