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
		return $this->resource->query($query);
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

