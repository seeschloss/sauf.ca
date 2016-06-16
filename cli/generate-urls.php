<?php
require __DIR__.'/../inc/common.inc.php';

if (!$argv) {
	header('HTTP/1.0 404 Not Found');
	exit();
}

function new_path($path) {
	$new_path = str_replace("/home/seeschloss/sauf.ca/pictures/", "", $path);
	$new_path = str_replace("/home/seeschloss/sauf.ca/http/pictures/", "", $new_path);

	return $new_path;
}

$db = new DB();

/*
$db->query("TRUNCATE TABLE urls;");
$db->query("TRUNCATE TABLE images;");
$db->query("TRUNCATE TABLE videos;");
$db->query("TRUNCATE TABLE thumbnails;");
$db->query("TRUNCATE TABLE screenshots;");
*/

$query = "SELECT id, link_id, picture_id FROM unique_ids WHERE id > 39888 ORDER BY id ASC";
$result = $db->query($query);
while ($row = $result->fetch_assoc()) {
	var_dump($row['id']);
	$unique_id = $row['id'];

	$url = new URL();
	$url->id = $unique_id;

	if (isset($row['picture_id'])) {
		$picture = new Picture();
		$picture->load_by_id($row['picture_id']);

		$url->random_id = $picture->md5;
		$url->published = 1;
		$url->url = $picture->url;
		$url->date = $picture->date;
		$url->post_tribune_id = $picture->tribune_id;
		$url->post_id = $picture->post_id;
		$url->post_user = $picture->user;
		$url->post_message = $picture->title;
		$url->post_info = "-";
		$url->tags = $picture->tags;
		$url->title = "";
		$url->description = "";

		switch ($picture->type) {
			case 'image/jpeg':
				$image = new Image();
				$image->jpg = new_path($picture->path);
				$image->insert();
				$url->image_id = $image->id;

				$thumbnail = new Thumbnail();
				$thumbnail->jpg = new_path($picture->thumbnail_path);
				$thumbnail->insert();
				$url->thumbnail_id = $thumbnail->id;
				break;
			case 'image/png':
				$image = new Image();
				$image->png = new_path($picture->path);
				$image->insert();
				$url->image_id = $image->id;

				$thumbnail = new Thumbnail();
				$thumbnail->png = new_path($picture->thumbnail_path);
				$thumbnail->insert();
				$url->thumbnail_id = $thumbnail->id;
				break;
			case 'image/gif':
				$image = new Image();
				$image->gif = new_path($picture->path);
				$image->insert();
				$url->image_id = $image->id;

				$thumbnail = new Thumbnail();
				$thumbnail->png = new_path($picture->thumbnail_path);

				if (file_exists($picture->thumbnail_path.'.animated.gif')) {
					$thumbnail->gif = new_path($picture->thumbnail_path.'.animated.gif');
				}

				$thumbnail->insert();
				$url->thumbnail_id = $thumbnail->id;
				break;
			case 'video/webm':
				$video = new Video();
				$video->webm = new_path($picture->path);
				$video->insert();
				$url->video_id = $video->id;

				$thumbnail = new Thumbnail();
				$thumbnail->jpg = new_path($picture->thumbnail_path);

				if (file_exists($picture->thumbnail_path.'.animated.webm')) {
					$thumbnail->webm = new_path($picture->thumbnail_path.'.animated.webm');
				}

				$thumbnail->insert();
				$url->thumbnail_id = $thumbnail->id;
				break;
		}
	} else if (isset($row['link_id'])) {
		$link = new Link();
		$link->load_by_id($row['link_id']);

		$url->random_id = $link->random_id ? $link->random_id : substr(sha1(rand() . $link->context . $link->url), 0, 32);
		$url->published = $link->published;
		$url->url = $link->url;
		$url->date = $link->date;
		$url->post_tribune_id = $link->tribune_id;
		$url->post_id = $link->post_id;
		$url->post_user = $link->user;
		$url->post_message = $link->context;
		$url->post_info = "-";
		$url->tags = $link->tags;
		$url->title = $link->title;
		$url->description = $link->description;

		$screenshot = new Screenshot();
		$screenshot->png_full = new_path($link->screenshot_path);

		$pdf_screenshot_path = str_replace('.png', '.pdf', $link->screenshot_path);
		if (file_exists($pdf_screenshot_path)) {
			$screenshot->pdf = new_path($pdf_screenshot_path);
		}

		$screenshot->insert();
		$url->screenshot_id = $screenshot->id;

		if ($link->thumbnail_path && file_exists($link->thumbnail_path)) {
			$thumbnail = new Thumbnail();
			if (preg_match('/png$/', $link->thumbnail_path)) {
				$thumbnail->png = new_path($link->thumbnail_path);
			} else if (preg_match('/jpg$/', $link->thumbnail_path)) {
				$thumbnail->jpg = new_path($link->thumbnail_path);
			} else { // ??
				$thumbnail->jpg = new_path($link->thumbnail_path);
			}
			$thumbnail->insert();
			$url->thumbnail_id = $thumbnail->id;
		}
	}

	$query = 'REPLACE INTO urls SET
		screenshot_id = '.(int)$url->screenshot_id.',
		thumbnail_id = '.(int)$url->thumbnail_id.',
		image_id = '.(int)$url->image_id.',
		video_id = '.(int)$url->video_id.',
		random_id = \''.$db->escape($url->random_id).'\',
		published = '.(int)$url->published.',
		url = \''.$db->escape($url->url).'\',
		date = '.(int)$url->date.',
		post_tribune_id = '.(int)$url->post_tribune_id.',
		post_id = '.(int)$url->post_id.',
		post_user = \''.$db->escape($url->post_user).'\',
		post_message = \''.$db->escape($url->post_message).'\',
		post_info = \''.$db->escape($url->post_info).'\',
		title = \''.$db->escape($url->title).'\',
		description = \''.$db->escape($url->description).'\',
		tags = \''.$db->escape($url->tags).'\',
		id = '.(int)$url->id.'
		';

	$db->query($query);
}
