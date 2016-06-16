<?php
require 'inc/common.inc.php';

if (!$argv)
	{
	header('HTTP/1.0 404 Not Found');
	exit();
	}

for ($id = 27011; $id < 27030; $id++) {
	$link = new Link();

	echo "Link #$id\n";

	if ($link->load_by_id($id)) {
		$link->generate_thumbnail();
		echo "fixed.\n";
	}
}
