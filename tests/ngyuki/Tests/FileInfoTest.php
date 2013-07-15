<?php
namespace ngyuki\Tests;

use ngyuki\FtpClient\FileInfo\File;
use ngyuki\FtpClient\FileInfo\Directory;

/**
 * @author ngyuki
 */
class FileInfoTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function file_()
    {
        $file = new File("f", 123, time());

        assertFalse($file->isDir());
        assertTrue($file->isFile());
    }
    /**
     * @test
     */
    public function dir_()
    {
        $dir = new Directory("d", 123, time());

        assertTrue($dir->isDir());
        assertFalse($dir->isFile());
    }
}
