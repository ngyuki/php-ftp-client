<?php
namespace ngyuki\Tests;

use ngyuki\FtpClient\FtpClient;
use ngyuki\FtpClient\FtpException;
use RuntimeException;

/**
 * @author ng
 * @group ftpserver
 */
class RealServerTest extends \PHPUnit_Framework_TestCase
{
    function init_empty(FtpClient $ftp)
    {
        $this->rmdir_f($ftp, __CLASS__);
        $ftp->mkdir(__CLASS__);
        $ftp->chdir(__CLASS__);
    }

    public function rmdir_f(FtpClient $ftp, $dir)
    {
        try
        {
            $ftp->delete($dir);
            return;
        }
        catch (FtpException $ex)
        {}

        try
        {
            $ftp->chdir($dir);
        }
        catch (FtpException $ex)
        {
            return;
        }

        $list = $ftp->nlist(".");

        foreach ($list as $fn)
        {
            if (preg_match("/^\.+$/", $fn) == 0)
            {
                $this->rmdir_f($ftp, $fn);
            }
        }

        $ftp->chdir("../");

        $ftp->rmdir($dir);
    }

    /**
     * @test
     */
    function success()
    {
        if (defined('FTP_HOST') == false || strlen(FTP_HOST) == 0)
        {
            $this->markTestSkipped("require const FTP_HOST");
        }

        $ftp = new FtpClient();

        try
        {
            $ftp->connect(FTP_HOST, (int)FTP_PORT, 5);
            $ftp->login(FTP_USER, FTP_PASS);

            $this->init_empty($ftp);

            $data = uniqid();
            $ftp->put("a.txt", $data);
            $this->assertSame($data, $ftp->get("a.txt"));

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
        if (defined('REFUSE_HOST') == false || strlen(REFUSE_HOST) == 0)
        {
            $this->markTestSkipped("require const REFUSE_HOST");
        }

        $ftp = new FtpClient();

        try
        {
            // ポートが閉じていて拒否される
            $ftp->connect(REFUSE_HOST, (int)REFUSE_PORT, 1);
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
        if (defined('HTTP_HOST') == false || strlen(HTTP_HOST) == 0)
        {
            $this->markTestSkipped("require const HTTP_HOST");
        }

        $ftp = new FtpClient();

        try
        {
            // ポートは開いているが応答が無い
            $ftp->connect(HTTP_HOST, (int)HTTP_PORT, 1);
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
        if (defined('FTP_HOST') == false || strlen(FTP_HOST) == 0)
        {
            $this->markTestSkipped("require const FTP_HOST");
        }

        $ftp = new FtpClient();

        $ftp->connect(FTP_HOST, (int)FTP_PORT, 5);

        try
        {
            $ftp->login(INVALID_USER, INVALID_PASS);
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
}
