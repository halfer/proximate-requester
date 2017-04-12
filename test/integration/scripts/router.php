<?php

$root = realpath(__DIR__ . '/../../..');

if ($_SERVER["REQUEST_URI"] == '/server-check')
{
	// This is a check to ensure the web server is up
	echo 'OK';
}
else
{
	// Send page requests to the web app
	$docRoot = $root . "/test/integration/web";
	$page = null;
	if ($_SERVER["REQUEST_URI"] == '/')
	{
		$page = $docRoot . "/index.php";
	}
	else
	{
		// @todo Ensure we have no .. naughtiness in our URL
		$possiblePath = $docRoot . $_SERVER["REQUEST_URI"];
		if (file_exists($possiblePath))
		{
			$page = $possiblePath;
		}
	}

	if ($page)
	{
		include $page;
	}
	else
	{
		// Send back 404 page/header
		header("HTTP/1.0 404 Not Found");
		echo "Not found\n";
	}
}
