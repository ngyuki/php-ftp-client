<?php
namespace ngyuki\FtpClient;

/**
 * @package   ngyuki\FtpClient
 * @copyright 2012 ngyuki <ngyuki.ts@gmail.com>
 * @author    ngyuki <ngyuki.ts@gmail.com>
 * @license   http://www.opensource.org/licenses/mit-license.php  MIT License
 * @link      https://github.com/ngyuki/php-ftp-client
 */
class FileInfo
{
    private $_type;
    private $_name;
    private $_size;
    private $_time;

    const FILE = 1;
    const DIRECTORY = 2;

    public function __construct($name, $size, $time, $type)
    {
        $this->_name = $name;
        $this->_size = $size;
        $this->_time = $time;
        $this->_type = $type;
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
        return $this->_type === self::DIRECTORY;
    }

    public function isFile()
    {
        return $this->_type === self::FILE;
    }
}
