<?php
namespace ngyuki\Tests;

use ngyuki\FtpClient\ErrorHandler;

/**
 * @author ngyuki
 */
class ErrorHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @expectedException RuntimeException
     * @expectedExceptionCode E_USER_WARNING
     * @expectedExceptionMessage afwe04a54t
     */
    public function test()
    {
        $obj = new ErrorHandler();
        user_error("afwe04a54t", E_USER_WARNING);
    }

    /**
     * @test
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionCode E_USER_WARNING
     * @expectedExceptionMessage afwe04a54t
     */
    public function restore()
    {
        $obj = new ErrorHandler();
        $obj->restore();

        user_error("afwe04a54t", E_USER_WARNING);
    }

    /**
     * @test
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionCode E_USER_WARNING
     * @expectedExceptionMessage afwe04a54t
     */
    public function scope()
    {
        call_user_func(function() {
            $obj = new ErrorHandler();
        });

        user_error("afwe04a54t", E_USER_WARNING);
    }
}
