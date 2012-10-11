<?php
/**
 * @author ng
 * @group posix
 * @group server
 * @requires function pcntl_fork
 */
class FtpAlternative_DummyServerTest extends PHPUnit_Framework_TestCase
{
	private function action($function)
	{
		$self = $this;
		
		return function ($stream) use ($self, $function) {
			$function($self, $stream);
		};
	}
	
	public function recvline($stream, $pattern)
	{
		$data = fgets($stream);
		
		if (preg_match($pattern, $data) == 0)
		{
			$data = trim($data);
			fputs($stream, "999 recv [$data]");
			throw new Exception("999 recv [$data]");
		}
	}
	
	public function sendline($stream, $data)
	{
		fputs($stream, $data . "\r\n");
	}
	
	/**
	 * @test
	 */
	public function test()
	{
		$dummy = new DummyServer();
		$dummy->run(11111, $this->action(function(FtpAlternative_DummyServerTest $self, $stream) {
			
			$self->sendline($stream, "220 FTP Server ready.");
			
			//
			$self->recvline($stream, "/^USER as0d5a\s*/");
			$self->sendline($stream, "331 Password required for as0d5a");
			
			//
			$self->recvline($stream, "/^PASS fg54sdf\s*/");
			$self->sendline($stream, "230 User hoge logged in.");
			
			//
			$self->recvline($stream, "/^QUIT\s*/");
			$self->sendline($stream, "221 Goodbye.");
		}));
		
		$ftp = new FtpAlternative_FtpClient();
		$ftp->connect("127.0.0.1", 11111, 10);
		
		$ftp->login("as0d5a", "fg54sdf");
		
		$ftp->quit();
	}
	
	/**
	 * @test
	 */
	public function connect_error_respcode()
	{
		$dummy = new DummyServer();
		$dummy->run(11111, $this->action(function(FtpAlternative_DummyServerTest $self, $stream) {
			
			$self->sendline($stream, "981 a54rga50sdf5");
		}));
		
		$ftp = new FtpAlternative_FtpClient();
		
		try
		{
			$ftp->connect("127.0.0.1", 11111, 10);
			$this->fail();
		}
		catch (FtpAlternative_FtpException $ex)
		{
			$this->assertEquals(981, $ex->getCode());
			$this->assertStringMatchesFormat('connect(): returned %s', $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	public function quit_error_respcode()
	{
		$dummy = new DummyServer();
		$dummy->run(11111, $this->action(function(FtpAlternative_DummyServerTest $self, $stream) {
			
			$self->sendline($stream, "220 FTP Server ready.");
			
			//
			$self->recvline($stream, "/^QUIT\s*/");
			$self->sendline($stream, "944 gsd04g5sdag5a0");
		}));
		
		$ftp = new FtpAlternative_FtpClient();
		$ftp->connect("127.0.0.1", 11111, 10);
		
		try
		{
			$ftp->quit();
			$this->fail();
		}
		catch (FtpAlternative_FtpException $ex)
		{
			$this->assertEquals(944, $ex->getCode());
			$this->assertStringMatchesFormat('quit(): QUIT command returned %s', $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	public function login_nopass()
	{
		$dummy = new DummyServer();
		$dummy->run(11111, $this->action(function(FtpAlternative_DummyServerTest $self, $stream) {
			
			$self->sendline($stream, "220 FTP Server ready.");
			
			//
			$self->recvline($stream, "/^USER as0d5a\s*/");
			$self->sendline($stream, "230 hoge");
		}));
		
		$ftp = new FtpAlternative_FtpClient();
		$ftp->connect("127.0.0.1", 11111, 10);
		$ftp->login("as0d5a", "");
		
		$this->assertTrue(true);
	}
	
	/**
	 * @test
	 */
	public function login_error_user_respcode()
	{
		$dummy = new DummyServer();
		$dummy->run(11111, $this->action(function(FtpAlternative_DummyServerTest $self, $stream) {
			
			$self->sendline($stream, "220 FTP Server ready.");
			
			//
			$self->recvline($stream, "/^USER as0d5a\s*/");
			$self->sendline($stream, "917 asdfafr");
		}));
		
		$ftp = new FtpAlternative_FtpClient();
		$ftp->connect("127.0.0.1", 11111, 10);
		
		try
		{
			$ftp->login("as0d5a", "");
			$this->fail();
		}
		catch (FtpAlternative_FtpException $ex)
		{
			$this->assertEquals(917, $ex->getCode());
			$this->assertStringMatchesFormat('login(): USER command returned %s', $ex->getMessage());
		}
		
		$this->assertTrue(true);
	}
	
	/**
	 * @test
	 */
	public function login_error_pass_respcode()
	{
		$dummy = new DummyServer();
		$dummy->run(11111, $this->action(function(FtpAlternative_DummyServerTest $self, $stream) {
			
			$self->sendline($stream, "220 FTP Server ready.");
			
			//
			$self->recvline($stream, "/^USER as0d5a\s*/");
			$self->sendline($stream, "331 Password required for as0d5a");
			
			//
			$self->recvline($stream, "/^PASS fg54sdf\s*/");
			$self->sendline($stream, "914 asfdsa4d1a0.");
		}));
		
		$ftp = new FtpAlternative_FtpClient();
		$ftp->connect("127.0.0.1", 11111, 10);
		
		try
		{
			$ftp->login("as0d5a", "fg54sdf");
			$this->fail();
		}
		catch (FtpAlternative_FtpException $ex)
		{
			$this->assertEquals(914, $ex->getCode());
			$this->assertStringMatchesFormat('login(): PASS command returned %s', $ex->getMessage());
		}
		
		$this->assertTrue(true);
	}
}
