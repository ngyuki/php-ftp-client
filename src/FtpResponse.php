<?php
namespace ngyuki\FtpClient;

/**
 * @package   ngyuki\FtpClient
 * @copyright 2012 ngyuki <ngyuki.ts@gmail.com>
 * @author    ngyuki <ngyuki.ts@gmail.com>
 * @license   http://www.opensource.org/licenses/mit-license.php  MIT License
 * @link      https://github.com/ngyuki/php-ftp-client
 *
 * @property string $line
 * @property int    $code
 * @property string $mesg
 */
class FtpResponse
{
    /**
     * @var string レスポンスライン
     */
    private $_line;

    /**
     * @var int レスポンスコード
     */
    private $_code;

    /**
     * @var string メッセージ
     */
    private $_mesg;

    /**
     * 文字列からレスポンスオブジェクトを作成
     *
     * @param string $line
     * @return FtpResponse|null
     */
    public static function fromString($line)
    {
        assert(is_string($line));

        $code = null;
        $mesg = "";

        $arr = explode(" ", $line, 2);

        if (count($arr) < 2)
        {
            list ($code) = $arr;
        }
        else
        {
            list ($code, $mesg) = $arr;
        }

        if (strlen($code) !== 3)
        {
            return null;
        }

        if (ctype_digit($code) === false)
        {
            return null;
        }

        return new self((int)$code, (string)$mesg);
    }

    /**
     * コンストラクタ
     *
     * @param string $code
     * @param string $mesg
     * @param string $line
     */
    public function __construct($code, $mesg, $line = null)
    {
        assert(is_null($code) || is_int($code));
        assert(is_string($mesg));

        if ($line === null)
        {
            $line = "$code $mesg";
        }

        $this->_line = $line;
        $this->_code = $code;
        $this->_mesg = $mesg;
    }

    public function getResponseLine()
    {
        return $this->_line;
    }

    public function getCode()
    {
        return $this->_code;
    }

    public function getMessage()
    {
        return $this->_mesg;
    }

    /**
     * マジックメソッド __get
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        $name = '_' . $name;
        return $this->{$name};
    }

    /**
     * レスポンスラインを返す
     *
     * @return string
     */
    public function __toString()
    {
        return $this->_line;
    }
}
