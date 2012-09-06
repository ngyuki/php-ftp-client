<?php
/**
 * @package   FtpAlternative
 * @copyright 2012 tsyk goto (@ngyuki)
 * @author    tsyk goto <ngyuki.ts@gmail.com>
 * @license   http://www.opensource.org/licenses/mit-license.php  MIT License
 * @link      https://github.com/ngyuki/FtpAlternative
 */

spl_autoload_register(function($name) {
	
	$ns = 'FtpAlternative_';
	$len = strlen($ns);
	
	if (strncmp($name, $ns, $len) === 0)
	{
		$name = substr($name, $len);
		$fn = __DIR__ . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $name) . '.php';
		
		if (is_readable($fn))
		{
			require $fn;
		}
	}
});
