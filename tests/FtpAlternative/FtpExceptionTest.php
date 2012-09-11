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
		$resp = new FtpAlternative_FtpResponse(123, "abc", "123 abc");
		$obj = new FtpAlternative_FtpException("xyz", $resp);
		
		$this->assertSame(123, $obj->getCode());
		$this->assertSame("xyz", $obj->getMessage());
		$this->assertSame($resp, $obj->getResponse());
	}
}
