<?php
require __DIR__.'/../inc/common.inc.php';

if (!$argv)
	{
	header('HTTP/1.0 404 Not Found');
	exit();
	}

$id = $argv[1];

$picture = new Picture();
$picture->load_by_id($id);

$picture->raw_tags = $picture->find_tags();

#echo "Raw tags for picture ".$picture->src.":\n";
#echo json_encode(json_decode($picture->raw_tags), JSON_PRETTY_PRINT)."\n";

$picture->init_tags();
echo "Tags kept: ".$picture->tags."\n";

$picture->update();
