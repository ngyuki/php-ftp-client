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
	 * @param string $line
	 */
	public function __construct($line)
	{
		ASSERT(' is_string($line) ');
		
		$line = trim($line);
		
		$code = null;
		$mesg = "";
		
		$arr = preg_split("/\s+/", $line, 2);
		
		if (isset($arr[0]))
		{
			$str = $arr[0];
			
			if ((strlen($str) <= 3) && ctype_digit($str))
			{
				$code = (int)$str;
			}
		}
		
		if (isset($arr[1]))
		{
			$mesg = $arr[1];
		}
		
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
