<?php
/**
 * @author ng
 * @group realserver
 * @group server
 */
class FtpAlternative_RealServerTest extends PHPUnit_Framework_TestCase implements FtpAlternative_RealServerTest_Config
{
	function init_empty(FtpAlternative_FtpClient $ftp)
	{
		$this->rmdir_f($ftp, __CLASS__);
		$ftp->mkdir(__CLASS__);
		$ftp->chdir(__CLASS__);
	}
	
	public function rmdir_f(FtpAlternative_FtpClient $ftp, $dir)
	{
		try
		{
			$ftp->delete($dir);
			return;
		}
		catch (FtpAlternative_FtpException $ex)
		{}
		
		try
		{
			$ftp->chdir($dir);
		}
		catch (FtpAlternative_FtpException $ex)
		{
			return;
		}
		
		$list = $ftp->nlist(".");
		
		foreach ($list as $fn)
		{
			if (preg_match("/^\.+$/", $fn) == 0)
			{
				$this->rmdir_f($ftp, $fn);
			}
		}
		
		$ftp->chdir("../");
		
		$ftp->rmdir($dir);
	}
	
	/**
	 * @test
	 */
	function success()
	{
		$ftp = new FtpAlternative_FtpClient();
		
		try
		{
			$ftp->connect(self::HOST, self::PORT, 5);
			$ftp->login(self::USER, self::PASS);
			
			$this->init_empty($ftp);
			
			$data = uniqid();
			$ftp->put("a.txt", $data);
			$this->assertSame($data, $ftp->get("a.txt"));
			
			try
			{
				$ftp->mkdir("a.txt");
				$this->fail();
			}
			catch (FtpAlternative_FtpException $ex)
			{
				$this->assertEquals(550, $ex->getCode());
			}
			
			$ftp->chmod("a.txt", 0700);
			$list = $ftp->rawlist("a.txt");
			$this->assertContains("rwx------", $list[0]);
			
			try
			{
				$ftp->chmod("b.txt", 0700);
				$this->fail();
			}
			catch (FtpAlternative_FtpException $ex)
			{
				$this->assertEquals(550, $ex->getCode());
			}
			
			try
			{
				$ftp->get("b.txt");
				$this->fail();
			}
			catch (FtpAlternative_FtpException $ex)
			{
				$this->assertEquals(550, $ex->getCode());
			}
			
			$list = $ftp->nlist(".");
			$this->assertContains("a.txt", $list);
			$ftp->delete("a.txt");
			$list = $ftp->nlist(".");
			$this->assertNotContains("a.txt", $list);
			
			///
			try
			{
				$ftp->chdir("aa");
				$this->fail();
			}
			catch (FtpAlternative_FtpException $ex)
			{
				$this->assertEquals(550, $ex->getCode());
				$this->assertStringMatchesFormat('chdir(): CWD command returned "%s"', $ex->getMessage());
			}
			
			$ftp->mkdir("aa");
			$ftp->chdir("aa");
			
			$this->assertStringEndsWith("/aa", $ftp->pwd());
			
			///
			$ftp->mkdir("xxx");
			
			try
			{
				$ftp->put("xxx", "xxx");
				$this->fail();
			}
			catch (FtpAlternative_FtpException $ex)
			{
				$this->assertEquals(550, $ex->getCode());
			}
			
			$ftp->rmdir("xxx");
			
			try
			{
				$ftp->rmdir("xxx");
				$this->fail();
			}
			catch (FtpAlternative_FtpException $ex)
			{
				$this->assertEquals(550, $ex->getCode());
			}
			
			///
			try
			{
				$ftp->nlist("zzz");
				$this->fail();
			}
			catch (FtpAlternative_FtpException $ex)
			{
				$this->assertEquals(450, $ex->getCode());
			}
			
			///
			$rawlist = $ftp->rawlist(".");
			$this->assertInternalType("array", $rawlist);
			$this->assertNotEmpty($rawlist);
			
			///
			$ftp->quit();
		}
		catch (RuntimeException $ex)
		{
			$ftp->close();
			throw $ex;
		}
	}
	
	/**
	 * @test
	 */
	function connect_errors()
	{
		$ftp = new FtpAlternative_FtpClient();
		
		try
		{
			$ftp->connect(self::REFUSE_HOST, self::REFUSE_PORT, 1);
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertSame('RuntimeException', get_class($ex));
			$this->assertContains("Connection refused", $ex->getMessage());
		}
		
		try
		{
			$ftp->connect(self::HTTP_HOST, self::HTTP_PORT, 1);
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertSame('RuntimeException', get_class($ex));
			$this->assertContains("fgets(): unknown error", $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	function login_errors()
	{
		$ftp = new FtpAlternative_FtpClient();
		
		$ftp->connect(self::HOST, self::PORT, 5);
		
		try
		{
			$ftp->login(self::INVALID_USER, self::INVALID_PASS);
			$this->fail();
		}
		catch (FtpAlternative_FtpException $ex)
		{
			$this->assertEquals(530, $ex->getCode());
		}
		
		try
		{
			$ftp->login("", "");
			$this->fail();
		}
		catch (FtpAlternative_FtpException $ex)
		{
			$this->assertEquals(500, $ex->getCode());
		}
	}
}
