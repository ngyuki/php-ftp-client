<?php
/**
 * @package   ngyuki\FtpClient
 * @copyright 2012 Toshiyuki Goto <ngyuki.ts@gmail.com>
 * @author    Toshiyuki Goto <ngyuki.ts@gmail.com>
 * @license   http://www.opensource.org/licenses/mit-license.php  MIT License
 * @link      https://github.com/ngyuki/php-ftp-client
 */

namespace ngyuki\FtpClient\FileInfo;

/**
 * @package   ngyuki\FtpClient
 * @copyright 2012 Toshiyuki Goto <ngyuki.ts@gmail.com>
 * @author    Toshiyuki Goto <ngyuki.ts@gmail.com>
 * @license   http://www.opensource.org/licenses/mit-license.php  MIT License
 * @link      https://github.com/ngyuki/php-ftp-client
 */
class Directory extends File
{
    /**
     * @var \ArrayObject
     */
    private $_files;

    public function isDir()
    {
        return true;
    }

    public function isFile()
    {
        return false;
    }

    public function getFiles()
    {
        if ($this->_files === null)
        {
            $this->_files = new \ArrayObject();
        }

        return $this->_files;
    }
}
