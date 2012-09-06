<?php
require_once dirname(__DIR__) . '/class/FtpAlternative/autoload.php';

spl_autoload_register(function($name) {
	
	$name = str_replace("_", DIRECTORY_SEPARATOR, $name) . ".php";
	$dirs = array(__DIR__, __DIR__  . DIRECTORY_SEPARATOR . 'class');
	
	foreach ($dirs as $dir)
	{
		$fn = $dir . DIRECTORY_SEPARATOR . $name;
		
		if (is_readable($fn))
		{
			require $fn;
			return;
		}
	}
});

if (is_readable(__DIR__ . DIRECTORY_SEPARATOR . 'config.php'))
{
	require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
}
else
{
	require __DIR__ . DIRECTORY_SEPARATOR . 'config.dist.php';
}
