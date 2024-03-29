<?php

class Screenshot {
	public $id = 0;

	public $pdf = "";
	public $png_full = "";
	public $png_cropped = "";

	public $random_id = "";

	function load_by_id($id) {
		$data = array();
		$db = new DB();

		$query = 'SELECT s.*
			FROM screenshots AS s
			WHERE s.id = \''.$db->escape($id).'\'';
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

		$db->insert('screenshots', [
			'pdf' => "'".$db->escape($this->pdf)."'",
			'png_full' => "'".$db->escape($this->png_full)."'",
			'png_cropped' => "'".$db->escape($this->png_cropped)."'"
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

		$query = 'UPDATE screenshots SET
			pdf = \''.$db->escape($this->pdf).'\',
			png_full = \''.$db->escape($this->png_full).'\',
			png_cropped = \''.$db->escape($this->png_cropped).'\'
			WHERE id = \''.(int)$this->id.'\'
			';

		$db->query($query);

		return true;
	}

	function retrieve_image($url, $type) {
		$http = new HTTP($url);
		if ($data = $http->get()) {
			$base_path = date('Y-m-d').'/'.$this->random_id;

			switch ($type) {
				case 'pdf':
					$extension = 'pdf';
					$path = $base_path.'.pdf';
					$this->pdf = $path;
					break;
				case 'png_cropped':
					$extension = 'png';
					$path = $base_path.'.png';
					$this->png_cropped = $path;
					break;
				case 'png_full':
					$extension = 'full.png';
					$path = $base_path.'.full.png';
					$this->png_full = $path;
					break;
				default;
					return false;
			}

			return File::store($data, time(), $this->random_id, $extension);
		} else {
			return false;
		}
	}
}

