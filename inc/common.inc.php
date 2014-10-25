<?php

require_once dirname(__FILE__).'/../cfg/config.inc.php';

define("UPLOAD_DIR", "/home/seeschloss/sauf.ca/pictures");
define("UPLOAD_MAX_SIZE", 55*1024*1024);
define("NB_INITIAL_THUMBNAILS", 250);
define("THUMBNAIL_SIZE", 100);

define("PICTURES_PREFIX", 'pictures');

function search_condition($search)
	{
	$db = new DB();
	$conditions = array();

	foreach (explode(" ", $search) as $term)
		{
		$tribune = new Tribune();
		if ($term[0] == "@" && $tribune->load_by_name(substr($term, 1)))
			{
			$condition = "p.tribune_id = ".(int)$tribune->id;
			}
		else if ($term[strlen($term) - 1] == "<")
			{
			$condition = "p.user = '".$db->escape(substr($term, 0, -1))."'";
			}
		else
			{
			$term = $db->escape($term);
			if ($term == 'tut_tut')
				{
				$term = 'tut_tu%t';
				}
			$condition = "p.user LIKE '$term%' OR p.title LIKE '%$term%' OR p.url LIKE '%$term%' OR p.tags LIKE '%$term%' OR p.raw_tags LIKE '%$term%'";
			}

		$conditions[] = "(".$condition.")";
		}

	return count($conditions) ? join(" AND ", $conditions) : "TRUE";
	}

function url($path, $random = false)
	{
	if (strpos($_SERVER['HTTP_HOST'], 'sauf.ca') === FALSE)
		{
		return $path;
		}

	if ($random)
		{
		$SERVERS = array
			(
			'a.img.sauf.ca',
			'b.img.sauf.ca',
			'c.img.sauf.ca',
			);

		$index = abs(crc32($path)) % count($SERVERS);
		$server = $SERVERS[$index];
		}
	else
		{
		$server = 'sauf.ca';
		}

	return 'http://'.$server.'/'.$path;
	}

function is_image($url, &$error)
	{
	$details = parse_url($url);

	$c = curl_init();

	$referer = $details['scheme'].'://'.$details['host'];

	if (strpos($details['host'], 'ecx.images-amazon') !== FALSE) {
		$nobody = false;
	} else {
		$nobody = true;
	}

	curl_setopt($c, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Linux x86_64; rv:29.0) Gecko/20100101 Firefox/29.0");
	curl_setopt($c, CURLOPT_REFERER, $referer);
	curl_setopt($c, CURLOPT_URL, $url);
	curl_setopt($c, CURLOPT_HEADER, true);
	curl_setopt($c, CURLOPT_NOBODY, $nobody);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 2);
	curl_setopt($c, CURLOPT_TIMEOUT, 15);
	curl_setopt($c, CURLOPT_AUTOREFERER, true);
	curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($c, CURLOPT_MAXREDIRS, 5);

	if (!$a = curl_exec($c))
		{
		$error = $url . ': ' . curl_error($c);
		return false;
		}

	if (($code = curl_getinfo($c, CURLINFO_HTTP_CODE)) >= 400)
		{
		$error = 'HTTP error '.$code." for $url";
		return false;
		}

	if (($size = curl_getinfo($c, CURLINFO_CONTENT_LENGTH_DOWNLOAD)) > UPLOAD_MAX_SIZE)
		{
		$error = 'File bigger than '.round(UPLOAD_MAX_SIZE/1024/1024)."MB (".round(CURLINFO_CONTENT_LENGTH_DOWNLOAD/1024/1024)."MB)";
		return false;
		}

	$content_type = curl_getinfo($c, CURLINFO_CONTENT_TYPE);
	if (!is_acceptable($content_type))
		{
		$error = "Content type not acceptable (".$content_type.")";
		return false;
		}

	return true;
	}

function is_acceptable($content_type)
	{
	$content_types = array(
		'image/gif' => 'gif',
		'image/jpeg' => 'jpg',
		'image/jpg' => 'jpg',
		'image/png' => 'png',
		'video/webm' => 'webm',
	);

	return isset($content_types[$content_type]);
	}

function process_url($url)
	{
	$url = html_entity_decode($url);

	if (preg_match('/https?:\/\/(www\.)?youtube.com\/[^.]*$/', $url)) {
		$safe_url = escapeshellarg($url);
		$url = `youtube-dl -g -f webm {$safe_url}`;
	} elseif (false && preg_match('/https?:\/\/([a-z])*\.wikipedia\.org\/wiki\/File:(.)*$/', $url, $matches)) {
		// Needs some testing
		$filename = $matches[2];
		$md5 = md5($filename);
		$url = 'https://upload.wikimedia.org/wikipedia/commons/' . substr($md5, 0, 1) . '/' . substr($md5, 0, 2) . '/' . $filename;
	}

	$details = parse_url($url);

	$c = curl_init();

	$referer = $details['scheme'].'://'.$details['host'];

	if (strpos($details['host'], 'ecx.images-amazon') !== FALSE) {
		$nobody = false;
	} else {
		$nobody = true;
	}

	curl_setopt($c, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Linux x86_64; rv:29.0) Gecko/20100101 Firefox/29.0");
	curl_setopt($c, CURLOPT_REFERER, $referer);
	curl_setopt($c, CURLOPT_URL, $url);
	curl_setopt($c, CURLOPT_HEADER, true);
	curl_setopt($c, CURLOPT_NOBODY, $nobody);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 2);
	curl_setopt($c, CURLOPT_TIMEOUT, 15);
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

	$content_type = curl_getinfo($c, CURLINFO_CONTENT_TYPE);
	if (!is_acceptable($content_type))
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
			echo "Image downloaded (".strlen($image_data)." bytes)\n";
			return $image_data;
			}
		}

	return false;
	}


require_once dirname(__FILE__).'/db.inc.php';
require_once dirname(__FILE__).'/site.inc.php';
require_once dirname(__FILE__).'/picture.inc.php';
require_once dirname(__FILE__).'/tribune.inc.php';
require_once dirname(__FILE__).'/oauth.inc.php';
