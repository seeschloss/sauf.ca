<?php
class Tribune {
	public $id = 0;
	public $name = "";
	public $url = "";

	function __construct() {
	}

	function load_by_id($id) {
		$db = new DB();

		$query = 'SELECT *
			FROM tribunes t
			WHERE t.id = \''.$db->escape($id).'\''
			;
		$result = $db->query($query);

		if ($result) while ($row = $result->fetch_assoc()) {
			foreach ($row as $key => $value) {
				$this->{$key} = $value;
			}

			return true;
		}

		return false;
	}

	function load_by_name($name) {
		$db = new DB();

		$query = 'SELECT *
			FROM tribunes t
			WHERE t.name = \''.$db->escape($name).'\''
			;
		$result = $db->query($query);

		if ($result) while ($row = $result->fetch_assoc()) {
			foreach ($row as $key => $value) {
				$this->{$key} = $value;
			}

			return true;
		}

		return false;
	}

	function insert() {
		$db = new DB();

		$query = 'INSERT INTO tribunes SET
			name = \''.$db->escape($this->name).'\',
			url = \''.$db->escape($this->url).'\',
			';

		$db->query($query);

		$this->id = $db->insert_id();
	}

	function post($message) {
		if (isset($GLOBALS['config']['backends'][$this->name])) {
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, $GLOBALS['config']['backends'][$this->name]['post_url']);
			curl_setopt($ch, CURLOPT_POSTFIELDS, [
				$GLOBALS['config']['backends'][$this->name]['post_fields'] => $message,
			]);
			curl_setopt($ch, CURLOPT_COOKIE, $GLOBALS['config']['backends'][$this->name]['cookie']);
			curl_setopt($ch, CURLOPT_REFERER, $GLOBALS['config']['backends'][$this->name]['referer']);
			curl_setopt($ch, CURLOPT_USERAGENT, $GLOBALS['config']['backends'][$this->name]['user-agent']);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			curl_exec($ch);
		}
	}
}
