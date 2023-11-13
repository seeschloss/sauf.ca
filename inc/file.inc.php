<?php

class File {
	public static function src(int $date, string $id, string $extension) {
		return date('Y-m-d', $date).'/'.$id.'.'.$extension;
	}

	public static function path(int $date, string $id, string $extension) {
		return Site::path(date('Y-m-d', $date).'/'.$id.'.'.$extension);
	}

	public static function store($data, int $date, string $id, string $extension) {
		$path = self::path($date, $id, $extension);

		return file_put_contents($path, $data) !== false;
	}

	public static function retrieve(int $date, string $id, string $extension) {
		$path = self::path($date, $id, $extension);

		return file_get_contents($path);
	}

	public static function url(int $date, string $id, string $extension) {
		$path = self::path($date, $id, $extension);

		return url($path);
	}
}
