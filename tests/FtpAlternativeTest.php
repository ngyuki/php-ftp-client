<?php
/**
 * @author ng
 */
class FtpAlternativeTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var FtpAlternative
	 */
	private $ftp;
	
	/**
	 * @var FtpAlternative_TransportControlMock
	 */
	private $control;
	
	/**
	 * @var FtpAlternative_TransportTransferMock
	 */
	private $transfer;
	
	protected function setUp()
	{
		new stdClass() instanceof FtpAlternative_Response;
		
		$this->control = new FtpAlternative_TransportControlMock();
		$this->transfer = new FtpAlternative_TransportTransferMock();
		$this->ftp = new FtpAlternative($this->control, $this->transfer);
	}
	
	/**
	 * @test
	 */
	function construct()
	{
		$control = new FtpAlternative_TransportControlMock();
		$transfer = new FtpAlternative_TransportTransferMock();
		
		$ftp = new FtpAlternative();
		$ftp = new FtpAlternative($control);
		$ftp = new FtpAlternative($control, $transfer);
	}
	
	/**
	 * @test
	 */
	function connect()
	{
		$control = new FtpAlternative_TransportControlMock();
		$transfer = new FtpAlternative_TransportTransferMock();
		$ftp = new FtpAlternative($control, $transfer);
		
		$control->setListen("192.2.0.123", 12345);
		$control->addRecvline("220 connected");
		
		$this->assertFalse($control->connected());
		$this->assertFalse($transfer->connected());
		
		$ftp->connect("192.2.0.123", 12345, 5);
		
		$this->assertTrue($control->connected());
		$this->assertFalse($transfer->connected());
	}
	
	/**
	 * @test
	 */
	function connect_error_invalid_respcode()
	{
		/// connect_error_invalid_respcode()
		$control = new FtpAlternative_TransportControlMock();
		$transfer = new FtpAlternative_TransportTransferMock();
		$ftp = new FtpAlternative($control, $transfer);
		
		$control->setListen("192.2.0.123", 12345);
		$control->addRecvline("954 xxx");
		
		try
		{
			$ftp->connect("192.2.0.123", 12345, 5);
		}
		catch (FtpAlternative_FtpException $ex)
		{
			$this->assertFalse($control->connected());
			$this->assertFalse($transfer->connected());
			
			$this->assertEquals($ex->getCode(), 954);
			$this->assertContains("connect(): returned", $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	function connect_error_invalid_host()
	{
		$control = new FtpAlternative_TransportControlMock();
		$transfer = new FtpAlternative_TransportTransferMock();
		$ftp = new FtpAlternative($control, $transfer);
		
		$control->setListen("192.2.0.123", 12345);
		
		try
		{
			$ftp->connect("192.2.0.124", 12346, 5);
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertFalse($control->connected());
			$this->assertFalse($transfer->connected());
			
			$this->assertEquals('RuntimeException', get_class($ex));
			$this->assertContains("connect host mismatch", $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	function close()
	{
		$control = new FtpAlternative_TransportControlMock();
		$transfer = new FtpAlternative_TransportTransferMock();
		
		$ftp = new FtpAlternative($control, $transfer);
		
		$control->setListen("192.2.0.123", 12345);
		$control->addRecvline("220 connected");
		$ftp->connect("192.2.0.123", 12345, 5);
		$transfer->connect("192.2.0.123", 12345, 5);
		
		$this->assertTrue($control->connected());
		$this->assertTrue($transfer->connected());
		
		$ftp->close();
		
		$this->assertFalse($control->connected());
		$this->assertFalse($transfer->connected());
		
		/// $control の切断で例外
		
		$control->setListen("192.2.0.123", 12345);
		$control->addRecvline("220 connected");
		$ftp->connect("192.2.0.123", 12345, 5);
		$transfer->connect("192.2.0.123", 12345, 5);
		
		$control->onClose(function (){
			throw new RuntimeException("asda405das2d4");
		});
		
		try
		{
			$ftp->close();
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$control->onClose(null);
			
			$this->assertEquals('RuntimeException', get_class($ex));
			$this->assertContains("asda405das2d4", $ex->getMessage());
			
			$this->assertFalse($transfer->connected());
		}
		
		/// $transfer の切断で例外
		
		$control->setListen("192.2.0.123", 12345);
		$control->addRecvline("220 connected");
		$ftp->connect("192.2.0.123", 12345, 5);
		$transfer->connect("192.2.0.123", 12345, 5);
		
		$transfer->onClose(function (){
			throw new RuntimeException("gerh8884as0ra");
		});
		
		try
		{
			$ftp->close();
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$control->onClose(null);
			$transfer->onClose(null);
			
			$this->assertEquals('RuntimeException', get_class($ex));
			$this->assertContains("gerh8884as0ra", $ex->getMessage());
			
			$this->assertFalse($control->connected());
		}
		
		/// $control と $transfer の切断で例外
		
		$control->setListen("192.2.0.123", 12345);
		$control->addRecvline("220 connected");
		$ftp->connect("192.2.0.123", 12345, 5);
		$transfer->connect("192.2.0.123", 12345, 5);
		
		$control->onClose(function (){
			throw new RuntimeException("asda405das2d4");
		});
		
		$transfer->onClose(function (){
			throw new RuntimeException("gerh8884as0ra");
		});
		
		try
		{
			$ftp->close();
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$control->onClose(null);
			$transfer->onClose(null);
			
			$this->assertEquals('RuntimeException', get_class($ex));
			$this->assertContains("asda405das2d4", $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	function quit()
	{
		$control = new FtpAlternative_TransportControlMock();
		$ftp = new FtpAlternative($control);
		
		$control->setListen("192.2.0.123", 12345);
		$control->addRecvline("220 connected");
		$ftp->connect("192.2.0.123", 12345, 5);
		
		$control->addPattern("/^QUIT\s*$/", "221 bye");
		$ftp->quit();
	}
	
	/**
	 * @test
	 */
	function quit_error_invalid_respcode()
	{
		// 応答コードが異なる
		
		$control = new FtpAlternative_TransportControlMock();
		$ftp = new FtpAlternative($control);
		
		$control->setListen("192.2.0.123", 12345);
		$control->addRecvline("220 connected");
		$ftp->connect("192.2.0.123", 12345, 5);
		
		$control->addPattern("/^QUIT\s*$/", "789");
		
		try
		{
			$ftp->quit();
			$this->fail();
		}
		catch (FtpAlternative_FtpException $ex)
		{
			$this->assertEquals($ex->getCode(), 789);
			$this->assertContains("quit(): QUIT command returned", $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	function quit_error_noresp()
	{
		$control = new FtpAlternative_TransportControlMock();
		$ftp = new FtpAlternative($control);
		
		$control->setListen("192.2.0.123", 12345);
		$control->addRecvline("220 connected");
		$ftp->connect("192.2.0.123", 12345, 5);
		
		$control->addPattern("/^QUIT\s*$/", null);
		
		try
		{
			$ftp->quit();
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertEquals('RuntimeException', get_class($ex));
			$this->assertContains("recvline is empty", $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	function quit_error_senderror()
	{
		$control = new FtpAlternative_TransportControlMock();
		$ftp = new FtpAlternative($control);
		
		$control->setListen("192.2.0.123", 12345);
		$control->addRecvline("220 connected");
		$ftp->connect("192.2.0.123", 12345, 5);
		
		try
		{
			$ftp->quit();
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertEquals('RuntimeException', get_class($ex));
			$this->assertContains("patterns is empty", $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	function quit_no_connect()
	{
		// 未接続ならなにもしない
		
		$control = new FtpAlternative_TransportControlMock();
		$ftp = new FtpAlternative($control);
		
		$this->assertFalse($control->connected());
		$ftp->quit();
		$this->assertFalse($control->connected());
	}
	
	/**
	 * FTP接続する
	 */
	private function prepareConnection()
	{
		$this->ftp->close();
		
		$this->control->clear();
		$this->transfer->clear();
		
		$this->control->setListen("192.2.0.123", 12345);
		$this->control->addRecvline("220 connected");
		
		$this->ftp->connect("192.2.0.123", 12345, 5);
	}
	
	/**
	 * @test
	 */
	function login()
	{
		$ftp = $this->ftp;
		$control = $this->control;
		
		$this->prepareConnection();
		
		$control->addPattern("/^USER +hoge\s*$/", "331 xxx");
		$control->addPattern("/^PASS +pass\s*$/", "230 xxx");
		
		$ftp->login("hoge", "pass");
		
		/// login_nopass()
		$this->prepareConnection();
		
		$control->addPattern("/^USER +hoge\s*$/", "230 xxx");
		
		$ftp->login("hoge", "");
		
		/// login_error_user_mismatch()
		$this->prepareConnection();
		
		$control->addPattern("/^USER +hoge\s*$/", "530 xxx");
		
		try
		{
			$ftp->login("hoge", "pass");
			$this->fail();
		}
		catch (FtpAlternative_FtpException $ex)
		{
			$this->assertEquals(530, $ex->getCode());
			$this->assertContains("login(): USER command returned", $ex->getMessage());
		}
		
		/// login_error_pass_mismatch()
		$this->prepareConnection();
		
		$control->addPattern("/^USER +hoge\s*$/", "331 xxx");
		$control->addPattern("/^PASS +pass\s*$/", "530 xxx");
		
		try
		{
			$ftp->login("hoge", "pass");
			$this->fail();
		}
		catch (FtpAlternative_FtpException $ex)
		{
			$this->assertEquals(530, $ex->getCode());
			$this->assertContains("login(): PASS command returned", $ex->getMessage());
		}
		
		/// login_error_user_noresp()
		$this->prepareConnection();
		
		$control->addPattern("/^USER +hoge\s*$/", null);
		
		try
		{
			$ftp->login("hoge", "pass");
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertEquals('RuntimeException', get_class($ex));
			$this->assertContains("recvline is empty", $ex->getMessage());
		}
		
		/// login_error_pass_noresp()
		$this->prepareConnection();
		
		$control->addPattern("/^USER +hoge\s*$/", "331 xxx");
		$control->addPattern("/^PASS +pass\s*$/", null);
		
		try
		{
			$ftp->login("hoge", "pass");
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertEquals('RuntimeException', get_class($ex));
			$this->assertContains("recvline is empty", $ex->getMessage());
		}
	}
	
	
	/**
	 * @test
	 */
	function get()
	{
		$ftp = $this->ftp;
		$control = $this->control;
		$transfer = $this->transfer;
		
		$this->prepareConnection();
		
		$control->addPattern("/^PASV\s*$/", "227 (192,2,0,124,48,58)");
		$control->addPattern("/^TYPE I\s*$/", "200");
		$control->addPattern("/^RETR hoge.txt\s*$/", "150");
		
		$self = $this;
		$obj = new stdClass();
		$obj->no = 0;
		
		$transfer->onConnect(function($host, $port, $timeout) use ($self, $obj) {
			$self->assertEquals($host, "192.2.0.124");
			$self->assertEquals($port, 12346);
			$obj->connect = ++$obj->no;
		});
		
		$transfer->onRecvAll(function() use($self, $obj, $transfer) {
			$self->assertTrue($transfer->connected());
			$obj->send = ++$obj->no;
			return "0123456789abcdef";
		});
		
		$transfer->onClose(function() use($self, $obj, $control, $transfer) {
			$self->assertTrue($transfer->connected());
			$control->addRecvline("226");
			$obj->close = ++$obj->no;
		});
		
		$data = $ftp->get("hoge.txt");
		
		$this->assertSame($data, "0123456789abcdef");
		
		$this->assertFalse($transfer->connected());
		$this->assertSame($obj->connect, 1);
		$this->assertSame($obj->send, 2);
		$this->assertSame($obj->close, 3);
	}
	
	/**
	 * @test
	 */
	function get_errors()
	{
		$ftp = $this->ftp;
		$control = $this->control;
		$transfer = $this->transfer;
		
		/// command invalid respcode
		$this->prepareConnection();
		
		$control->addPattern("/^PASV\s*$/", "227 (192,2,0,124,48,58)");
		$control->addPattern("/^TYPE I\s*$/", "200");
		$control->addPattern("/^RETR hoge.txt\s*$/", "958");
		
		try
		{
			$ftp->get("hoge.txt");
			$this->fail();
		}
		catch (FtpAlternative_FtpException $ex)
		{
			$this->assertFalse($transfer->connected());
			
			$this->assertEquals(958, $ex->getCode());
			$this->assertContains("get(): RETR command returned", $ex->getMessage());
		}
		
		/// command noresp
		$this->prepareConnection();
		
		$control->addPattern("/^PASV\s*$/", "227 (192,2,0,124,48,58)");
		$control->addPattern("/^TYPE I\s*$/", "200");
		$control->addPattern("/^RETR hoge.txt\s*$/", null);
		
		try
		{
			$ftp->get("hoge.txt");
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertFalse($transfer->connected());
			
			$this->assertEquals('RuntimeException', get_class($ex));
			$this->assertContains("recvline is empty", $ex->getMessage());
		}
		
		/// transfer recv error
		$this->prepareConnection();
		$ftp = $this->ftp;
		$control = $this->control;
		$transfer = $this->transfer;
		
		$control->addPattern("/^PASV\s*$/", "227 (192,2,0,124,48,58)");
		$control->addPattern("/^TYPE I\s*$/", "200");
		$control->addPattern("/^RETR hoge.txt\s*$/", "150");
		
		$transfer->onRecvAll(function() {
			throw new RuntimeException("transfer recv error");
		});
		
		try
		{
			$ftp->get("hoge.txt");
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertFalse($transfer->connected());
			
			$this->assertEquals('RuntimeException', get_class($ex));
			$this->assertContains("transfer recv error", $ex->getMessage());
		}
		
		/// transfer invalid respcode
		$this->prepareConnection();
		
		$control->addPattern("/^PASV\s*$/", "227 (192,2,0,124,48,58)");
		$control->addPattern("/^TYPE I\s*$/", "200");
		$control->addPattern("/^RETR hoge.txt\s*$/", "150");
		
		$transfer->onClose(function() use($control) {
			$control->addRecvline("981");
		});
		
		try
		{
			$ftp->get("hoge.txt");
			$this->fail();
		}
		catch (FtpAlternative_FtpException $ex)
		{
			$this->assertFalse($transfer->connected());
			
			$this->assertEquals(981, $ex->getCode());
			$this->assertContains("get(): RETR complete returned", $ex->getMessage());
		}
		
		/// transfer noresp
		$this->prepareConnection();
		
		$control->addPattern("/^PASV\s*$/", "227 (192,2,0,124,48,58)");
		$control->addPattern("/^TYPE I\s*$/", "200");
		$control->addPattern("/^RETR hoge.txt\s*$/", "150");
		
		try
		{
			$ftp->get("hoge.txt");
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertFalse($transfer->connected());
			
			$this->assertEquals('RuntimeException', get_class($ex));
			$this->assertContains("recvline is empty", $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	function put()
	{
		$this->prepareConnection();
		$ftp = $this->ftp;
		$control = $this->control;
		$transfer = $this->transfer;
		
		$control->addPattern("/^PASV\s*$/", "227 (192,2,0,124,48,58)");
		$control->addPattern("/^TYPE I\s*$/", "200");
		$control->addPattern("/^STOR hoge.txt\s*$/", "150");
		
		$self = $this;
		$obj = new stdClass();
		$obj->no = 0;
		
		$transfer->onConnect(function($host, $port, $timeout) use ($self, $obj) {
			$self->assertEquals($host, "192.2.0.124");
			$self->assertEquals($port, 12346);
			$obj->connect = ++$obj->no;
		});
		
		$transfer->onSend(function($data) use($self, $obj, $transfer) {
			$self->assertTrue($transfer->connected());
			$self->assertSame("0123456789abcdef", $data);
			$obj->send = ++$obj->no;
		});
		
		$transfer->onClose(function() use($self, $obj, $control, $transfer) {
			$self->assertTrue($transfer->connected());
			$control->addRecvline("226");
			$obj->close = ++$obj->no;
		});
		
		$ftp->put("hoge.txt", "0123456789abcdef");
		
		$this->assertFalse($transfer->connected());
		$this->assertSame($obj->connect, 1);
		$this->assertSame($obj->send, 2);
		$this->assertSame($obj->close, 3);
	}
	
	/**
	 * @test
	 */
	function put_error_pasv_invalid_respcode()
	{
		$this->prepareConnection();
		$ftp = $this->ftp;
		$control = $this->control;
		$transfer = $this->transfer;
		
		$control->addPattern("/^PASV\s*$/", "400 (192,2,0,124,48,58)");
		
		try
		{
			$ftp->put("hoge.txt", "0123456789abcdef");
			$this->fail();
		}
		catch (FtpAlternative_FtpException $ex)
		{
			$this->assertEquals(400, $ex->getCode());
			$this->assertContains("pasv(): PASV command returned", $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	function put_error_pasv_noresp()
	{
		$this->prepareConnection();
		$ftp = $this->ftp;
		$control = $this->control;
		$transfer = $this->transfer;
		
		$control->addPattern("/^PASV\s*$/", null);
		
		try
		{
			$ftp->put("hoge.txt", "0123456789abcdef");
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertEquals('RuntimeException', get_class($ex));
			$this->assertContains("recvline is empty", $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	function put_error_pasv_cannot_parse()
	{
		$this->prepareConnection();
		$ftp = $this->ftp;
		$control = $this->control;
		$transfer = $this->transfer;
		
		$control->addPattern("/^PASV\s*$/", "227 (192,2,0,124,48)");
		
		try
		{
			$ftp->put("hoge.txt", "0123456789abcdef");
			$this->fail();
		}
		catch (FtpAlternative_FtpException $ex)
		{
			$this->assertEquals(227, $ex->getCode());
			$this->assertContains("pasv(): PASV not parsed", $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	function put_error_pasv_invalid_number()
	{
		$this->prepareConnection();
		$ftp = $this->ftp;
		$control = $this->control;
		$transfer = $this->transfer;
		
		$control->addPattern("/^PASV\s*$/", "227 (256,256,256,256,256,256)");
		
		try
		{
			$ftp->put("hoge.txt", "0123456789abcdef");
			$this->fail();
		}
		catch (FtpAlternative_FtpException $ex)
		{
			$this->assertEquals(227, $ex->getCode());
			$this->assertContains("pasv(): PASV invalid number", $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	function put_error_type_invalid_respcode()
	{
		$this->prepareConnection();
		$ftp = $this->ftp;
		$control = $this->control;
		$transfer = $this->transfer;
		
		$control->addPattern("/^PASV\s*$/", "227 (192,2,0,124,48,58)");
		$control->addPattern("/^TYPE I\s*$/", "951");
		
		try
		{
			$ftp->put("hoge.txt", "0123456789abcdef");
			$this->fail();
		}
		catch (FtpAlternative_FtpException $ex)
		{
			$this->assertEquals(951, $ex->getCode());
			$this->assertContains("pasv(): TYPE command returned", $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	function put_error_type_noresp()
	{
		$this->prepareConnection();
		$ftp = $this->ftp;
		$control = $this->control;
		$transfer = $this->transfer;
		
		$control->addPattern("/^PASV\s*$/", "227 (192,2,0,124,48,58)");
		$control->addPattern("/^TYPE I\s*$/", null);
		
		try
		{
			$ftp->put("hoge.txt", "0123456789abcdef");
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertEquals('RuntimeException', get_class($ex));
			$this->assertContains("recvline is empty", $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	function put_error_stor_invalid_respcode()
	{
		$this->prepareConnection();
		$ftp = $this->ftp;
		$control = $this->control;
		$transfer = $this->transfer;
		
		$control->addPattern("/^PASV\s*$/", "227 (192,2,0,124,48,58)");
		$control->addPattern("/^TYPE I\s*$/", "200");
		$control->addPattern("/^STOR hoge.txt\s*$/", "945");
		
		try
		{
			$ftp->put("hoge.txt", "0123456789abcdef");
			$this->fail();
		}
		catch (FtpAlternative_FtpException $ex)
		{
			$this->assertEquals(945, $ex->getCode());
			$this->assertContains("put(): STOR command returned", $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	function put_error_stor_noresp()
	{
		$this->prepareConnection();
		$ftp = $this->ftp;
		$control = $this->control;
		$transfer = $this->transfer;
		
		$control->addPattern("/^PASV\s*$/", "227 (192,2,0,124,48,58)");
		$control->addPattern("/^TYPE I\s*$/", "200");
		$control->addPattern("/^STOR hoge.txt\s*$/", null);
		
		try
		{
			$ftp->put("hoge.txt", "0123456789abcdef");
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertEquals('RuntimeException', get_class($ex));
			$this->assertContains("recvline is empty", $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	function put_error_transfer_senderror()
	{
		$this->prepareConnection();
		$ftp = $this->ftp;
		$control = $this->control;
		$transfer = $this->transfer;
		
		$control->addPattern("/^PASV\s*$/", "227 (192,2,0,124,48,58)");
		$control->addPattern("/^TYPE I\s*$/", "200");
		$control->addPattern("/^STOR hoge.txt\s*$/", "150");
		
		$transfer->onSend(function($data) {
			throw new RuntimeException("transfer senderror");
		});
		
		try
		{
			$ftp->put("hoge.txt", "0123456789abcdef");
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertEquals('RuntimeException', get_class($ex));
			$this->assertContains("transfer senderror", $ex->getMessage());
			
			$this->assertFalse($transfer->connected());
		}
	}
	
	/**
	 * @test
	 */
	function put_error_transfer_invalid_respcode()
	{
		$this->prepareConnection();
		$ftp = $this->ftp;
		$control = $this->control;
		$transfer = $this->transfer;
		
		$control->addPattern("/^PASV\s*$/", "227 (192,2,0,124,48,58)");
		$control->addPattern("/^TYPE I\s*$/", "200");
		$control->addPattern("/^STOR hoge.txt\s*$/", "150");
		
		$transfer->onClose(function() use($control) {
			$control->addRecvline("952");
		});
		
		try
		{
			$ftp->put("hoge.txt", "0123456789abcdef");
			$this->fail();
		}
		catch (FtpAlternative_FtpException $ex)
		{
			$this->assertEquals(952, $ex->getCode());
			$this->assertContains("put(): STOR complete returned", $ex->getMessage());
			
			$this->assertFalse($transfer->connected());
		}
	}
	
	/**
	 * @test
	 */
	function put_error_transfer_noresp()
	{
		$this->prepareConnection();
		$ftp = $this->ftp;
		$control = $this->control;
		$transfer = $this->transfer;
		
		$control->addPattern("/^PASV\s*$/", "227 (192,2,0,124,48,58)");
		$control->addPattern("/^TYPE I\s*$/", "200");
		$control->addPattern("/^STOR hoge.txt\s*$/", "150");
		
		try
		{
			$ftp->put("hoge.txt", "0123456789abcdef");
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertEquals('RuntimeException', get_class($ex));
			$this->assertContains("recvline is empty", $ex->getMessage());
			
			$this->assertFalse($transfer->connected());
		}
	}
	
	/**
	 * @test
	 */
	function rawlist()
	{
		$this->prepareConnection();
		$ftp = $this->ftp;
		$control = $this->control;
		$transfer = $this->transfer;
		
		$control->addPattern("/^PASV\s*$/", "227 (192,2,0,124,48,58)");
		$control->addPattern("/^TYPE A\s*$/", "200");
		$control->addPattern("/^LIST zzz\s*$/", "150");
		
		$self = $this;
		$obj = new stdClass();
		$obj->no = 0;
		
		$transfer->onConnect(function($host, $port, $timeout) use ($self, $obj) {
			$self->assertEquals($host, "192.2.0.124");
			$self->assertEquals($port, 12346);
			$obj->connect = ++$obj->no;
		});
		
		$transfer->onRecvAll(function() use($self, $obj, $transfer) {
			$self->assertTrue($transfer->connected());
			$obj->send = ++$obj->no;
			return "123\r\nabc";
		});
		
		$transfer->onClose(function() use($self, $obj, $control, $transfer) {
			$self->assertTrue($transfer->connected());
			$control->addRecvline("226");
			$obj->close = ++$obj->no;
		});
		
		$list = $ftp->rawlist("zzz");
		
		$this->assertEquals($list, array("123", "abc"));
		
		$this->assertFalse($transfer->connected());
		$this->assertSame($obj->connect, 1);
		$this->assertSame($obj->send, 2);
		$this->assertSame($obj->close, 3);
	}
	
	/**
	 * @test
	 */
	function nlist()
	{
		$this->prepareConnection();
		$ftp = $this->ftp;
		$control = $this->control;
		$transfer = $this->transfer;
		
		$control->addPattern("/^PASV\s*$/", "227 (192,2,0,124,48,58)");
		$control->addPattern("/^TYPE A\s*$/", "200");
		$control->addPattern("/^NLST zzz\s*$/", "150");
		
		$self = $this;
		$obj = new stdClass();
		$obj->no = 0;
		
		$transfer->onConnect(function($host, $port, $timeout) use ($self, $obj) {
			$self->assertEquals($host, "192.2.0.124");
			$self->assertEquals($port, 12346);
			$obj->connect = ++$obj->no;
		});
		
		$transfer->onRecvAll(function() use($self, $obj, $transfer) {
			$self->assertTrue($transfer->connected());
			$obj->send = ++$obj->no;
			return "123\r\nabc";
		});
		
		$transfer->onClose(function() use($self, $obj, $control, $transfer) {
			$self->assertTrue($transfer->connected());
			$control->addRecvline("226");
			$obj->close = ++$obj->no;
		});
		
		$list = $ftp->nlist("zzz");
		
		$this->assertEquals($list, array("123", "abc"));
		
		$this->assertFalse($transfer->connected());
		$this->assertSame($obj->connect, 1);
		$this->assertSame($obj->send, 2);
		$this->assertSame($obj->close, 3);
	}
	
	/**
	 * @test
	 */
	function getlist()
	{
		$ftp = $this->ftp;
		$control = $this->control;
		$transfer = $this->transfer;
		
		/// empty list
		$this->prepareConnection();
		$control->addPattern("/^PASV\s*$/", "227 (192,2,0,124,48,58)");
		$control->addPattern("/^TYPE A\s*$/", "200");
		$control->addPattern("/^LIST zzz\s*$/", "226");
		
		$list = $ftp->rawlist("zzz");
		$this->assertEmpty($list);
		
		/// command returned invalid respcode
		$this->prepareConnection();
		$control->addPattern("/^PASV\s*$/", "227 (192,2,0,124,48,58)");
		$control->addPattern("/^TYPE A\s*$/", "200");
		$control->addPattern("/^LIST zzz\s*$/", "964");
		
		try
		{
			$ftp->rawlist("zzz");
			$this->fail();
		}
		catch (FtpAlternative_FtpException $ex)
		{
			$this->assertFalse($transfer->connected());
			
			$this->assertEquals(964, $ex->getCode());
			$this->assertContains("rawlist(): LIST command returned", $ex->getMessage());
		}
		
		/// command returned noresp
		$this->prepareConnection();
		$control->addPattern("/^PASV\s*$/", "227 (192,2,0,124,48,58)");
		$control->addPattern("/^TYPE A\s*$/", "200");
		$control->addPattern("/^LIST zzz\s*$/", null);
		
		try
		{
			$ftp->rawlist("zzz");
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertFalse($transfer->connected());
			
			$this->assertEquals('RuntimeException', get_class($ex));
			$this->assertContains("recvline is empty", $ex->getMessage());
		}
		
		/// transfer recv error
		$this->prepareConnection();
		$control->addPattern("/^PASV\s*$/", "227 (192,2,0,124,48,58)");
		$control->addPattern("/^TYPE A\s*$/", "200");
		$control->addPattern("/^LIST zzz\s*$/", "150");
		
		$transfer->onRecvAll(function() {
			throw new RuntimeException("recv error");
		});
		
		try
		{
			$ftp->rawlist("zzz");
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertFalse($transfer->connected());
			
			$this->assertEquals('RuntimeException', get_class($ex));
			$this->assertContains("recv error", $ex->getMessage());
		}
		
		/// transfer returned invalid respcode
		$this->prepareConnection();
		$control->addPattern("/^PASV\s*$/", "227 (192,2,0,124,48,58)");
		$control->addPattern("/^TYPE A\s*$/", "200");
		$control->addPattern("/^LIST zzz\s*$/", "150");
		
		$transfer->onRecvAll(function() {
			return "123\r\nabc";
		});
		
		$transfer->onClose(function() use($control) {
			$control->addRecvline("957");
		});
		
		try
		{
			$ftp->rawlist("zzz");
			$this->fail();
		}
		catch (FtpAlternative_FtpException $ex)
		{
			$this->assertFalse($transfer->connected());
			
			$this->assertEquals(957, $ex->getCode());
			$this->assertContains("rawlist(): LIST complete returned", $ex->getMessage());
		}
		
		/// transfer returned noresp
		$this->prepareConnection();
		$control->addPattern("/^PASV\s*$/", "227 (192,2,0,124,48,58)");
		$control->addPattern("/^TYPE A\s*$/", "200");
		$control->addPattern("/^LIST zzz\s*$/", "150");
		
		$transfer->onRecvAll(function() {
			return "123\r\nabc";
		});
		
		try
		{
			$ftp->rawlist("zzz");
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertFalse($transfer->connected());
			
			$this->assertEquals('RuntimeException', get_class($ex));
			$this->assertContains("recvline is empty", $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	function pwd()
	{
		$this->prepareConnection();
		$ftp = $this->ftp;
		$control = $this->control;
		
		$control->addPattern("@^PWD\s*$@", "257 \"/aa/bb/cc\"");
		$pwd = $ftp->pwd();
		$this->assertSame("/aa/bb/cc", $pwd);
		
		/// invalid_respcode
		$control->addPattern("@^PWD\s*$@", "952 \"/aa/bb/cc\"");
		
		try
		{
			$ftp->pwd();
			$this->fail();
		}
		catch (FtpAlternative_FtpException $ex)
		{
			$this->assertEquals(952, $ex->getCode());
			$this->assertContains("pwd(): PWD command returned", $ex->getMessage());
		}
		
		/// no responce
		$control->addPattern("@^PWD\s*$@", null);
		
		try
		{
			$ftp->pwd();
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertEquals('RuntimeException', get_class($ex));
			$this->assertContains("recvline is empty", $ex->getMessage());
		}
		
		/// cannot parse
		$control->addPattern("@^PWD\s*$@", "257 \"/aa/bb/cc");
		
		try
		{
			$ftp->pwd();
			$this->fail();
		}
		catch (FtpAlternative_FtpException $ex)
		{
			$this->assertEquals(257, $ex->getCode());
			$this->assertContains("pwd(): PWD cannot parsd", $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	function chdir()
	{
		$this->prepareConnection();
		$ftp = $this->ftp;
		$control = $this->control;
		
		$control->addPattern("@^CWD +aa/bb/cc\s*$@", "250 aa/bb/cc");
		$ftp->chdir("aa/bb/cc");
		
		/// invalid_respcode
		$control->addPattern("@^CWD +aa/bb/cc\s*$@", "914 aa/bb/cc");
		
		try
		{
			$ftp->chdir("aa/bb/cc");
			$this->fail();
		}
		catch (FtpAlternative_FtpException $ex)
		{
			$this->assertEquals(914, $ex->getCode());
			$this->assertContains("chdir(): CWD command returned", $ex->getMessage());
		}
		
		/// no responce
		$control->addPattern("@^CWD +aa/bb/cc\s*$@", null);
		
		try
		{
			$ftp->chdir("aa/bb/cc");
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertEquals('RuntimeException', get_class($ex));
			$this->assertContains("recvline is empty", $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	function mkdir()
	{
		$this->prepareConnection();
		$ftp = $this->ftp;
		$control = $this->control;
		
		$control->addPattern("@^MKD +aa/bb/cc\s*$@", "257 \"aa/bb/cc\"");
		$ftp->mkdir("aa/bb/cc");
		
		/// invalid_respcode
		$control->addPattern("@^MKD +aa/bb/cc\s*$@", "964 \"aa/bb/cc\"");
		
		try
		{
			$ftp->mkdir("aa/bb/cc");
			$this->fail();
		}
		catch (FtpAlternative_FtpException $ex)
		{
			$this->assertEquals(964, $ex->getCode());
			$this->assertContains("mkdir(): MKD command returned", $ex->getMessage());
		}
		
		/// no responce
		$control->addPattern("@^MKD +aa/bb/cc\s*$@", null);
		
		try
		{
			$ftp->mkdir("aa/bb/cc");
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertEquals('RuntimeException', get_class($ex));
			$this->assertContains("recvline is empty", $ex->getMessage());
		}
		
		/// cannot parse
		$control->addPattern("@^MKD +aa/bb/cc\s*$@", "257 \"aa/bb/cc");
		
		try
		{
			$ftp->mkdir("aa/bb/cc");
			$this->fail();
		}
		catch (FtpAlternative_FtpException $ex)
		{
			$this->assertEquals(257, $ex->getCode());
			$this->assertContains("mkdir(): MKD cannot parsd", $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	function rmdir()
	{
		$this->prepareConnection();
		$ftp = $this->ftp;
		$control = $this->control;
		
		$control->addPattern("@^RMD +aa/bb/cc\s*$@", "250");
		$ftp->rmdir("aa/bb/cc");
		
		/// invalid_respcode
		$control->addPattern("@^RMD +aa/bb/cc\s*$@", "964");
		
		try
		{
			$ftp->rmdir("aa/bb/cc");
			$this->fail();
		}
		catch (FtpAlternative_FtpException $ex)
		{
			$this->assertEquals(964, $ex->getCode());
			$this->assertContains("rmdir(): RMD command returned", $ex->getMessage());
		}
		
		/// no responce
		$control->addPattern("@^RMD +aa/bb/cc\s*$@", null);
		
		try
		{
			$ftp->rmdir("aa/bb/cc");
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertEquals('RuntimeException', get_class($ex));
			$this->assertContains("recvline is empty", $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	function chmod()
	{
		$this->prepareConnection();
		$ftp = $this->ftp;
		$control = $this->control;
		
		$control->addPattern("@^SITE CHMOD +777 +hoge.txt\s*$@", "200");
		$ftp->chmod("hoge.txt", 0777);
		
		/// invalid_respcode
		$control->addPattern("@^SITE CHMOD +777 +hoge.txt\s*$@", "982");
		
		try
		{
			$ftp->chmod("hoge.txt", 0777);
			$this->fail();
		}
		catch (FtpAlternative_FtpException $ex)
		{
			$this->assertEquals(982, $ex->getCode());
			$this->assertContains("chmod(): SITE CHMOD command returned", $ex->getMessage());
		}
		
		/// no responce
		$control->addPattern("@^SITE CHMOD +777 +hoge.txt\s*$@", null);
		
		try
		{
			$ftp->chmod("hoge.txt", 0777);
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertEquals('RuntimeException', get_class($ex));
			$this->assertContains("recvline is empty", $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	function delete()
	{
		$this->prepareConnection();
		$ftp = $this->ftp;
		$control = $this->control;
		
		$control->addPattern("@^DELE +hoge.txt\s*$@", "250");
		$ftp->delete("hoge.txt");
		
		/// invalid_respcode
		$control->addPattern("@^DELE +hoge.txt\s*$@", "924");
		
		try
		{
			$ftp->delete("hoge.txt");
			$this->fail();
		}
		catch (FtpAlternative_FtpException $ex)
		{
			$this->assertEquals(924, $ex->getCode());
			$this->assertContains("delete(): DELE command returned", $ex->getMessage());
		}
		
		/// no responce
		$control->addPattern("@^DELE +hoge.txt\s*$@", null);
		
		try
		{
			$ftp->delete("hoge.txt");
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertEquals('RuntimeException', get_class($ex));
			$this->assertContains("recvline is empty", $ex->getMessage());
		}
	}
}
