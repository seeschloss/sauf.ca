<?php

require __DIR__.'/../inc/common.inc.php';

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
	$post_info = $post[2];
	$user_name = $post[3];
	$post_message = isset($post[4]) ? $post[4] : "";

	$timestamp = mktime(substr($date, 8, 2), substr($date, 10, 2), substr($date, 12, 2), substr($date, 4, 2), substr($date, 6, 2), substr($date, 0, 4));

	$matches = array();
	preg_match_all('/href="([^"]*)"/', $post_message, $matches);

	if (!empty($matches[1])) foreach ($matches[1] as $url)
		{
		$url = html_entity_decode($url);

		$source_url = $url;

		Logger::notice('URL is '.$url);
		if (preg_match('/sauf\.ca/', $url))
			{
			continue;
			}

		if (preg_match('/imgur.com\/[^.]*\.mp4$/', $url))
			{
			$url = str_replace('.mp4', '.webm', $url);
			}

		if (preg_match('/imgur.com\/[^.]*\.gifv$/', $url))
			{
			$url = str_replace('.gifv', '.webm', $url);
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
		Logger::notice('URL to download is '.$url);

		$picture = new Picture();
		if ($picture->load_by_post_id($post_id) and $picture->url == $url)
			{
			Logger::warning('Picture already uploaded (id '.$picture->id.')');
			continue;
			}
		else if ($picture->url)
			{
			Logger::notice('Previous url in this post was '.$picture->url);
			}

		if (!isset($images_per_user[$user_name]))
			{
			$images_per_user[$user_name] = 0;
			}

		if ($user_name == 'gle' and $images_per_user[$user_name] > 3)
			{
			Logger::warning('gle< has already posted '.$images_per_user[$user_name].' images among new posts, skipping');
			continue;
			}

		$tribune = new Tribune();
		if (!$tribune->load_by_name($tribune_name))
			{
			$tribune->name = $tribune_name;
			$tribune->url = $tribune_url;
			$tribune->insert();
			}

		$url_record = new URL();
		$url_record->random_id = substr(sha1(rand() . $post_message . $source_url), 0, 32);
		$url_record->published = 0;
		$url_record->url = $source_url;
		$url_record->date = $timestamp;
		$url_record->post_tribune_id = $tribune->id;
		$url_record->post_id = $post_id;
		$url_record->post_user = $user_name;
		$url_record->post_message = $post_message;
		$url_record->post_info = $post_info;
		$url_record->insert();

		$url_record->retrieve_embed();

		$content_type = get_content_type($url);
		if (Picture::acceptable($content_type) and $data = process_url($url, $content_type))
			{
			$picture = new Picture();
			$picture->name = $source_url;
			$picture->title = $post[4];
			$picture->url = $source_url;
			$picture->date = $timestamp;
			$picture->user = $user_name;
			$picture->tribune_id = $tribune->id;
			$picture->post_id = $post_id;

			Logger::notice('Post ID is '.$post_id);
			Logger::notice('Tribune ID is '.$tribune->id);

			if ($picture->write($data))
				{
				Logger::notice('Picture written');
				$picture->raw_tags = $picture->find_tags();
				$picture->init_tags();
				if ($picture->insert())
					{
					Logger::notice('Picture saved');
					}
				else
					{
					Logger::error('Could not save picture');
					}

				$images_per_user[$user_name] += 1;
				}

			$url_record->unique_id = $picture->unique_id;
			$url_record->update();
			}
		else if (Link::acceptable($content_type))
			{
			$link = new Link();
			$link->user = $user_name;
			$link->date = $timestamp;
			$link->url = $source_url;
			$link->tribune_id = $tribune->id;
			$link->post_id = $post_id;
			$link->context = $post[4];

			list($content_type) = explode(';', $content_type);
			$link->type = $content_type;

			$link->retrieve_embed();
			$link->generate_thumbnail();
			Logger::notice('Link created');
			$link->insert();
			Logger::notice('Link saved');

			$url_record->unique_id = $link->unique_id;
			$url_record->update();
			}
		}
	}

?>
