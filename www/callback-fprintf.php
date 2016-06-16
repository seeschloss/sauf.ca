<?php
require '../inc/common.inc.php';

$body = file_get_contents("php://input");

if (!($data = json_decode($body, TRUE))) {
	header("400 Bad Request");
	die();
}

if (!isset($data['data'])) {
	header("400 Bad Request");
	die();
}

$url = new URL();
if (!$url->load_by_random_id($data['data'])) {
	header("404 Not Found");
	die();
}

Logger::notice('URL found ('.$url->url.')');
$url->parse_fprintf_data($data);
$url->generate_thumbnail();

$url->published = 1;

$url->update();
Logger::notice('URL saved');

