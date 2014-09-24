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
		trigger_error('URL is '.$url);
		if (preg_match('/sauf.ca/', $url))
			{
			continue;
			}

		if (preg_match('/https?:\/\/gfycat.com\/[^.]*$/', $url))
			{
			$url = str_replace('gfycat.com', 'giant.gfycat.com', $url).".gif";
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
			$picture->name = $url;
			$picture->title = $post[4];
			$picture->url = $url;
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
