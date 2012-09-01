<?php
/**
 * @author ng
 */
interface FtpAlternative_RealServerTest_Config
{
	const HOST = "127.0.0.1";
	const PORT = 21;
	const USER = "hoge";
	const PASS = "piyo";
	
	const INVALID_USER = "xxx";
	const INVALID_PASS = "xxx";
	
	const REFUSE_HOST = "127.0.0.1";
	const REFUSE_PORT = 12345;
	
	const HTTP_HOST = "127.0.0.1";
	const HTTP_PORT = 80;
}
