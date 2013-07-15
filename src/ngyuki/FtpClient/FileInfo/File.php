<?php
namespace ngyuki\FtpClient\FileInfo;

/**
 * @package   ngyuki\FtpClient
 * @copyright 2012 ngyuki <ngyuki.ts@gmail.com>
 * @author    ngyuki <ngyuki.ts@gmail.com>
 * @license   http://www.opensource.org/licenses/mit-license.php  MIT License
 * @link      https://github.com/ngyuki/php-ftp-client
 */
class File
{
    private $_name;
    private $_size;
    private $_time;

    public function __construct($name, $size, $time)
    {
        $this->_name = $name;
        $this->_size = $size;
        $this->_time = $time;
    }

    public function getName()
    {
        return $this->_name;
    }

    public function getSize()
    {
        return $this->_size;
    }

    public function getTime()
    {
        return $this->_time;
    }

    public function isDir()
    {
        return false;
    }

    public function isFile()
    {
        return true;
    }
}
