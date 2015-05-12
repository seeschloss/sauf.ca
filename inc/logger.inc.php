<?php

class Logger
	{
	public static function __callStatic($function, $arguments)
		{
		$level = $function;
		$message = $arguments[0];

		$file = __DIR__ . '/../logs/' . date('Y-m-d').'.log';
		
		if (!file_exists(__DIR__ . '/../logs/'))
			{
			mkdir(__DIR__ . '/../logs/');
			}

		file_put_contents($file, gmdate('Y-m-d\TH:i:s').' [' . $level . '] ' . $message . "\n", FILE_APPEND);
		}
	}
