<?php
/**
 * @author ng
 */
class FtpAlternative_ErrorHandlerTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @test
	 */
	public function test()
	{
		$obj = new FtpAlternative_ErrorHandler();
		
		try
		{
			user_error("afwe04a54t", E_USER_WARNING);
			$this->fail();
		}
		catch (RuntimeException $ex)
		{
			$this->assertEquals(E_USER_WARNING, $ex->getCode());
			$this->assertEquals("afwe04a54t", $ex->getMessage());
		}
		
		$obj->restore();
		
		try
		{
			user_error("afwe04a54t", E_USER_WARNING);
			$this->fail();
		}
		catch (PHPUnit_Framework_Error $ex)
		{
			$this->assertEquals(E_USER_WARNING, $ex->getCode());
			$this->assertEquals("afwe04a54t", $ex->getMessage());
		}
	}
	
	/**
	 * @test
	 */
	public function scope()
	{
		try
		{
			call_user_func(function() {
				$obj = new FtpAlternative_ErrorHandler();
			});
			
			user_error("afwe04a54t", E_USER_WARNING);
			$this->fail();
		}
		catch (PHPUnit_Framework_Error $ex)
		{
			$this->assertEquals(E_USER_WARNING, $ex->getCode());
			$this->assertEquals("afwe04a54t", $ex->getMessage());
		}
	}
}
