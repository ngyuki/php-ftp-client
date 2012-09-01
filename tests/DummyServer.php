<?php
declare(ticks = 1);

/**
 * @author ng
 */
class DummyServer
{
	/**
	 * リッスンするポート番号
	 *
	 * @var int
	 */
	private $_port = 11111;
	
	/**
	 * バッファの初期状態
	 *
	 * @var array
	 */
	private $_buffers = array();
	
	/**
	 * 子プロセスのプロセスID
	 *
	 * @var int
	 */
	private $_pid;
	
	/**
	 * デストラクタ
	 */
	public function __destruct()
	{
		$this->term();
	}
	
	/**
	 * バッファの初期状態を追加
	 *
	 * ここで指定した内容が最初にサーバからクライアントへ送信される
	 *
	 * @param string $buffer
	 */
	public function addBuffer($buffer)
	{
		$this->_buffers[] = $buffer;
	}
	
	/**
	 * サーバの実行
	 *
	 * @throws Exception
	 */
	public function run()
	{
		$socket = $this->listen();
		
		$pid = pcntl_fork();
		
		if ($pid == -1)
		{
			throw new Exception("pcntl_fork(): unknown error");
		}
		
		if ($pid)
		{
			$this->_pid = $pid;
			socket_close($socket);
		}
		else
		{
			try
			{
				$this->init();
				$this->main($socket);
				$this->term();
			}
			catch (Exception $ex)
			{
				$this->term();
			}
		}
	}
	
	/**
	 * サーバの強制終了
	 */
	public function term()
	{
		if ($this->_pid)
		{
			$pid = $this->_pid;
			
			if (pcntl_waitpid($pid, $st, WNOHANG) == 0)
			{
				posix_kill($pid, SIGTERM);
				$ret = pcntl_waitpid($pid, $st);
			}
		}
		else
		{
			$pid = posix_getpid();
			posix_kill($pid, SIGTERM);
			exit;
		}
	}
	
	private function listen()
	{
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		
		socket_set_block($socket);
		socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1) or die();
		
		socket_bind($socket, "127.0.0.1", $this->_port) or die();
		socket_listen($socket) or die();
		
		return $socket;
	}
	
	private function init()
	{
		// 出力をすべて破棄
		ob_start(function(){});
		
		// PHPエラーで終了
		set_error_handler(function() {
			$pid = posix_getpid();
			posix_kill($pid, SIGTERM);
			exit;
		});
		
		// 未知の例外で終了
		set_exception_handler(function() {
			$pid = posix_getpid();
			posix_kill($pid, SIGTERM);
			exit;
		});
		
		// 最大生存期間
		$lifelimit = microtime(true) + 600;
		
		// 生存期間が過ぎたら終了
		register_tick_function(function() use ($lifelimit) {
			
			if (microtime(true) >= $lifelimit)
			{
				$pid = posix_getpid();
				posix_kill($pid, SIGTERM);
				exit;
			}
		});
	}
	
	private function main($socket)
	{
		for(;;)
		{
			$remote = $this->accept($socket);
			$this->readwrite($remote);
		}
	}
	
	private function accept($server)
	{
		for(;;)
		{
			$rlst = array($server);
			$wlst = array();
			$elst = array();
			
			$ret = @socket_select($rlst, $wlst, $elst, 3) !== false or die();
			
			if ($ret && count($rlst))
			{
				$remote = socket_accept($server) or die();
				return $remote;
			}
		}
	}
	
	private function readwrite($socket)
	{
		socket_set_block($socket);
		socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1) or die();
		
		$buffers = $this->_buffers;
		$rflg = true;
		$wflg = true;
		
		while ($rflg || $wflg)
		{
			$rlst = array();
			$wlst = array();
			$elst = array();
			
			if ($rflg)
			{
				$rlst = array($socket);
			}
			
			if ($wflg && count($buffers))
			{
				$wlst = array($socket);
			}
			
			$ret = @socket_select($rlst, $wlst, $elst, 3) !== false or die();
			
			if ($ret != 0)
			{
				if (count($wlst))
				{
					$data = array_shift($buffers);
					
					if ($data === null)
					{
						socket_shutdown($socket, 1);
						$wflg = false;
					}
					else
					{
						$len = socket_write($socket, $data) or die();
						$data = substr($data, $len);
						
						if (strlen($data) > 0)
						{
							array_unshift($buffers, $data);
						}
					}
				}
				
				if (count($rlst))
				{
					$data = socket_read($socket, 4096);
					$data === false and die();
					
					if ($data === "")
					{
						$rflg = false;
					}
					else
					{
						$buffers[] = $data;
					}
				}
			}
		}
		
		socket_close($socket);
	}
}
