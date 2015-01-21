<?php
class DB
	{
	private $resource;

	function __construct()
		{
		$this->resource = new mysqli(
			$GLOBALS['config']['db']['host'],
			$GLOBALS['config']['db']['user'],
			$GLOBALS['config']['db']['password'],
			$GLOBALS['config']['db']['database']
		);
		$this->resource->set_charset("utf8");
		}

	function query($query)
		{
		$result = $this->resource->query($query);
		if ($error = mysqli_error($this->resource))
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
		return $this->resource->real_escape_string($string);
		}

	function insert_id()
		{
		return $this->resource->insert_id;
		}
	}

