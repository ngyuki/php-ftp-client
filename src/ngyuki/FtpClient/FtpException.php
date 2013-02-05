<?php
/**
 * @package   ngyuki\FtpClient
 * @copyright 2012 tsyk goto
 * @author    tsyk goto
 * @license   http://www.opensource.org/licenses/mit-license.php  MIT License
 * @link      https://github.com/ngyuki/php-ftp-client
 */

namespace ngyuki\FtpClient;

/**
 * @package   ngyuki\FtpClient
 * @copyright 2012 tsyk goto
 * @author    tsyk goto
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
