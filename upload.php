<?php
require 'inc/common.inc.php';

if (!$argv)
	{
	header('HTTP/1.0 404 Not Found');
	exit();
	}

$tribune_name = $argv[1];
$tribune_url = $argv[2];

$images_per_user = array();

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
		$source_url = $url;

		trigger_error('URL is '.$url);
		if (preg_match('/sauf.ca/', $url))
			{
			continue;
			}

		if (preg_match('/https?:\/\/gfycat.com\/[^.]*$/', $url))
			{
			$url = str_replace('gfycat.com', 'giant.gfycat.com', $url).".gif";
			}

		if (preg_match('/http:\/\/pr0gramm.com\/.*\/([0-9]*)$/', $url, $matches))
			{
			$url = 'http://pr0gramm.com/api/items/get?id=' . $matches[1];
			$c = curl_init();
			curl_setopt($c, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Linux x86_64; rv:29.0) Gecko/20100101 Firefox/29.0");
			curl_setopt($c, CURLOPT_REFERER, "http://sauf.ca");
			curl_setopt($c, CURLOPT_URL, $url);
			curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 2);
			curl_setopt($c, CURLOPT_TIMEOUT, 15);
			curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($c, CURLOPT_MAXREDIRS, 5);

			if ($a = curl_exec($c) and $data = json_decode($a) and isset($data->items) and count($data->items))
				{
				$url = 'http://img.pr0gramm.com/' . $data->items[0]->image;
				if (isset($data->items[0]->source) and $data->items[0]->source)
					{
					$source_url = $data->items[0]->source;
					}
				}
			}

		$picture = new Picture();
		if ($picture->load_by_post_id($post_id) and $picture->url == $url)
			{
			trigger_error('Picture already uploaded (id '.$picture->id.')');
			continue;
			}
		else if ($picture->url)
			{
			trigger_error('Previous url in this post was '.$picture->url);
			}

		if (!isset($images_per_user[$user_name]))
			{
			$images_per_user[$user_name] = 0;
			}

		if ($user_name == 'gle' and $images_per_user[$user_name] > 3)
			{
			trigger_error('gle< has already posted '.$images_per_user[$user_name].' images among new posts, skipping');
			continue;
			}

		if ($image_data = process_url($url))
			{
			$tribune = new Tribune();
			if (!$tribune->load_by_name($tribune_name))
				{
				$tribune->name = $tribune_name;
				$tribune->url = $tribune_url;
				$tribune->insert();
				}

			$picture = new Picture();
			$picture->name = $source_url;
			$picture->title = $post[4];
			$picture->url = $source_url;
			$picture->date = $timestamp;
			$picture->user = $user_name;
			$picture->tribune_id = $tribune->id;
			$picture->post_id = $post_id;

			trigger_error('Post ID is '.$post_id);
			trigger_error('Tribune ID is '.$tribune->id);

			if ($picture->write($image_data))
				{
				trigger_error('Picture written');
				$picture->raw_tags = $picture->find_tags();
				$picture->insert();
				trigger_error('Picture saved');
				echo "Image saved\n";

				$images_per_user[$user_name] += 1;
				}
			}
		}
	}

?>
