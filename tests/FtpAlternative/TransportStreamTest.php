<?php
/**
 * @author ng
 * @group posix
 */
class FtpAlternative_TransportStreamTest extends PHPUnit_Framework_TestCase
{
	public static function checkSkipped()
	{
		if (!function_exists('posix_kill'))
		{
			self::markTestSkipped("Require Extension posix");
		}
		
		if (!function_exists('pcntl_fork'))
		{
			self::markTestSkipped("Require Extension pcntl");
		}
		
		if (!function_exists('socket_create'))
		{
			self::markTestSkipped("Require Extension sockets");
		}
	}
	
	public function createDummyServer()
	{
		self::checkSkipped();
		return new DummyServer();
	}
	
	protected function createTransport()
	{
		return new FtpAlternative_TransportStream();
	}
	
	/**
	 * @test
	 */
	public function test()
	{
		$server = $this->createDummyServer();
		$server->run();
		
		$transport = $this->createTransport();
		$this->assertFalse($transport->connected());
		
		$transport->connect('127.0.0.1', 11111, 3);
		$this->assertTrue($transport->connected());
		
		$data = "123456789\r\n";
		
		$transport->send($data);
		
		$line = $transport->recvline();
		$this->assertSame($data, $line);
		
		//
		$data = str_repeat("x", 1000*1000) . "\r\n";
		
		$transport->send($data);
		
		$line = $transport->recvline();
		$this->assertSame($data, $line);
		
		$transport->close();
		$this->assertFalse($transport->connected());
	}
	
	/**
	 * @test
	 */
	public function connect_error()
	{
		$transport = $this->createTransport();
		
		try
		{
			$transport->connect('127.0.0.1', 11111, 1);
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertContains("connect", $ex->getMessage());
			$this->assertContains("Connection refused", $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	public function recvall()
	{
		$data = "123\r\naaaa\r\nbbb";
		
		$server = $this->createDummyServer();
		$server->addBuffer($data);
		$server->addBuffer(null);
		$server->run();
		
		$transport = $this->createTransport();
		
		$transport->connect('127.0.0.1', 11111, 3);
		
		$recv = $transport->recvall();
		$this->assertSame($data, $recv);
		
		$transport->close();
	}
	
	/**
	 * @test
	 */
	public function recvall_error()
	{
		$server = $this->createDummyServer();
		$server->run();
		
		$transport = $this->createTransport();
		$transport->connect('127.0.0.1', 11111, 1);
		
		try
		{
			$recv = $transport->recvall();
			$this->fail("recvall ... \"$recv\"");
		}
		catch (RuntimeException $ex)
		{
			$this->assertContains("fgets()", $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	public function recvline_error()
	{
		$server = $this->createDummyServer();
		$server->run();
		
		$transport = $this->createTransport();
		$transport->connect('127.0.0.1', 11111, 1);
		
		try
		{
			$transport->recvline();
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertContains("fgets()", $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	public function send_error()
	{
		$server = $this->createDummyServer();
		$server->run();
		
		$transport = $this->createTransport();
		$transport->connect('127.0.0.1', 11111, 1);
		
		$server->term();
		
		$data = str_repeat("x", 1024*1024) . "\r\n";
		
		try
		{
			$transport->send($data);
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertContains("fwrite()", $ex->getMessage());
		}
	}
}
