<?php
require '../inc/common.inc.php';

$body = file_get_contents("php://input");

if (!($data = json_decode($body, TRUE))) {
	header("HTTP/1.0 400 Bad Request");
	die();
}

if (!isset($data['data'])) {
	header("HTTP/1.0 400 Bad Request");
	die();
}

$link = new Link();
if (!$link->load_by_random_id($data['data'])) {
	header("HTTP/1.0 404 Not Found");
	die();
}

Logger::notice('Link found ('.$link->url.')');
$link->parse_fprintf_data($data);
$link->generate_thumbnail();

$link->published = 1;

$link->update();
Logger::notice('Link saved');

