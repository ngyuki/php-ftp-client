<?php
namespace Tests;

use ngyuki\FtpClient\FtpResponse;

/**
 * @author ngyuki
 */
class FtpResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function test()
    {
        $resp = new FtpResponse(123, "abc", "htrsaef");

        $this->assertSame(123, $resp->code);
        $this->assertSame(123, $resp->getCode());

        $this->assertSame("abc", $resp->mesg);
        $this->assertSame("abc", $resp->getMessage());

        $this->assertSame("htrsaef", $resp->line);
        $this->assertSame("htrsaef", $resp->getResponseLine());
        $this->assertSame("htrsaef", (string)$resp);

        $resp = new FtpResponse(456, "xyz");

        $this->assertSame(456, $resp->code);
        $this->assertSame(456, $resp->getCode());

        $this->assertSame("xyz", $resp->mesg);
        $this->assertSame("xyz", $resp->getMessage());

        $this->assertSame("456 xyz", $resp->line);
        $this->assertSame("456 xyz", $resp->getResponseLine());
        $this->assertSame("456 xyz", (string)$resp);
    }
}
