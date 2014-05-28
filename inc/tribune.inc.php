<?php
class Tribune
	{
	public $id = 0;
	public $name = "";
	public $url = "";

	function __construct()
		{
		}

	function load_by_id($id)
		{
		$db = new DB();

		$query = 'SELECT *
			FROM tribunes t
			WHERE t.id = \''.$db->escape($id).'\''
			;
		$result = $db->query($query);

		if ($result) while ($row = $result->fetch_assoc())
			{
			foreach ($row as $key => $value)
				{
				$this->{$key} = $value;
				}

			return true;
			}

		return false;
		}

	function load_by_name($name)
		{
		$db = new DB();

		$query = 'SELECT *
			FROM tribunes t
			WHERE t.name = \''.$db->escape($name).'\''
			;
		$result = $db->query($query);

		if ($result) while ($row = $result->fetch_assoc())
			{
			foreach ($row as $key => $value)
				{
				$this->{$key} = $value;
				}

			return true;
			}

		return false;
		}

	function insert()
		{
		$db = new DB();

		$query = 'INSERT INTO tribunes SET
			name = \''.$db->escape($this->name).'\',
			url = \''.$db->escape($this->url).'\',
			';

		$db->query($query);

		$this->id = $db->insert_id();
		}
	}
