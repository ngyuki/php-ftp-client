<?php
/**
 * @package   FtpAlternative
 * @copyright 2012 tsyk goto
 * @author    tsyk goto
 * @license   http://www.opensource.org/licenses/mit-license.php  MIT License
 * @link      https://github.com/ngyuki/FtpAlternative
 */

/**
 * @package   FtpAlternative
 * @copyright 2012 tsyk goto
 * @author    tsyk goto
 * @license   http://www.opensource.org/licenses/mit-license.php  MIT License
 * @link      https://github.com/ngyuki/FtpAlternative
 */
class FtpAlternative_FtpResponse
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
	 * コンストラクタ
	 *
	 * @param string $code
	 * @param string $mesg
	 * @param string $line
	 */
	public function __construct($code, $mesg, $line)
	{
		ASSERT(' is_null($code) || is_int($code) ');
		ASSERT(' is_string($mesg) ');
		ASSERT(' is_string($line) ');
		
		$this->_line = $line;
		$this->_code = $code;
		$this->_mesg = $mesg;
	}
	
	/**
	 * マジックメソッド __get
	 *
	 * @param string $name
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
