<?php
namespace ngyuki\Tests;

use ngyuki\FtpClient\FtpClient;
use ngyuki\FtpClient\FtpException;
use RuntimeException;

/**
 * @author ngyuki
 * @group ftpserver
 */
class RealServerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * テストに先立って接続とディレクトリを初期化する
     */
    function initFtpClient()
    {
        $host = getenv('FTP_HOST');
        $port = getenv('FTP_PORT');
        $user = getenv('FTP_USER');
        $pass = getenv('FTP_PASS');
        $base = getenv('FTP_BASE');

        if (strlen($host) == 0 || strlen($port) == 0 || strlen($user) == 0 || strlen($pass) == 0 || strlen($base) == 0)
        {
            $this->markTestSkipped("require env FTP_HOST, FTP_PORT, FTP_USER, FTP_PASS, FTP_BASE");
        }

        $dir = str_replace("\\", "_", __CLASS__);

        $base = escapeshellarg($base . DIRECTORY_SEPARATOR . $dir);
        exec("rm -fr $base");

        $ftp = new FtpClient();

        try
        {
            $ftp->connect($host, (int)$port, 5);
            $ftp->login($user, $pass);
            $ftp->mkdir($dir);
            $ftp->chdir($dir);

            return $ftp;
        }
        catch (\Exception $ex)
        {
            $ftp->close();
            throw $ex;
        }
    }

    /**
     * @test
     */
    function success()
    {
        $ftp = $this->initFtpClient();

        try
        {
            $data = uniqid();
            $ftp->put("a.txt", $data);
            $this->assertSame($data, $ftp->get("a.txt"));

            $ftp->site("CHMOD 0755 a.txt");

            try
            {
                $ftp->mkdir("a.txt");
                $this->fail();
            }
            catch (FtpException $ex)
            {
                $this->assertEquals(550, $ex->getCode());
            }

            $ftp->chmod("a.txt", 0700);
            $list = $ftp->rawlist("a.txt");
            $this->assertContains("rwx------", $list[0]);

            try
            {
                $ftp->chmod("b.txt", 0700);
                $this->fail();
            }
            catch (FtpException $ex)
            {
                $this->assertEquals(550, $ex->getCode());
            }

            try
            {
                $ftp->get("b.txt");
                $this->fail();
            }
            catch (FtpException $ex)
            {
                $this->assertEquals(550, $ex->getCode());
            }

            $list = $ftp->nlist(".");
            $this->assertContains("a.txt", $list);
            $ftp->delete("a.txt");
            $list = $ftp->nlist(".");
            $this->assertNotContains("a.txt", $list);

            ///
            try
            {
                $ftp->chdir("aa");
                $this->fail();
            }
            catch (FtpException $ex)
            {
                $this->assertEquals(550, $ex->getCode());
                $this->assertStringMatchesFormat('chdir(): CWD command returned "%s"', $ex->getMessage());
            }

            $ftp->mkdir("aa");
            $ftp->chdir("aa");

            $this->assertStringEndsWith("/aa", $ftp->pwd());

            ///
            $ftp->mkdir("xxx");

            try
            {
                $ftp->put("xxx", "xxx");
                $this->fail();
            }
            catch (FtpException $ex)
            {
                $this->assertEquals(550, $ex->getCode());
            }

            $ftp->rmdir("xxx");

            try
            {
                $ftp->rmdir("xxx");
                $this->fail();
            }
            catch (FtpException $ex)
            {
                $this->assertEquals(550, $ex->getCode());
            }

            ///
            try
            {
                $ftp->nlist("zzz");
                $this->fail();
            }
            catch (FtpException $ex)
            {
                $this->assertEquals(450, $ex->getCode());
            }

            ///
            $rawlist = $ftp->rawlist(".");
            $this->assertInternalType("array", $rawlist);
            $this->assertNotEmpty($rawlist);

            ///
            $ftp->quit();
        }
        catch (RuntimeException $ex)
        {
            $ftp->close();
            throw $ex;
        }
    }

    /**
     * @test
     */
    function connect_refuse()
    {
        $host = getenv('FTP_HOST');
        $port = getenv('REFUSE_PORT');

        if (strlen($host) == 0 || strlen($port) == 0)
        {
            $this->markTestSkipped("require env FTP_HOST, REFUSE_PORT");
        }

        $ftp = new FtpClient();

        try
        {
            // ポートが閉じていて拒否される
            $ftp->connect($host, (int)$port, 1);
            $this->fail();
        }
        catch (RuntimeException $ex)
        {
            $this->assertSame('RuntimeException', get_class($ex));
            $this->assertContains("Connection refused", $ex->getMessage());
        }
    }

    /**
     * @test
     */
    function connect_nothing()
    {
        $host = getenv('FTP_HOST');
        $port = getenv('NEVER_PORT');

        if (strlen($host) == 0 || strlen($port) == 0)
        {
            $this->markTestSkipped("require env FTP_HOST, NEVER_PORT");
        }

        $ftp = new FtpClient();

        try
        {
            // ポートは開いているが応答が無い
            $ftp->connect($host, (int)$port, 1);
            $this->fail();
        }
        catch (RuntimeException $ex)
        {
            $this->assertSame('RuntimeException', get_class($ex));
            $this->assertContains("fgets(): timeout", $ex->getMessage());
        }
    }

    /**
     * @test
     */
    function login_errors()
    {
        $host = getenv('FTP_HOST');
        $port = getenv('FTP_PORT');
        $user = getenv('INVALID_USER');
        $pass = getenv('INVALID_PASS');

        if (strlen($host) == 0 || strlen($port) == 0 || strlen($user) == 0 || strlen($pass) == 0)
        {
            $this->markTestSkipped("require env FTP_HOST, FTP_PORT, INVALID_USER, INVALID_PASS");
        }

        $ftp = new FtpClient();

        $ftp->connect($host, (int)$port, 5);

        try
        {
            $ftp->login($user, $pass);
            $this->fail();
        }
        catch (FtpException $ex)
        {
            $this->assertEquals(530, $ex->getCode());
        }

        try
        {
            $ftp->login("", "");
            $this->fail();
        }
        catch (FtpException $ex)
        {
            $this->assertEquals(500, $ex->getCode());
        }
    }

    /**
     * @test
     */
    function getList_ok()
    {
        $ftp = $this->initFtpClient();

        $ftp->mkdir("zxc");
        $ftp->put(".abc", "");
        $ftp->put("zxc/123", "");

        $list = $ftp->getList(".");

        $list = array_values($list->getArrayCopy());
        $keys = array_map(function ($file) { return $file->getName(); }, $list);
        $list = array_combine($keys, $list);

        $this->assertCount(2, $list);
        $this->assertNotEmpty($list['.abc']);
        $this->assertNotEmpty($list['zxc']);

        $list = $ftp->getList("zxc");

        $list = array_values($list->getArrayCopy());
        $keys = array_map(function ($file) { return $file->getName(); }, $list);
        $list = array_combine($keys, $list);

        $this->assertCount(1, $list);
        $this->assertNotEmpty($list['zxc/123']);
    }

    /**
     * @test
     */
    function getRecursiveList_ok()
    {
        $ftp = $this->initFtpClient();

        $ftp->mkdir("zxc");
        $ftp->mkdir("zxc/.abc");

        $list = $ftp->getRecursiveList(".");

        $list = array_values($list->getArrayCopy());
        $keys = array_map(function ($file) { return $file->getName(); }, $list);
        $list = array_combine($keys, $list);

        $this->assertCount(2, $list);
        $this->assertNotEmpty($list['zxc']);
        $this->assertNotEmpty($list['zxc/.abc']);

        $list = $ftp->getRecursiveList("zxc");

        $list = array_values($list->getArrayCopy());
        $keys = array_map(function ($file) { return $file->getName(); }, $list);
        $list = array_combine($keys, $list);

        $this->assertCount(1, $list);
        $this->assertNotEmpty($list['zxc/.abc']);
    }

    /**
     * @test
     */
    function rename_()
    {
        $ftp = $this->initFtpClient();

        try
        {
            $data = uniqid();
            $ftp->put("a.txt", $data);
            $ftp->rename("a.txt", "b.txt");

            $this->assertSame($data, $ftp->get("b.txt"));

            $ftp->quit();
        }
        catch (RuntimeException $ex)
        {
            $ftp->close();
            throw $ex;
        }
    }

    /**
     * @test
     * @expectedException ngyuki\FtpClient\FtpException
     * @expectedExceptionMessage rename(): RNFR command returned
     */
    function rename_err_rnfr()
    {
        $ftp = $this->initFtpClient();

        try
        {
            $ftp->rename("x.txt", "b.txt");
            $ftp->quit();
        }
        catch (RuntimeException $ex)
        {
            $ftp->close();
            throw $ex;
        }
    }

    /**
     * @test
     * @expectedException ngyuki\FtpClient\FtpException
     * @expectedExceptionMessage rename(): RNTO command returned
     */
    function rename_err_rnto()
    {
        $ftp = $this->initFtpClient();

        try
        {
            $data = uniqid();
            $ftp->put("a.txt", $data);
            $ftp->mkdir("b.txt");
            $ftp->rename("a.txt", "b.txt");
            $ftp->quit();
        }
        catch (RuntimeException $ex)
        {
            $ftp->close();
            throw $ex;
        }
    }
}
