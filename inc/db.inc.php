<?php
class DB
	{
	static private $resource;

	function __construct()
		{
		if (!isset(self::$resource))
			{
			self::$resource = new mysqli(
				$GLOBALS['config']['db']['host'],
				$GLOBALS['config']['db']['user'],
				$GLOBALS['config']['db']['password'],
				$GLOBALS['config']['db']['database']
			);
			self::$resource->set_charset("utf8mb4");
			}
		}

	function query($query)
		{
		$result = self::$resource->query($query);
		if ($error = mysqli_error(self::$resource))
			{
			if (class_exists('Logger'))
				{
				Logger::error($error);
				Logger::error("Query was: ".$query);
				}
			else
				{
				trigger_error($error);
				trigger_error("Query was: ".$query);
				}
			}
		return $result;
		}

	function value($query)
		{
		$result = $this->query($query);
		if ($result) while ($row = $result->fetch_array())
			{
			return $row[0];
			}

		return '';
		}

	function escape($string)
		{
		return self::$resource->real_escape_string($string);
		}

	function insert_id()
		{
		return self::$resource->insert_id;
		}
	}

