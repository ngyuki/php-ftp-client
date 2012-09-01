<?php
/**
 * @author ng
 */
class FtpAlternative_FtpExceptionTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @test
	 */
	public function test()
	{
		$line = "123 abc";
		$resp = new FtpAlternative_Response($line);
		$obj = new FtpAlternative_FtpException("xyz", $resp);
		
		$this->assertSame(123, $obj->getCode());
		$this->assertSame("xyz", $obj->getMessage());
		$this->assertSame($resp, $obj->getResponse());
	}
}
