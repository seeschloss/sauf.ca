<?php

class Image {
	public $id = 0;

	public $png = "";
	public $jpg = "";
	public $gif = "";

	public $random_id = "";

	function load_by_id($id) {
		$data = array();
		$db = new DB();

		$query = 'SELECT i.*
			FROM images AS i
			WHERE i.id = \''.$db->escape($id).'\'';
			;
		$result = $db->query($query);

		if ($result) while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			$data = $row;
		}

		foreach ($data as $key => $value) {
			$this->{$key} = $value;
		}

		return $this->id;
	}

	function insert() {
		$db = new DB();

		$db->insert('images', [
			'png' => "'".$db->escape($this->png)."'",
			'jpg' => "'".$db->escape($this->jpg)."'",
			'gif' => "'".$db->escape($this->gif)."'"
		]);

		$this->id = $db->insert_id();

		return $this->id;
	}

	function update() {
		if (!$this->id) {
			return false;
		}

		$db = new DB();

		$query = 'UPDATE thumbnails SET
			png = \''.$db->escape($this->png).'\',
			jpg = \''.$db->escape($this->jpg).'\',
			gif = \''.$db->escape($this->gif).'\'
			WHERE id = '.(int)$this->id.'
			';

		$db->query($query);

		return true;
	}

	function retrieve_image($url) {
		$http = new HTTP($url);
		if ($data = $http->get()) {
			$base_path = date('Y-m-d').'/'.$this->random_id;

			$finfo = new Finfo(FILEINFO_MIME);
			@list($mime, $charset) = explode(';', $finfo->buffer($data));
			switch ($mime) {
				case 'image/jpeg':
					$extension = 'jpg';
					$path = $base_path.'.jpg';
					$this->jpg = $path;
					break;
				case 'image/gif':
					$extension = 'gif';
					$path = $base_path.'.gif';
					$this->gif = $path;
					break;
				case 'image/png':
					$extension = 'png';
					$path = $base_path.'.png';
					$this->png = $path;
					break;
				default:
					Logger::warning("Mime-type not acceptable: ".$mime);
					return false;
			}

			return File::store($data, time(), $this->random_id, $extension);
		} else {
			return false;
		}
	}
}

