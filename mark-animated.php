<?php
require 'inc/common.inc.php';

$db = new DB();

$query = "SELECT id
	FROM pictures
	WHERE src LIKE '%.gif'
	ORDER BY id DESC
	";
$result = $db->query($query);

if ($result) while ($row = $result->fetch_assoc())
	{
	$picture = new Picture();
	$picture->load($row['id']);

	if (file_exists($picture->thumbnail_path . '.animated.gif'))
		{
		$picture->animated = 1;
		$picture->update();
		var_dump($picture->thumbnail_path);
		}
	}
