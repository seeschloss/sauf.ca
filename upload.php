<?php
require 'inc/common.inc.php';

if (!$argv)
	{
	header('HTTP/1.0 404 Not Found');
	exit();
	}

$tribune_name = $argv[1];
$tribune_url = $argv[2];

$f = fopen('php://stdin', 'r');
while ($line = fgets($f))
	{
	$post = explode("\t", trim($line));
	$post_id = $post[0];
	$date = $post[1];
	$user_name = $post[3];

	$timestamp = mktime(substr($date, 8, 2), substr($date, 10, 2), substr($date, 12, 2), substr($date, 4, 2), substr($date, 6, 2), substr($date, 0, 4));

	$matches = array();
	preg_match_all('/href="([^"]*)"/', $post[4], $matches);

	if (!empty($matches[1])) foreach ($matches[1] as $url)
		{
		if (preg_match('/sauf.ca/', $url))
			{
			continue;
			}

		if (preg_match('/https?:\/\/gfycat.com\/[^.]*$/', $url))
			{
			$url = str_replace('gfycat.com', 'giant.gfycat.com', $url).".gif";
			}

		process_url($post_id, $timestamp, $user_name, $url, $post[4], $tribune_name, $tribune_url);
		}
	}

function process_url($post_id, $timestamp, $user_name, $url, $post_text, $tribune_name, $tribune_url)
	{
	$picture = new Picture();
	if ($picture->load_by_post_id($post_id) and $picture->url == $url)
		{
		return false;
		}

	$details = parse_url($url);

	$c = curl_init();

	$referer = $details['scheme'].'://'.$details['host'];

	curl_setopt($c, CURLOPT_USERAGENT, "Mozilla/5.0");
	curl_setopt($c, CURLOPT_REFERER, $referer);
	curl_setopt($c, CURLOPT_URL, $url);
	curl_setopt($c, CURLOPT_HEADER, true);
	curl_setopt($c, CURLOPT_NOBODY, true);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 2);
	curl_setopt($c, CURLOPT_TIMEOUT, 10);
	curl_setopt($c, CURLOPT_AUTOREFERER, true);
	curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($c, CURLOPT_MAXREDIRS, 5);

	if (!$a = curl_exec($c))
		{
		echo curl_error($c)."\n";
		return false;
		}

	if (($code = curl_getinfo($c, CURLINFO_HTTP_CODE)) >= 400)
		{
		echo 'HTTP error '.$code." for $url\n";
		return false;
		}

	if (($size = curl_getinfo($c, CURLINFO_CONTENT_LENGTH_DOWNLOAD)) > UPLOAD_MAX_SIZE)
		{
		echo 'File bigger than '.round(UPLOAD_MAX_SIZE/1024/1024)."MB (".round(CURLINFO_CONTENT_LENGTH_DOWNLOAD/1024/1024)."MB)\n";
		return false;
		}

	$content_types = array(
		'image/gif' => 'gif',
		'image/jpeg' => 'jpg',
		'image/jpg' => 'jpg',
		'image/png' => 'png',
		'video/webm' => 'webm',
	);
	$content_type = curl_getinfo($c, CURLINFO_CONTENT_TYPE);
	if (!isset($content_types[$content_type]))
		{
		echo "Content type not acceptable (".$content_type.")\n";
		return false;
		}
	else
		{
		curl_setopt($c, CURLOPT_HEADER, false);
		curl_setopt($c, CURLOPT_NOBODY, false);
		curl_setopt($c, CURLOPT_HTTPGET, true);

		if (!$image_data = curl_exec($c))
			{
			echo curl_error($c)."\n";
			return false;
			}
		else
			{
			$md5 = md5($image_data);
			$extension = $content_types[$content_type];
			echo "Image downloaded ($md5 - $content_type)\n";

			$tribune = new Tribune();
			if (!$tribune->load_by_name($tribune_name)) {
				$tribune->name = $tribune_name;
				$tribune->url = $tribune_url;
				$tribune->insert();
			}

			$picture = new Picture();
			$picture->name = $md5.'.'.$extension;
			$picture->title = $post_text;
			$picture->url = $url;
			$picture->date = $timestamp;
			$picture->user = $user_name;
			$picture->tribune_id = $tribune->id;
			$picture->post_id = $post_id;

			if ($picture->write($image_data))
				{
				//$picture->find_tags();
				$picture->insert();
				echo "Image saved\n";

				return true;
				}
			}
		}

	return false;
	}

?>
