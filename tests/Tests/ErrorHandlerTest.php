<?php
namespace Tests;

use ngyuki\FtpClient\ErrorHandler;
use PHPUnit\Framework\TestCase;

/**
 * @author ngyuki
 */
class ErrorHandlerTest extends TestCase
{
    /**
     * @test
     * @expectedException \RuntimeException
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
     * @expectedException \PHPUnit\Framework\Error\Warning
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
     * @expectedException \PHPUnit\Framework\Error\Warning
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
