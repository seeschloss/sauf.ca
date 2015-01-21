<?php

class Logger
	{
	public static function notice($message)
		{
		trigger_error($message);
		}

	public static function warning($message)
		{
		trigger_error($message);
		}

	public static function error($message)
		{
		trigger_error($message);
		}
	}
