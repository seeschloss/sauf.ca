<?php
require 'inc/common.inc.php';

if (!$argv)
	{
	header('HTTP/1.0 404 Not Found');
	exit();
	}

$picture_id = $argv[1];
$picture = new Picture();

if (!$picture->load($picture_id)) {
	echo "Cannot load picture $picture_id\n";
	exit();
}

echo "Picture $picture_id found, posted on ".date("Y-m-d", $picture->date)." at ".date("H:i:s", $picture->date)."\n";

if (!file_exists($picture->path)) {
	echo "Local file not found...\n";
	if ($picture->url) {
		echo "Downloading again.\n";
		if ($image_data = process_url($picture->url)) {
			if ($picture->write($image_data)) {
				$picture->update();
				exit();
			} else {
				echo "Could not write image data to a file.\n";
			}
		} else {
			echo "Could not download anything from ".$picture->url."\n";
		}
		exit();
	} else {
		echo "And no url on record, fix this manually.\n";
		exit();
	}
}

echo "Known content-type is ".$picture->type."\n";

$finfo = new Finfo(FILEINFO_MIME);
@list($mime, $charset) = explode(';', $finfo->file($picture->path));

echo "Real content-type is ".$mime."\n";

if ($mime != $picture->type) {
	echo "Types differ, aborting.\n";
	exit();
}

echo "Generating thumbnail...\n";
$picture->generate_thumbnail();
echo "done.\n";

echo "Thumbnail is: ".$picture->thumbnail_path."\n";

$picture->update();
echo "Picture saved.\n";


?>
