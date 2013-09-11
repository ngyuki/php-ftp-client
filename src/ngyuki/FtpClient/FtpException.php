<?php
namespace ngyuki\FtpClient;

/**
 * @package   ngyuki\FtpClient
 * @copyright 2012 ngyuki <ngyuki.ts@gmail.com>
 * @author    ngyuki <ngyuki.ts@gmail.com>
 * @license   http://www.opensource.org/licenses/mit-license.php  MIT License
 * @link      https://github.com/ngyuki/php-ftp-client
 */
class FtpException extends \RuntimeException
{
    /**
     * @var FtpResponse レスポンスオブジェクト
     */
    private $_response;

    /**
     * 文字列から例外を作成
     *
     * @return FtpException
     */
    public static function fromString($line)
    {
        $response = FtpResponse::fromString($line);

        if ($response === null)
        {
            throw new \InvalidArgumentException("unable parse response string.");
        }

        return new self($response->getMessage(), $response);
    }

    /**
     * コンストラクタ
     *
     * @param string $message
     * @param FtpResponse $response
     */
    public function __construct($message, FtpResponse $response)
    {
        parent::__construct($message, $response->code);

        $this->_response = $response;
    }

    /**
     * レスポンスオブジェクトを取得
     *
     * @return FtpResponse
     */
    public function getResponse()
    {
        return $this->_response;
    }
}
