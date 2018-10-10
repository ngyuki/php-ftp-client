<?php
namespace ngyuki\FtpClient\Tests;

use ngyuki\FtpClient\FtpClient;
use ngyuki\FtpClient\FtpResponse;
use ngyuki\FtpClient\FtpException;
use PHPUnit\Framework\TestCase;

/**
 * @author ngyuki
 */
class FtpClientTest extends TestCase
{
    /**
     * @var FtpClient
     */
    private $ftp;

    /**
     * @var TransportControlMock
     */
    private $control;

    /**
     * @var TransportTransferMock
     */
    private $transfer;

    protected function setUp()
    {
        $this->control = new TransportControlMock();
        $this->transfer = new TransportTransferMock();
        $this->ftp = new FtpClient($this->control, $this->transfer);
    }

    /**
     * @test
     */
    function construct()
    {
        new FtpClient();
        new FtpClient($this->control);
        new FtpClient($this->control, $this->transfer);

        $this->assertTrue(True);
    }

    /**
     * @test
     */
    function connect()
    {
        $this->control->setListen("192.0.2.123", 12345);
        $this->control->addRecvline("220 connected");

        $this->assertFalse($this->control->connected());
        $this->assertFalse($this->transfer->connected());

        $this->ftp->connect("192.0.2.123", 12345, 5);

        $this->assertTrue($this->control->connected());
        $this->assertFalse($this->transfer->connected());
    }

    /**
     * @test
     */
    function connect_error_invalid_respcode()
    {
        $this->control->setListen("192.0.2.123", 12345);
        $this->control->addRecvline("954 xxx");

        try
        {
            $this->ftp->connect("192.0.2.123", 12345, 5);
        }
        catch (FtpException $ex)
        {
            $this->assertFalse($this->control->connected());
            $this->assertFalse($this->transfer->connected());

            $this->assertEquals($ex->getCode(), 954);
            $this->assertContains("connect(): returned", $ex->getMessage());
        }
    }

    /**
     * @test
     */
    function connect_error_nodigit_respcode()
    {
        $this->control->setListen("192.0.2.123", 12345);
        $this->control->addRecvline("ABC xxx");

        try
        {
            $this->ftp->connect("192.0.2.123", 12345, 5);
        }
        catch (\RuntimeException $ex)
        {
            $this->assertFalse($this->control->connected());
            $this->assertFalse($this->transfer->connected());

            $this->assertEquals('RuntimeException', get_class($ex));
            $this->assertContains("recvline is empty", $ex->getMessage());
        }
    }

    /**
     * @test
     */
    function connect_error_invalid_host()
    {
        $this->control->setListen("192.0.2.123", 12345);

        try
        {
            $this->ftp->connect("192.0.2.124", 12346, 5);
            $this->fail();
        }
        catch (\RuntimeException $ex)
        {
            $this->assertFalse($this->control->connected());
            $this->assertFalse($this->transfer->connected());

            $this->assertEquals('RuntimeException', get_class($ex));
            $this->assertContains("connect host mismatch", $ex->getMessage());
        }
    }

    /**
     * @test
     */
    function close()
    {
        $this->control->setListen("192.0.2.123", 12345);
        $this->control->addRecvline("220 connected");
        $this->ftp->connect("192.0.2.123", 12345, 5);
        $this->transfer->connect("192.0.2.123", 12345, 5);

        $this->assertTrue($this->control->connected());
        $this->assertTrue($this->transfer->connected());

        $this->ftp->close();

        $this->assertFalse($this->control->connected());
        $this->assertFalse($this->transfer->connected());

        /// $control の切断で例外

        $this->control->setListen("192.0.2.123", 12345);
        $this->control->addRecvline("220 connected");
        $this->ftp->connect("192.0.2.123", 12345, 5);
        $this->transfer->connect("192.0.2.123", 12345, 5);

        $this->control->onClose(function () {
            throw new \RuntimeException("asda405das2d4");
        });

        try
        {
            $this->ftp->close();
            $this->fail();
        }
        catch (\RuntimeException $ex)
        {
            $this->control->onClose(null);

            $this->assertEquals('RuntimeException', get_class($ex));
            $this->assertContains("asda405das2d4", $ex->getMessage());

            $this->assertFalse($this->transfer->connected());
        }

        /// $transfer の切断で例外

        $this->control->setListen("192.0.2.123", 12345);
        $this->control->addRecvline("220 connected");
        $this->ftp->connect("192.0.2.123", 12345, 5);
        $this->transfer->connect("192.0.2.123", 12345, 5);

        $this->transfer->onClose(function () {
            throw new \RuntimeException("gerh8884as0ra");
        });

        try
        {
            $this->ftp->close();
            $this->fail();
        }
        catch (\RuntimeException $ex)
        {
            $this->control->onClose(null);
            $this->transfer->onClose(null);

            $this->assertEquals('RuntimeException', get_class($ex));
            $this->assertContains("gerh8884as0ra", $ex->getMessage());

            $this->assertFalse($this->control->connected());
        }

        /// $control と $transfer の切断で例外

        $this->control->setListen("192.0.2.123", 12345);
        $this->control->addRecvline("220 connected");
        $this->ftp->connect("192.0.2.123", 12345, 5);
        $this->transfer->connect("192.0.2.123", 12345, 5);

        $this->control->onClose(function () {
            throw new \RuntimeException("asda405das2d4");
        });

        $this->transfer->onClose(function () {
            throw new \RuntimeException("gerh8884as0ra");
        });

        try
        {
            $this->ftp->close();
            $this->fail();
        }
        catch (\RuntimeException $ex)
        {
            $this->control->onClose(null);
            $this->transfer->onClose(null);

            $this->assertEquals('RuntimeException', get_class($ex));
            $this->assertContains("asda405das2d4", $ex->getMessage());
        }
    }

    /**
     * @test
     */
    function quit()
    {
        $this->control->setListen("192.0.2.123", 12345);
        $this->control->addRecvline("220 connected");
        $this->ftp->connect("192.0.2.123", 12345, 5);

        $this->control->addPattern("/^QUIT\s*$/", "221 bye");
        $this->ftp->quit();

        $this->assertTrue(True);
    }

    /**
     * @test
     * @expectedException \ngyuki\FtpClient\FtpException
     * @expectedExceptionCode 789
     * @expectedExceptionMessage quit(): QUIT command returned
     */
    function quit_error_invalid_respcode()
    {
        // 応答コードが異なる

        $this->control->setListen("192.0.2.123", 12345);
        $this->control->addRecvline("220 connected");
        $this->ftp->connect("192.0.2.123", 12345, 5);

        $this->control->addPattern("/^QUIT\s*$/", "789");

        $this->ftp->quit();
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage recvline is empty
     */
    function quit_error_noresp()
    {
        $this->control->setListen("192.0.2.123", 12345);
        $this->control->addRecvline("220 connected");
        $this->ftp->connect("192.0.2.123", 12345, 5);

        $this->control->addPattern("/^QUIT\s*$/", null);

        $this->ftp->quit();
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage patterns is empty
     */
    function quit_error_senderror()
    {
        $this->control->setListen("192.0.2.123", 12345);
        $this->control->addRecvline("220 connected");
        $this->ftp->connect("192.0.2.123", 12345, 5);

        $this->ftp->quit();
    }

    /**
     * @test
     */
    function quit_no_connect()
    {
        // 未接続ならなにもしない

        $this->assertFalse($this->control->connected());

        $this->ftp->quit();

        $this->assertFalse($this->control->connected());
    }

    /**
     * FTP接続する
     */
    private function prepareConnection()
    {
        $this->ftp->close();

        $this->control->clear();
        $this->transfer->clear();

        $this->control->setListen("192.0.2.123", 12345);
        $this->control->addRecvline("220 connected");

        $this->ftp->connect("192.0.2.123", 12345, 5);
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
        catch (FtpException $ex)
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
        catch (FtpException $ex)
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
        catch (\RuntimeException $ex)
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
        catch (\RuntimeException $ex)
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

        $control->addPattern("/^PASV\s*$/", "227 (192,0,2,124,48,58)");
        $control->addPattern("/^TYPE I\s*$/", "200");
        $control->addPattern("/^RETR hoge.txt\s*$/", "150");

        $self = $this;
        $obj = new \stdClass();
        $obj->no = 0;

        $transfer->onConnect(function($host, $port, $timeout) use ($self, $obj) {
            $self->assertEquals($host, "192.0.2.124");
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
        $control = $this->control;
        $transfer = $this->transfer;

        /// command invalid respcode
        $this->prepareConnection();

        $control->addPattern("/^PASV\s*$/", "227 (192,0,2,124,48,58)");
        $control->addPattern("/^TYPE I\s*$/", "200");
        $control->addPattern("/^RETR hoge.txt\s*$/", "958");

        try
        {
            $this->ftp->get("hoge.txt");
            $this->fail();
        }
        catch (FtpException $ex)
        {
            $this->assertFalse($transfer->connected());

            $this->assertEquals(958, $ex->getCode());
            $this->assertContains("get(): RETR command returned", $ex->getMessage());
        }

        /// command noresp
        $this->prepareConnection();

        $control->addPattern("/^PASV\s*$/", "227 (192,0,2,124,48,58)");
        $control->addPattern("/^TYPE I\s*$/", "200");
        $control->addPattern("/^RETR hoge.txt\s*$/", null);

        try
        {
            $this->ftp->get("hoge.txt");
            $this->fail();
        }
        catch (\RuntimeException $ex)
        {
            $this->assertFalse($transfer->connected());

            $this->assertEquals('RuntimeException', get_class($ex));
            $this->assertContains("recvline is empty", $ex->getMessage());
        }

        /// transfer recv error
        $this->prepareConnection();

        $control = $this->control;
        $transfer = $this->transfer;

        $control->addPattern("/^PASV\s*$/", "227 (192,0,2,124,48,58)");
        $control->addPattern("/^TYPE I\s*$/", "200");
        $control->addPattern("/^RETR hoge.txt\s*$/", "150");

        $transfer->onRecvAll(function() {
            throw new \RuntimeException("transfer recv error");
        });

        try
        {
            $this->ftp->get("hoge.txt");
            $this->fail();
        }
        catch (\RuntimeException $ex)
        {
            $this->assertFalse($transfer->connected());

            $this->assertEquals('RuntimeException', get_class($ex));
            $this->assertContains("transfer recv error", $ex->getMessage());
        }

        /// transfer invalid respcode
        $this->prepareConnection();

        $control->addPattern("/^PASV\s*$/", "227 (192,0,2,124,48,58)");
        $control->addPattern("/^TYPE I\s*$/", "200");
        $control->addPattern("/^RETR hoge.txt\s*$/", "150");

        $transfer->onClose(function() use($control) {
            $control->addRecvline("981");
        });

        try
        {
            $this->ftp->get("hoge.txt");
            $this->fail();
        }
        catch (FtpException $ex)
        {
            $this->assertFalse($transfer->connected());

            $this->assertEquals(981, $ex->getCode());
            $this->assertContains("get(): RETR complete returned", $ex->getMessage());
        }

        /// transfer noresp
        $this->prepareConnection();

        $control->addPattern("/^PASV\s*$/", "227 (192,0,2,124,48,58)");
        $control->addPattern("/^TYPE I\s*$/", "200");
        $control->addPattern("/^RETR hoge.txt\s*$/", "150");

        try
        {
            $this->ftp->get("hoge.txt");
            $this->fail();
        }
        catch (\RuntimeException $ex)
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
        $control = $this->control;
        $transfer = $this->transfer;

        $control->addPattern("/^PASV\s*$/", "227 (192,0,2,124,48,58)");
        $control->addPattern("/^TYPE I\s*$/", "200 Type set to I");
        $control->addPattern("/^STOR hoge.txt\s*$/", "150 Content Scanning Enabled - please wait.");

        $self = $this;
        $obj = new \stdClass();
        $obj->no = 0;

        $transfer->onConnect(function($host, $port, $timeout) use ($self, $obj) {
            $self->assertEquals($host, "192.0.2.124");
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

        $this->ftp->put("hoge.txt", "0123456789abcdef");

        $this->assertFalse($transfer->connected());
        $this->assertSame($obj->connect, 1);
        $this->assertSame($obj->send, 2);
        $this->assertSame($obj->close, 3);
    }

    /**
     * @test
     */
    function put_multiline()
    {
        $this->prepareConnection();
        $control = $this->control;
        $transfer = $this->transfer;

        $control->addPattern("/^PASV\s*$/", "227 (192,0,2,124,48,58)");
        $control->addPattern("/^TYPE I\s*$/", "200 Type set to I");
        $control->addPattern("/^STOR hoge.txt\s*$/", "150 Content Scanning Enabled - please wait.");

        $self = $this;
        $obj = new \stdClass();
        $obj->no = 0;

        $transfer->onConnect(function ($host, $port, $timeout) use ($self, $obj) {
            $self->assertEquals($host, "192.0.2.124");
            $self->assertEquals($port, 12346);
            $obj->connect = ++$obj->no;
        });

        $transfer->onSend(function ($data) use ($self, $obj, $transfer) {
            $self->assertTrue($transfer->connected());
            $self->assertSame("0123456789abcdef", $data);
            $obj->send = ++$obj->no;
        });

        $transfer->onClose(function () use ($self, $obj, $control, $transfer) {
            $self->assertTrue($transfer->connected());
            $control->addRecvline("226- Scanning hoge.txt - (16 bytes).");
            $control->addRecvline("226- Uploading hoge.txt (0 of 16 bytes).");
            $control->addRecvline("226 Content allowed  : transfer complete.");

            $obj->close = ++$obj->no;
        });

        $this->ftp->put("hoge.txt", "0123456789abcdef");

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

        $this->control->addPattern("/^PASV\s*$/", "400 (192,0,2,124,48,58)");

        try
        {
            $this->ftp->put("hoge.txt", "0123456789abcdef");
            $this->fail();
        }
        catch (FtpException $ex)
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

        $this->control->addPattern("/^PASV\s*$/", null);

        try
        {
            $this->ftp->put("hoge.txt", "0123456789abcdef");
            $this->fail();
        }
        catch (\RuntimeException $ex)
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

        $this->control->addPattern("/^PASV\s*$/", "227 (192,0,2,124,48)");

        try
        {
            $this->ftp->put("hoge.txt", "0123456789abcdef");
            $this->fail();
        }
        catch (FtpException $ex)
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

        $this->control->addPattern("/^PASV\s*$/", "227 (256,256,256,256,256,256)");

        try
        {
            $this->ftp->put("hoge.txt", "0123456789abcdef");
            $this->fail();
        }
        catch (FtpException $ex)
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

        $this->control->addPattern("/^PASV\s*$/", "227 (192,0,2,124,48,58)");
        $this->control->addPattern("/^TYPE I\s*$/", "951");

        try
        {
            $this->ftp->put("hoge.txt", "0123456789abcdef");
            $this->fail();
        }
        catch (FtpException $ex)
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

        $this->control->addPattern("/^PASV\s*$/", "227 (192,0,2,124,48,58)");
        $this->control->addPattern("/^TYPE I\s*$/", null);

        try
        {
            $this->ftp->put("hoge.txt", "0123456789abcdef");
            $this->fail();
        }
        catch (\RuntimeException $ex)
        {
            $this->assertEquals('RuntimeException', get_class($ex));
            $this->assertContains("recvline is empty", $ex->getMessage());

            $this->assertFalse($this->transfer->connected());
        }
    }

    /**
     * @test
     */
    function put_error_stor_invalid_respcode()
    {
        $this->prepareConnection();

        $this->control->addPattern("/^PASV\s*$/", "227 (192,0,2,124,48,58)");
        $this->control->addPattern("/^TYPE I\s*$/", "200");
        $this->control->addPattern("/^STOR hoge.txt\s*$/", "945");

        try
        {
            $this->ftp->put("hoge.txt", "0123456789abcdef");
            $this->fail();
        }
        catch (FtpException $ex)
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

        $this->control->addPattern("/^PASV\s*$/", "227 (192,0,2,124,48,58)");
        $this->control->addPattern("/^TYPE I\s*$/", "200");
        $this->control->addPattern("/^STOR hoge.txt\s*$/", null);

        try
        {
            $this->ftp->put("hoge.txt", "0123456789abcdef");
            $this->fail();
        }
        catch (\RuntimeException $ex)
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

        $this->control->addPattern("/^PASV\s*$/", "227 (192,0,2,124,48,58)");
        $this->control->addPattern("/^TYPE I\s*$/", "200");
        $this->control->addPattern("/^STOR hoge.txt\s*$/", "150");

        $this->transfer->onSend(function($data) {
            throw new \RuntimeException("transfer senderror");
        });

        try
        {
            $this->ftp->put("hoge.txt", "0123456789abcdef");
            $this->fail();
        }
        catch (\RuntimeException $ex)
        {
            $this->assertEquals('RuntimeException', get_class($ex));
            $this->assertContains("transfer senderror", $ex->getMessage());

            $this->assertFalse($this->transfer->connected());
        }
    }

    /**
     * @test
     */
    function put_error_transfer_invalid_respcode()
    {
        $this->prepareConnection();
        $control = $this->control;

        $this->control->addPattern("/^PASV\s*$/", "227 (192,0,2,124,48,58)");
        $this->control->addPattern("/^TYPE I\s*$/", "200");
        $this->control->addPattern("/^STOR hoge.txt\s*$/", "150");

        $this->transfer->onClose(function() use($control) {
            $control->addRecvline("952");
        });

        try
        {
            $this->ftp->put("hoge.txt", "0123456789abcdef");
            $this->fail();
        }
        catch (FtpException $ex)
        {
            $this->assertEquals(952, $ex->getCode());
            $this->assertContains("put(): STOR complete returned", $ex->getMessage());

            $this->assertFalse($this->transfer->connected());
        }
    }

    /**
     * @test
     */
    function put_error_transfer_noresp()
    {
        $this->prepareConnection();

        $this->control->addPattern("/^PASV\s*$/", "227 (192,0,2,124,48,58)");
        $this->control->addPattern("/^TYPE I\s*$/", "200");
        $this->control->addPattern("/^STOR hoge.txt\s*$/", "150");

        try
        {
            $this->ftp->put("hoge.txt", "0123456789abcdef");
            $this->fail();
        }
        catch (\RuntimeException $ex)
        {
            $this->assertEquals('RuntimeException', get_class($ex));
            $this->assertContains("recvline is empty", $ex->getMessage());

            $this->assertFalse($this->transfer->connected());
        }
    }

    /**
     * @test
     */
    function rawlist()
    {
        $this->prepareConnection();
        $control = $this->control;
        $transfer = $this->transfer;

        $this->control->addPattern("/^PASV\s*$/", "227 (192,0,2,124,48,58)");
        $this->control->addPattern("/^TYPE A\s*$/", "200");
        $this->control->addPattern("/^LIST zzz\s*$/", "150");

        $self = $this;
        $obj = new \stdClass();
        $obj->no = 0;

        $this->transfer->onConnect(function($host, $port, $timeout) use ($self, $obj) {
            $self->assertEquals($host, "192.0.2.124");
            $self->assertEquals($port, 12346);
            $obj->connect = ++$obj->no;
        });

        $this->transfer->onRecvAll(function() use($self, $obj, $transfer) {
            $self->assertTrue($transfer->connected());
            $obj->send = ++$obj->no;
            return "123\r\nabc";
        });

        $this->transfer->onClose(function() use($self, $obj, $control, $transfer) {
            $self->assertTrue($transfer->connected());
            $control->addRecvline("226");
            $obj->close = ++$obj->no;
        });

        $list = $this->ftp->rawlist("zzz");

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
        $control = $this->control;
        $transfer = $this->transfer;

        $this->control->addPattern("/^PASV\s*$/", "227 (192,0,2,124,48,58)");
        $this->control->addPattern("/^TYPE A\s*$/", "200");
        $this->control->addPattern("/^NLST zzz\s*$/", "150");

        $self = $this;
        $obj = new \stdClass();
        $obj->no = 0;

        $this->transfer->onConnect(function($host, $port, $timeout) use ($self, $obj) {
            $self->assertEquals($host, "192.0.2.124");
            $self->assertEquals($port, 12346);
            $obj->connect = ++$obj->no;
        });

        $this->transfer->onRecvAll(function() use($self, $obj, $transfer) {
            $self->assertTrue($transfer->connected());
            $obj->send = ++$obj->no;
            return "123\r\nabc";
        });

        $this->transfer->onClose(function() use($self, $obj, $control, $transfer) {
            $self->assertTrue($transfer->connected());
            $control->addRecvline("226");
            $obj->close = ++$obj->no;
        });

        $list = $this->ftp->nlist("zzz");

        $this->assertEquals($list, array("123", "abc"));

        $this->assertFalse($this->transfer->connected());
        $this->assertSame($obj->connect, 1);
        $this->assertSame($obj->send, 2);
        $this->assertSame($obj->close, 3);
    }

    /**
     * @test
     */
    function getlist()
    {
        $control = $this->control;
        $transfer = $this->transfer;

        /// empty list
        $this->prepareConnection();
        $control->addPattern("/^PASV\s*$/", "227 (192,0,2,124,48,58)");
        $control->addPattern("/^TYPE A\s*$/", "200");
        $control->addPattern("/^LIST zzz\s*$/", "226");

        $list = $this->ftp->rawlist("zzz");
        $this->assertEmpty($list);

        /// command returned invalid respcode
        $this->prepareConnection();
        $control->addPattern("/^PASV\s*$/", "227 (192,0,2,124,48,58)");
        $control->addPattern("/^TYPE A\s*$/", "200");
        $control->addPattern("/^LIST zzz\s*$/", "964");

        try
        {
            $this->ftp->rawlist("zzz");
            $this->fail();
        }
        catch (FtpException $ex)
        {
            $this->assertFalse($transfer->connected());

            $this->assertEquals(964, $ex->getCode());
            $this->assertContains("rawlist(): LIST command returned", $ex->getMessage());
        }

        /// command returned noresp
        $this->prepareConnection();
        $control->addPattern("/^PASV\s*$/", "227 (192,0,2,124,48,58)");
        $control->addPattern("/^TYPE A\s*$/", "200");
        $control->addPattern("/^LIST zzz\s*$/", null);

        try
        {
            $this->ftp->rawlist("zzz");
            $this->fail();
        }
        catch (\RuntimeException $ex)
        {
            $this->assertFalse($transfer->connected());

            $this->assertEquals('RuntimeException', get_class($ex));
            $this->assertContains("recvline is empty", $ex->getMessage());
        }

        /// transfer recv error
        $this->prepareConnection();
        $control->addPattern("/^PASV\s*$/", "227 (192,0,2,124,48,58)");
        $control->addPattern("/^TYPE A\s*$/", "200");
        $control->addPattern("/^LIST zzz\s*$/", "150");

        $transfer->onRecvAll(function() {
            throw new \RuntimeException("recv error");
        });

        try
        {
            $this->ftp->rawlist("zzz");
            $this->fail();
        }
        catch (\RuntimeException $ex)
        {
            $this->assertFalse($transfer->connected());

            $this->assertEquals('RuntimeException', get_class($ex));
            $this->assertContains("recv error", $ex->getMessage());
        }

        /// transfer returned invalid respcode
        $this->prepareConnection();
        $control->addPattern("/^PASV\s*$/", "227 (192,0,2,124,48,58)");
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
            $this->ftp->rawlist("zzz");
            $this->fail();
        }
        catch (FtpException $ex)
        {
            $this->assertFalse($transfer->connected());

            $this->assertEquals(957, $ex->getCode());
            $this->assertContains("rawlist(): LIST complete returned", $ex->getMessage());
        }

        /// transfer returned noresp
        $this->prepareConnection();
        $control->addPattern("/^PASV\s*$/", "227 (192,0,2,124,48,58)");
        $control->addPattern("/^TYPE A\s*$/", "200");
        $control->addPattern("/^LIST zzz\s*$/", "150");

        $transfer->onRecvAll(function() {
            return "123\r\nabc";
        });

        try
        {
            $this->ftp->rawlist("zzz");
            $this->fail();
        }
        catch (\RuntimeException $ex)
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

        $this->control->addPattern("@^PWD\s*$@", "257 \"/aa/bb/cc\"");
        $pwd = $this->ftp->pwd();
        $this->assertSame("/aa/bb/cc", $pwd);

        /// invalid_respcode
        $this->control->addPattern("@^PWD\s*$@", "952 \"/aa/bb/cc\"");

        try
        {
            $this->ftp->pwd();
            $this->fail();
        }
        catch (FtpException $ex)
        {
            $this->assertEquals(952, $ex->getCode());
            $this->assertContains("pwd(): PWD command returned", $ex->getMessage());
        }

        /// no responce
        $this->control->addPattern("@^PWD\s*$@", null);

        try
        {
            $this->ftp->pwd();
            $this->fail();
        }
        catch (\RuntimeException $ex)
        {
            $this->assertEquals('RuntimeException', get_class($ex));
            $this->assertContains("recvline is empty", $ex->getMessage());
        }

        /// cannot parse
        $this->control->addPattern("@^PWD\s*$@", "257 \"/aa/bb/cc");

        try
        {
            $this->ftp->pwd();
            $this->fail();
        }
        catch (FtpException $ex)
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

        $this->control->addPattern("@^CWD +aa/bb/cc\s*$@", "250 aa/bb/cc");
        $this->ftp->chdir("aa/bb/cc");

        /// invalid_respcode
        $this->control->addPattern("@^CWD +aa/bb/cc\s*$@", "914 aa/bb/cc");

        try
        {
            $this->ftp->chdir("aa/bb/cc");
            $this->fail();
        }
        catch (FtpException $ex)
        {
            $this->assertEquals(914, $ex->getCode());
            $this->assertContains("chdir(): CWD command returned", $ex->getMessage());
        }

        /// no responce
        $this->control->addPattern("@^CWD +aa/bb/cc\s*$@", null);

        try
        {
            $this->ftp->chdir("aa/bb/cc");
            $this->fail();
        }
        catch (\RuntimeException $ex)
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

        $this->control->addPattern("@^MKD +aa/bb/cc\s*$@", "257 \"aa/bb/cc\"");
        $this->ftp->mkdir("aa/bb/cc");

        /// invalid_respcode
        $this->control->addPattern("@^MKD +aa/bb/cc\s*$@", "964 \"aa/bb/cc\"");

        try
        {
            $this->ftp->mkdir("aa/bb/cc");
            $this->fail();
        }
        catch (FtpException $ex)
        {
            $this->assertEquals(964, $ex->getCode());
            $this->assertContains("mkdir(): MKD command returned", $ex->getMessage());
        }

        /// no responce
        $this->control->addPattern("@^MKD +aa/bb/cc\s*$@", null);

        try
        {
            $this->ftp->mkdir("aa/bb/cc");
            $this->fail();
        }
        catch (\RuntimeException $ex)
        {
            $this->assertEquals('RuntimeException', get_class($ex));
            $this->assertContains("recvline is empty", $ex->getMessage());
        }

        /// cannot parse
        $this->control->addPattern("@^MKD +aa/bb/cc\s*$@", "257 \"aa/bb/cc");

        try
        {
            $this->ftp->mkdir("aa/bb/cc");
            $this->fail();
        }
        catch (FtpException $ex)
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

        $this->control->addPattern("@^RMD +aa/bb/cc\s*$@", "250");
        $this->ftp->rmdir("aa/bb/cc");

        /// invalid_respcode
        $this->control->addPattern("@^RMD +aa/bb/cc\s*$@", "964");

        try
        {
            $this->ftp->rmdir("aa/bb/cc");
            $this->fail();
        }
        catch (FtpException $ex)
        {
            $this->assertEquals(964, $ex->getCode());
            $this->assertContains("rmdir(): RMD command returned", $ex->getMessage());
        }

        /// no responce
        $this->control->addPattern("@^RMD +aa/bb/cc\s*$@", null);

        try
        {
            $this->ftp->rmdir("aa/bb/cc");
            $this->fail();
        }
        catch (\RuntimeException $ex)
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

        $this->control->addPattern("@^SITE CHMOD +777 +hoge.txt\s*$@", "200");
        $this->ftp->chmod("hoge.txt", 0777);

        /// invalid_respcode
        $this->control->addPattern("@^SITE CHMOD +777 +hoge.txt\s*$@", "982");

        try
        {
            $this->ftp->chmod("hoge.txt", 0777);
            $this->fail();
        }
        catch (FtpException $ex)
        {
            $this->assertEquals(982, $ex->getCode());
            $this->assertContains("chmod(): SITE CHMOD command returned", $ex->getMessage());
        }

        /// no responce
        $this->control->addPattern("@^SITE CHMOD +777 +hoge.txt\s*$@", null);

        try
        {
            $this->ftp->chmod("hoge.txt", 0777);
            $this->fail();
        }
        catch (\RuntimeException $ex)
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

        $this->control->addPattern("@^DELE +hoge.txt\s*$@", "250");
        $this->ftp->delete("hoge.txt");

        /// invalid_respcode
        $this->control->addPattern("@^DELE +hoge.txt\s*$@", "924");

        try
        {
            $this->ftp->delete("hoge.txt");
            $this->fail();
        }
        catch (FtpException $ex)
        {
            $this->assertEquals(924, $ex->getCode());
            $this->assertContains("delete(): DELE command returned", $ex->getMessage());
        }

        /// no responce
        $this->control->addPattern("@^DELE +hoge.txt\s*$@", null);

        try
        {
            $this->ftp->delete("hoge.txt");
            $this->fail();
        }
        catch (\RuntimeException $ex)
        {
            $this->assertEquals('RuntimeException', get_class($ex));
            $this->assertContains("recvline is empty", $ex->getMessage());
        }
    }

    /**
     * @test
     */
    function site()
    {
        $this->prepareConnection();

        $this->control->addPattern("@^SITE +hoge.txt\s*$@", "200");
        $this->ftp->site("hoge.txt");

        /// invalid_respcode
        $this->control->addPattern("@^SITE +hoge.txt\s*$@", "947");

        try
        {
            $this->ftp->site("hoge.txt");
            $this->fail();
        }
        catch (FtpException $ex)
        {
            $this->assertEquals(947, $ex->getCode());
            $this->assertContains("site(): SITE command returned", $ex->getMessage());
        }

        /// no responce
        $this->control->addPattern("@^SITE +hoge.txt\s*$@", null);

        try
        {
            $this->ftp->site("hoge.txt");
            $this->fail();
        }
        catch (\RuntimeException $ex)
        {
            $this->assertEquals('RuntimeException', get_class($ex));
            $this->assertContains("recvline is empty", $ex->getMessage());
        }
    }
}
