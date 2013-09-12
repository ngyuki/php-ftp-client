<?php
namespace ngyuki\FtpClient;

/**
 * @package   ngyuki\FtpClient
 * @copyright 2012 ngyuki <ngyuki.ts@gmail.com>
 * @author    ngyuki <ngyuki.ts@gmail.com>
 * @license   http://www.opensource.org/licenses/mit-license.php  MIT License
 * @link      https://github.com/ngyuki/php-ftp-client
 */
class ErrorHandler
{
    /**
     * @var mixed 元のエラーハンドラ
     */
    private $_original = false;

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        $this->_original = set_error_handler(self::_handler());
    }

    /**
     * デストラクタ
     */
    public function __destruct()
    {
        $this->restore();
    }

    /**
     * エラーハンドラを元に戻す
     */
    public function restore()
    {
        if ($this->_original !== false)
        {
            restore_error_handler();
            $this->_original = false;
        }
    }

    /**
     * エラーハンドラ
     *
     * @return Closure
     */
    private static function _handler()
    {
        // 5.4 で $this を束縛しないように
        return function($errno, $errstr) {
            throw new TransportException($errstr, $errno);
            //throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        };
    }
}
