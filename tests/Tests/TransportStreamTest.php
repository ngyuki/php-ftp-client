<?php
namespace Tests;

use ngyuki\FtpClient\TransportStream;
use RuntimeException;

/**
 * @author ngyuki
 * @group posix
 * @requires function pcntl_fork
 */
class TransportStreamTest extends \PHPUnit_Framework_TestCase
{
    protected function createTransport()
    {
        return new TransportStream();
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
        // 接続が拒否された場合（開いていないポートへ接続）

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
        // 接続がタイムアウトした場合（存在しないIPアドレスへ接続）

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
        // サーバが応答を返さずにタイムアウトした場合

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
            $this->assertContains("fgets(): timeout", $ex->getMessage());
        }
    }

    /**
     * @test
     * @group longtime
     */
    public function recvall_shutdown()
    {
        // サーバがソケットをシャットダウンした場合

        $server = new DummyServer();
        $server->run(11111, function ($stream) {
            stream_socket_shutdown($stream, STREAM_SHUT_RDWR);
        });

        $transport = $this->createTransport();
        $transport->connect('127.0.0.1', 11111, 2);

        $time = microtime(true);

        $recv = $transport->recvall();
        $this->assertSame("", $recv);

        $time = microtime(true);

        try
        {
            $transport->recvall();
            $this->fail();
        }
        catch (RuntimeException $ex)
        {
            $this->assertLessThan(0.1, microtime(true) - $time);
            $this->assertContains("fgets(): end of stream", $ex->getMessage());
        }
    }

    /**
     * @test
     * @group longtime
     * @group one
     */
    public function recvall_bigdata()
    {
        // 1 回の fgets では受信しきれない大きいデータ

        $data = str_repeat("x", 1024*100);

        $server = new DummyServer();
        $server->run(11111, function ($stream) use ($data) {
            fputs($stream, $data);
        });

        $transport = $this->createTransport();
        $transport->connect('127.0.0.1', 11111, 2);

        $time = microtime(true);

        $recv = $transport->recvall();
        $this->assertSame($data, $recv);
    }

    /**
     * @test
     * @group longtime
     */
    public function recvline_timeout()
    {
        // サーバが応答を返さずにタイムアウトした場合

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
            $this->assertContains("fgets(): timeout", $ex->getMessage());
        }
    }

    /**
     * @test
     * @group longtime
     */
    public function recvline_shutdown()
    {
        // サーバがソケットをシャットダウンした場合

        $server = new DummyServer();
        $server->run(11111, function ($stream) {
            stream_socket_shutdown($stream, STREAM_SHUT_RDWR);
        });

        $transport = $this->createTransport();
        $transport->connect('127.0.0.1', 11111, 2);

        $recv = $transport->recvline();
        $this->assertSame("", $recv);

        $time = microtime(true);

        try
        {
            $transport->recvline();
            $this->fail();
        }
        catch (RuntimeException $ex)
        {
            $this->assertLessThan(0.1, microtime(true) - $time);
            $this->assertContains("fgets(): end of stream", $ex->getMessage());
        }
    }

    /**
     * @test
     * @group longtime
     */
    public function recvline_bigdata()
    {
        // 1 回の fgets では受信しきれない大きいデータ

        $data = str_repeat("x", 1024*100);

        $server = new DummyServer();
        $server->run(11111, function ($stream) use ($data) {
            fputs($stream, $data);
        });

        $transport = $this->createTransport();
        $transport->connect('127.0.0.1', 11111, 2);

        $recv = $transport->recvline();
        $this->assertSame($data, $recv);

        $time = microtime(true);

        try
        {
            $transport->recvline();
        }
        catch (RuntimeException $ex)
        {
            // EOF に達しているため例外になるが呼び出し元にEOFを判断するすべがない・・・
            //   → 呼び出し元は EOF では無いはずだと思っているわけなので例外のままで構わないとする
            $this->assertLessThan(0.1, microtime(true) - $time);
            $this->assertContains("fgets(): end of stream", $ex->getMessage());
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
            $this->assertContains("fwrite()", $ex->getMessage());
        }
    }
}
