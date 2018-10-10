<?php
namespace Tests;

use ngyuki\FtpClient\TransportSocket;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @author ngyuki
 * @group posix
 * @requires function pcntl_fork
 */
class TransportSocketTest extends TestCase
{
    protected function createTransport()
    {
        return new TransportSocket();
    }

    /**
     * @test
     */
    public function test()
    {
        $data1 = "123456789\r\n";
        $data2 = str_repeat("x", 1000*1000) . "\r\n";

        $server = new DummyServer();
        $server->run(11111, function ($stream) use ($data1, $data2) {

            $data = fgets($stream);
            if ($data !== $data1)
            {
                return;
            }

            fputs($stream, $data);

            $data = fgets($stream);
            if ($data !== $data2)
            {
                return;
            }

            fputs($stream, $data2);
        });

        $transport = $this->createTransport();
        $this->assertFalse($transport->connected());

        $transport->connect('127.0.0.1', 11111, 3);
        $this->assertTrue($transport->connected());

        $transport->send($data1);

        $line = $transport->recvline();
        $this->assertSame($data1, $line);

        //

        $transport->send($data2);

        $line = $transport->recvline();
        $this->assertSame($data2, $line);

        $transport->close();
        $this->assertFalse($transport->connected());
    }

    /**
     * @test
     */
    public function connect_refused()
    {
        $transport = $this->createTransport();

        $time = microtime(true);

        try
        {
            $transport->connect('127.0.0.1', 1, 3);
            $this->fail();
        }
        catch (RuntimeException $ex)
        {
            $this->assertLessThan(0.1, microtime(true) - $time);
            $this->assertContains("connect", $ex->getMessage());
            $this->assertContains("Connection refused", $ex->getMessage());
        }
    }

    /**
     * @test
     * @group longtime
     */
    public function connect_timeout()
    {
        $transport = $this->createTransport();

        $time = microtime(true);

        try
        {
            $transport->connect('192.0.2.123', 11111, 2);
            $this->fail();
        }
        catch (RuntimeException $ex)
        {
            $this->assertLessThan(2.1, microtime(true) - $time);
            $this->assertContains("connect", $ex->getMessage());
        }
    }

    /**
     * @test
     */
    public function recvall()
    {
        $data = "123\r\naaaa\r\nbbb";

        $server = new DummyServer();
        $server->run(11111, function ($stream) use ($data) {
            fputs($stream, $data);
        });

        $transport = $this->createTransport();

        $transport->connect('127.0.0.1', 11111, 3);

        $recv = $transport->recvall();
        $this->assertSame($data, $recv);

        $transport->close();
    }

    /**
     * @test
     * @group longtime
     */
    public function recvall_timeout()
    {
        $server = new DummyServer();
        $server->run(11111, function ($stream) {
            sleep(10);
        });

        $transport = $this->createTransport();
        $transport->connect('127.0.0.1', 11111, 2);

        $time = microtime(true);

        try
        {
            $recv = $transport->recvall();
            $this->fail("recvall ... \"$recv\"");
        }
        catch (RuntimeException $ex)
        {
            $this->assertLessThan(2.1, microtime(true) - $time);
            $this->assertGreaterThan(1.9, microtime(true) - $time);
            $this->assertContains("socket_recv()", $ex->getMessage());
        }
    }

    /**
     * @test
     * @group longtime
     */
    public function recvline_timeout()
    {
        $server = new DummyServer();
        $server->run(11111, function ($stream) {
            sleep(10);
        });

        $transport = $this->createTransport();
        $transport->connect('127.0.0.1', 11111, 2);

        $time = microtime(true);

        try
        {
            $transport->recvline();
            $this->fail();
        }
        catch (RuntimeException $ex)
        {
            $this->assertLessThan(2.1, microtime(true) - $time);
            $this->assertGreaterThan(1.9, microtime(true) - $time);
            $this->assertContains("socket_recv()", $ex->getMessage());
        }
    }

    /**
     * @test
     */
    public function send_error()
    {
        $server = new DummyServer();
        $server->run(11111, function ($stream) {
            sleep(10);
        });

        $transport = $this->createTransport();
        $transport->connect('127.0.0.1', 11111, 1);

        $server->term();

        $data = str_repeat("x", 1024*1024) . "\r\n";

        try
        {
            $transport->send($data);
            $this->fail();
        }
        catch (RuntimeException $ex)
        {
            $this->assertContains("socket_send()", $ex->getMessage());
        }
    }
}
