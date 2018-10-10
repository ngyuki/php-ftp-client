<?php
namespace ngyuki\FtpClient\Tests;

use ngyuki\FtpClient\FtpClient;
use PHPUnit\Framework\TestCase;

/**
 * @author ngyuki
 * @group posix
 * @requires function pcntl_fork
 */
class DummyServerTest extends TestCase
{
    private function action($function)
    {
        $self = $this;

        return function ($stream) use ($self, $function) {
            $function($self, $stream);
        };
    }

    public function recvline($stream, $pattern)
    {
        $data = fgets($stream);

        if (preg_match($pattern, $data) == 0)
        {
            $data = trim($data);
            fputs($stream, "999 recv [$data]");
            throw new \Exception("999 recv [$data]");
        }
    }

    public function sendline($stream, $data)
    {
        fputs($stream, $data . "\r\n");
    }

    /**
     * @test
     */
    public function test()
    {
        $dummy = new DummyServer();
        $dummy->run(12345, $this->action(function (DummyServerTest $self, $stream) {

            $self->sendline($stream, "220 FTP Server ready.");

            //
            $self->recvline($stream, "/^USER as0d5a\s*/");
            $self->sendline($stream, "331 Password required for as0d5a");

            //
            $self->recvline($stream, "/^PASS fg54sdf\s*/");
            $self->sendline($stream, "230 User hoge logged in.");

            //
            $self->recvline($stream, "/^QUIT\s*/");
            $self->sendline($stream, "221 Goodbye.");
        }));

        $ftp = new FtpClient();
        $ftp->connect("127.0.0.1", 12345, 10);

        $ftp->login("as0d5a", "fg54sdf");

        $ftp->quit();

        $this->assertTrue(true);
    }

    /**
     * @test
     * @expectedException \ngyuki\FtpClient\FtpException
     * @expectedExceptionCode 981
     * @expectedExceptionMessage connect(): returned "981 a54rga50sdf5"
     */
    public function connect_error_respcode()
    {
        $dummy = new DummyServer();
        $dummy->run(11111, $this->action(function(DummyServerTest $self, $stream) {
            $self->sendline($stream, "981 a54rga50sdf5");
        }));

        $ftp = new FtpClient();
        $ftp->connect("127.0.0.1", 11111, 10);
    }

    /**
     * @test
     * @expectedException \ngyuki\FtpClient\FtpException
     * @expectedExceptionCode 944
     * @expectedExceptionMessage quit(): QUIT command returned "944 gsd04g5sdag5a0"
     */
    public function quit_error_respcode()
    {
        $dummy = new DummyServer();
        $dummy->run(11111, $this->action(function (DummyServerTest $self, $stream) {
            $self->sendline($stream, "220 FTP Server ready.");
            //
            $self->recvline($stream, "/^QUIT\s*/");
            $self->sendline($stream, "944 gsd04g5sdag5a0");
        }));

        $ftp = new FtpClient();
        $ftp->connect("127.0.0.1", 11111, 10);
        $ftp->quit();
    }

    /**
     * @test
     */
    public function login_nopass()
    {
        $dummy = new DummyServer();
        $dummy->run(11111, $this->action(function (DummyServerTest $self, $stream) {
            $self->sendline($stream, "220 FTP Server ready.");
            //
            $self->recvline($stream, "/^USER as0d5a\s*/");
            $self->sendline($stream, "230 hoge");
        }));

        $ftp = new FtpClient();
        $ftp->connect("127.0.0.1", 11111, 10);
        $ftp->login("as0d5a", "");

        $this->assertTrue(true);
    }

    /**
     * @test
     * @expectedException \ngyuki\FtpClient\FtpException
     * @expectedExceptionCode 917
     * @expectedExceptionMessage login(): USER command returned "917 asdfafr"
     */
    public function login_error_user_respcode()
    {
        $dummy = new DummyServer();
        $dummy->run(11111, $this->action(function(DummyServerTest $self, $stream) {
            $self->sendline($stream, "220 FTP Server ready.");
            //
            $self->recvline($stream, "/^USER as0d5a\s*/");
            $self->sendline($stream, "917 asdfafr");
        }));

        $ftp = new FtpClient();
        $ftp->connect("127.0.0.1", 11111, 10);
        $ftp->login("as0d5a", "");
    }

    /**
     * @test
     * @expectedException \ngyuki\FtpClient\FtpException
     * @expectedExceptionCode 914
     * @expectedExceptionMessage login(): PASS command returned "914 asfdsa4d1a0."
     */
    public function login_error_pass_respcode()
    {
        $dummy = new DummyServer();
        $dummy->run(11111, $this->action(function(DummyServerTest $self, $stream) {

            $self->sendline($stream, "220 FTP Server ready.");

            //
            $self->recvline($stream, "/^USER as0d5a\s*/");
            $self->sendline($stream, "331 Password required for as0d5a");

            //
            $self->recvline($stream, "/^PASS fg54sdf\s*/");
            $self->sendline($stream, "914 asfdsa4d1a0.");
        }));

        $ftp = new FtpClient();
        $ftp->connect("127.0.0.1", 11111, 10);
        $ftp->login("as0d5a", "fg54sdf");
    }
}
