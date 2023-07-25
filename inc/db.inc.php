<?php
class DB
	{
	static private $resource;

	function __construct()
		{
		if (!isset(self::$resource))
			{
			self::$resource = new PDO($GLOBALS['config']['db']['dsn']);
			}
		}

	function query($query)
		{
		try
			{
			$result = self::$resource->query($query);
			}
		catch (PDOException $error)
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

			return null;
			}

		return $result;
		}

	function insert(string $table, array $values) {
		$columns = implode(', ', array_keys($values));
		$values = implode(', ', array_values($values));

		$string = "INSERT INTO `{$table}` ({$columns}) VALUES ({$values})";

		return $this->query($string);
	}

	function value($query)
		{
		$result = $this->query($query);
		if ($result) while ($row = $result->fetch(PDO::FETCH_ASSOC))
			{
			return $row[0];
			}

		return '';
		}

	function escape($string)
		{
		return str_replace("'", "''", $string);
		}

	function insert_id()
		{
		return self::$resource->lastInsertId();
		}
	}

