<?php
/**
 * @author ng
 * @group posix
 */
class FtpAlternative_TransportTest extends PHPUnit_Framework_TestCase
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
	
	public static function createDummyServer()
	{
		self::checkSkipped();
		return new DummyServer();
	}
	
	/**
	 * @test
	 */
	public function test()
	{
		$server = self::createDummyServer();
		$server->run();
		
		$transport = new FtpAlternative_Transport();
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
		$transport = new FtpAlternative_Transport();
		
		try
		{
			$transport->connect('127.0.0.1', 11111, 1);
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertContains("stream_socket_client()", $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	public function recvall()
	{
		$data = "123\r\naaaa\r\nbbb";
		
		$server = self::createDummyServer();
		$server->addBuffer($data);
		$server->addBuffer(null);
		$server->run();
		
		$transport = new FtpAlternative_Transport();
		
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
		$server = self::createDummyServer();
		$server->run();
		
		$transport = new FtpAlternative_Transport();
		$transport->connect('127.0.0.1', 11111, 1);
		
		$server->term();
		
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
		$server = self::createDummyServer();
		$server->run();
		
		$transport = new FtpAlternative_Transport();
		$transport->connect('127.0.0.1', 11111, 1);
		
		$server->term();
		
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
		$server = self::createDummyServer();
		$server->run();
		
		$transport = new FtpAlternative_Transport();
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
