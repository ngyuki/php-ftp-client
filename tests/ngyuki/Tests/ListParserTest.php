<?php
namespace ngyuki\Tests;

use ngyuki\FtpClient\ListParser;
use ngyuki\FtpClient\FileInfo\File;
use ngyuki\FtpClient\FileInfo\Directory;

/**
 * @author ngyuki
 */
class ListParserTest extends \PHPUnit_Framework_TestCase
{
    private function textize(\ArrayObject $files, $indent = 0)
    {
        $str = "";

        /* @var $file File */
        foreach ($files as $file)
        {
            $file instanceof Directory;

            $name = $file->getName();

            if ($file->isDir())
            {
                $name .= "/";
            }

            $fmt = "%5s%20s%{$indent}s  %s\n";

            $date = date('Y-m-d H:i:s', $file->getTime());

            $str .= sprintf($fmt, $file->getSize(), $date, "", $name);

            if ($file->isDir())
            {
                $str .= $this->textize($file->getFiles(), $indent + 2);
            }
        }

        return $str;
    }

    /**
     * @test
     */
    public function test()
    {
        $out = file_get_contents(__DIR__ . '/_files/list-r.txt');

        $obj = new ListParser();
        $arr = $obj->parse($out);

        $exp = new \ArrayObject();

        $exp['aa'] = call_user_func(function(){
            $dir = new Directory('aa', 4096, strtotime("2013-07-11 01:02:00"));

            $files = $dir->getFiles();
            $files['cc'] = call_user_func(function(){
                $dir = new Directory('cc', 4096, strtotime("2012-10-25 00:00:00"));

                $files = $dir->getFiles();
                $files['z.txt'] = new File('x.txt', 5, strtotime("2013-06-04 01:57:00"));
                $files['.hta']  = new File('.hta',  9, strtotime("2013-07-14 10:44:00"));

                return $dir;
            });

            $files['y.txt'] = new File('y.txt', 9, strtotime("2013-07-13 10:45:00"));

            return $dir;
        });

        $exp['bb'] = call_user_func(function(){
            $dir = new Directory('bb', 4096, strtotime("2012-10-28 00:00:00"));
            return $dir;
        });

        $exp['z.txt'] = new File('z.txt', 123456789, strtotime("2013-07-11 01:02:00"));


        $this->assertEquals($this->textize($exp), $this->textize($arr));
    }
}
