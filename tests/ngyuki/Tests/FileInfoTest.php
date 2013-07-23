<?php
namespace ngyuki\Tests;

use ngyuki\FtpClient\FileInfo;

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
        $file = new FileInfo("fff", 123, 123456, FileInfo::FILE);

        assertFalse($file->isDir());
        assertTrue($file->isFile());
    }
    /**
     * @test
     */
    public function dir_()
    {
        $dir = new FileInfo("ddd", 123, 123456, FileInfo::DIRECTORY);

        assertTrue($dir->isDir());
        assertFalse($dir->isFile());
    }
}
