<?php
/**
 * @package   FtpAlternative
 * @copyright 2012 tsyk goto (@ngyuki)
 * @author    tsyk goto <ngyuki.ts@gmail.com>
 * @license   http://www.opensource.org/licenses/mit-license.php  MIT License
 * @link      https://github.com/ngyuki/FtpAlternative
 */

/**
 * @package   FtpAlternative
 * @copyright 2012 tsyk goto (@ngyuki)
 * @author    tsyk goto <ngyuki.ts@gmail.com>
 * @license   http://www.opensource.org/licenses/mit-license.php  MIT License
 * @link      https://github.com/ngyuki/FtpAlternative
 */
class FtpAlternative_FtpException extends RuntimeException
{
	/**
	 * @var FtpAlternative_Response レスポンスオブジェクト
	 */
	private $_response;
	
	/**
	 * コンストラクタ
	 *
	 * @param string $message
	 * @param FtpAlternative_Response $response
	 */
	public function __construct($message, FtpAlternative_Response $response)
	{
		parent::__construct($message, $response->code);
		
		$this->_response = $response;
	}
	
	/**
	 * レスポンスオブジェクトを取得
	 *
	 * @return FtpAlternative_Response
	 */
	public function getResponse()
	{
		return $this->_response;
	}
}
