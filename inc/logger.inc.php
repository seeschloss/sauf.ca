<?php

class Logger
	{
	public static function __callStatic($function, $arguments)
		{
		$level = $function;
		$message = $arguments[0];

		$file = $GLOBALS['config']['logging']['directory'] . date('Y-m-d').'.log';
		
		if (!file_exists($GLOBALS['config']['logging']['directory']))
			{
			mkdir($GLOBALS['config']['logging']['directory']);
			}

		file_put_contents($file, gmdate('Y-m-d\TH:i:s').' [' . $level . '] ' . $message . "\n", FILE_APPEND);
		}
	}
