<?php
require 'inc/common.inc.php';

$db = new DB();

$query = "SELECT id
	FROM pictures
	WHERE src LIKE '%.gif'
	ORDER BY id DESC
	";
$result = $db->query($query);

if ($result) while ($row = $result->fetch(PDO::FETCH_ASSOC))
	{
	$picture = new Picture();
	$picture->load($row['id']);

	if ($picture->animated_thumbnail($picture->path, $picture->thumbnail_path . '.animated.gif'))
		{
		$picture->animated = 1;
		$picture->update();
		var_dump($picture->thumbnail_path . '.animated.gif');
		}
	else
		{
		var_dump($picture->path);
		}
	}
