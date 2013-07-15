<?php
/**
 * @package   ngyuki\FtpClient
 * @copyright 2012 Toshiyuki Goto <ngyuki.ts@gmail.com>
 * @author    Toshiyuki Goto <ngyuki.ts@gmail.com>
 * @license   http://www.opensource.org/licenses/mit-license.php  MIT License
 * @link      https://github.com/ngyuki/php-ftp-client
 */

namespace ngyuki\FtpClient;

use ngyuki\FtpClient\FileInfo\Directory;
use ngyuki\FtpClient\FileInfo\File;

/**
 * @package   ngyuki\FtpClient
 * @copyright 2012 Toshiyuki Goto <ngyuki.ts@gmail.com>
 * @author    Toshiyuki Goto <ngyuki.ts@gmail.com>
 * @license   http://www.opensource.org/licenses/mit-license.php  MIT License
 * @link      https://github.com/ngyuki/php-ftp-client
 */
class ListParser
{
    public function parse($out)
    {
        $list = explode("\n", $out);

        return $this->parseByArray($list);
    }

    public function parseByArray(array $list)
    {
        $pat1 = '/(?:(d)|.)([rwxts-]{9})\s+(\w+)\s+([\w\d-()?.]+)\s+([\w\d-()?.]+)\s+(\w+)\s+(\S+\s+\S+\s+\S+)\s+(.+)/';
        $pat2 = '/^(.+):$/';

        $arr = new \ArrayObject();

        $cwd = $arr;

        foreach ($list as $line)
        {
            if (preg_match($pat1, $line, $mat))
            {
                if ($cwd)
                {
                    $is_dir = $mat[1] === 'd';
                    $size = $mat[6];
                    $time = $mat[7];
                    $name = $mat[8];

                    $time = strtotime($time);

                    if (preg_match('/^\.+$/', $name))
                    {
                        continue;
                    }

                    if ($is_dir)
                    {
                        $file = new Directory($name, $size, $time);
                    }
                    else
                    {
                        $file = new File($name, $size, $time);
                    }

                    $cwd[$name] = $file;
                }
            }
            else if (preg_match($pat2, $line, $mat))
            {
                $dirs = explode("/", $mat[1]);

                $cwd = $arr;

                foreach ($dirs as $dir)
                {
                    if (!$cwd->offsetExists($dir))
                    {
                        $cwd = null;
                        break;
                    }

                    $dir = $cwd[$dir];

                    if (!$dir instanceof Directory)
                    {
                        $cwd = null;
                        break;
                    }

                    $cwd = $dir->getFiles();
                }
            }
        }

        return $arr;
    }
}
