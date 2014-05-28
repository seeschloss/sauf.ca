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
	echo "Local file not found, aborting.\n";
	echo "\n";
	echo "Run this instead:\n";
	$tribune = new Tribune();
	$tribune->load_by_id($picture->tribune_id);

	echo "echo \"".str_replace('"', '\"', $picture->title)."\" | php upload.php ".$tribune->name." ".$tribune->url."\n";

	exit();
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
