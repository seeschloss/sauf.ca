<?php
require 'inc/common.inc.php';

if (!$argv)
	{
	header('HTTP/1.0 404 Not Found');
	exit();

	}

$db = new DB();
$query = 'SELECT p.*
	FROM pictures p
	WHERE raw_tags = \'\'
';
$result = $db->query($query);

if ($result) while ($row = $result->fetch_assoc())
	{
	$picture = new Picture();
	$picture->load($row);

	$picture->raw_tags = $picture->find_tags();

	echo "id ".$picture->id.": ".$picture->raw_tags."\n";

	$picture->update();
	}
