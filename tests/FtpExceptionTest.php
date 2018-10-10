<?php
namespace ngyuki\FtpClient\Tests;

use ngyuki\FtpClient\FtpResponse;
use ngyuki\FtpClient\FtpException;
use PHPUnit\Framework\TestCase;

/**
 * @author ngyuki
 */
class FtpExceptionTest extends TestCase
{
    /**
     * @test
     */
    public function test()
    {
        $resp = new FtpResponse(123, "abc", "123 abc");
        $obj = new FtpException("xyz", $resp);

        $this->assertSame(123, $obj->getCode());
        $this->assertSame("xyz", $obj->getMessage());
        $this->assertSame($resp, $obj->getResponse());
    }

    /**
     * @test
     */
    public function fromString_()
    {
        $obj = FtpException::fromString("123 qwe");

        $this->assertSame(123, $obj->getCode());
        $this->assertSame("qwe", $obj->getMessage());
        $this->assertInstanceOf('ngyuki\FtpClient\FtpResponse', $obj->getResponse());
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage unable parse response string.
     */
    public function fromString_invalid()
    {
        $obj = FtpException::fromString("xxx");
    }
}
