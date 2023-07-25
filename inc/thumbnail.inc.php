<?php

class Thumbnail {
	public $id = 0;

	public $png = "";
	public $jpg = "";
	public $gif = "";
	public $webm = "";
	public $mp4 = "";

	public $random_id = "";

	function load_by_id($id) {
		$data = array();
		$db = new DB();

		$query = 'SELECT t.*
			FROM thumbnails AS t
			WHERE t.id = \''.$db->escape($id).'\'';
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

		$db->insert('thumbnails', [
			'png' => "'".$db->escape($this->png)."'",
			'jpg' => "'".$db->escape($this->jpg)."'",
			'gif' => "'".$db->escape($this->gif)."'",
			'webm' => "'".$db->escape($this->webm)."'",
			'mp4' => "'".$db->escape($this->mp4)."'"
		]);

		$db->query($query);

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
			gif = \''.$db->escape($this->gif).'\',
			webm = \''.$db->escape($this->webm).'\',
			mp4 = \''.$db->escape($this->mp4).'\'
			WHERE id = '.(int)$this->id.'
			';

		$db->query($query);

		return true;
	}

	function retrieve_image($url, $type) {
		$http = new HTTP($url);
		if ($data = $http->get()) {
			$base_path = date('Y-m-d').'/t'.$this->random_id;

			switch ($type) {
				case 'png':
					$path = $base_path.'.png';
					$this->png = $path;
					break;
				case 'jpg':
					$path = $base_path.'.jpg';
					$this->jpg = $path;
					break;
				case 'webm':
					$path = $base_path.'.webm';
					$this->webm = $path;
					break;
				case 'mp4':
					$path = $base_path.'.mp4';
					$this->mp4 = $path;
					break;
				default;
					return false;
			}

			file_put_contents(Site::path($path), $data);
			return true;
		} else {
			return false;
		}
	}
}

