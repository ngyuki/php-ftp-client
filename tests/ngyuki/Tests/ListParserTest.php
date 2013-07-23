<?php
namespace ngyuki\Tests;

use ngyuki\FtpClient\ListParser;
use ngyuki\FtpClient\FileInfo;

/**
 * @author ngyuki
 */
class ListParserTest extends \PHPUnit_Framework_TestCase
{
    private function textize(\ArrayObject $files)
    {
        $str = "";

        /* @var $file FileInfo */
        foreach ($files as $file)
        {
            $file instanceof FileInfo;

            $name = $file->getName();

            if ($file->isDir())
            {
                $name .= "/";
            }

            $date = date('Y-m-d H:i:s', $file->getTime());

            $str .= sprintf("%5s%20s %s\n", $file->getSize(), $date, $name);
        }

        return $str;
    }

    /**
     * @test
     */
    public function test_01()
    {
        $out = file_get_contents(__DIR__ . '/_files/list-01.txt');
        $base = "";

        $obj = new ListParser();
        $arr = $obj->parse($base, $out);

        $exp = new \ArrayObject();

        $exp[] = new FileInfo('aa', 4096, strtotime('Jul 11 01:02'), FileInfo::DIRECTORY);
        $exp[] = new FileInfo('bb', 4096, strtotime('Oct 28  2012'), FileInfo::DIRECTORY);
        $exp[] = new FileInfo('z.txt', 123456789, strtotime('Jul 11 01:02'), FileInfo::FILE);

        $exp[] = new FileInfo('aa/cc', 4096, strtotime('Oct 25  2012'), FileInfo::DIRECTORY);
        $exp[] = new FileInfo('aa/y.txt', 9, strtotime('Jul 13 10:45'), FileInfo::FILE);

        $exp[] = new FileInfo('aa/cc/x.txt', 5, strtotime('Jun  4 01:57'), FileInfo::FILE);
        $exp[] = new FileInfo('aa/cc/.hta',  9, strtotime('Jul 14 10:44'), FileInfo::FILE);

        $this->assertEquals($this->textize($exp), $this->textize($arr));
    }

    /**
     * @test
     */
    public function test_02()
    {
        $out = file_get_contents(__DIR__ . '/_files/list-02.txt');
        $base = "/pre/base";

        $obj = new ListParser();
        $arr = $obj->parse($base, $out);

        $exp = new \ArrayObject();

        $exp[] = new FileInfo('/pre/base/html', 4096, strtotime('Jul 23 10:21'), FileInfo::DIRECTORY);
        $exp[] = new FileInfo('/pre/base/html/index.html', 6, strtotime('Jul 23 10:21'), FileInfo::FILE);

        $this->assertEquals($this->textize($exp), $this->textize($arr));
    }
}
