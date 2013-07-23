<?php
namespace ngyuki\FtpClient;

use ngyuki\FtpClient\FileInfo;

/**
 * @package   ngyuki\FtpClient
 * @copyright 2012 ngyuki <ngyuki.ts@gmail.com>
 * @author    ngyuki <ngyuki.ts@gmail.com>
 * @license   http://www.opensource.org/licenses/mit-license.php  MIT License
 * @link      https://github.com/ngyuki/php-ftp-client
 */
class ListParser
{
    public function parse($base, $out)
    {
        $list = explode("\n", $out);

        return $this->parseByArray($base, $list);
    }

    public function parseByArray($base, array $list)
    {
        $pat1 = '/(?:(d)|.)([rwxts-]{9})\s+(\w+)\s+([\w\d-()?.]+)\s+([\w\d-()?.]+)\s+(\w+)\s+(\S+\s+\S+\s+\S+)\s+(.+)/';
        $pat2 = '/^(.+):$/';

        $arr = new \ArrayObject();

        if ($base === '.')
        {
            $base = "";
        }

        $base = rtrim($base, '/');

        if (strlen($base) !== 0)
        {
            $base .= '/';
        }

        foreach ($list as $line)
        {
            if (preg_match($pat1, $line, $mat))
            {
                $type = $mat[1] === 'd' ? FileInfo::DIRECTORY : FileInfo::FILE;
                $size = $mat[6];
                $time = $mat[7];
                $name = $mat[8];

                $time = strtotime($time);

                if (preg_match('/^\.+$/', $name))
                {
                    continue;
                }

                $name = $base . $name;

                $arr[] = $file = new FileInfo($name, $size, $time, $type);
            }
            else if (preg_match($pat2, $line, $mat))
            {
                $base = $mat[1];
                $base = rtrim($base, '/');

                if (strlen($base) !== 0)
                {
                    $base .= '/';
                }
            }
        }

        return $arr;
    }
}
