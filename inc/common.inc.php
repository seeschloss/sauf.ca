<?php

require_once dirname(__FILE__).'/../cfg/config.inc.php';

define("UPLOAD_DIR", $GLOBALS['config']['upload']['directory']);
define("UPLOAD_MAX_SIZE", $GLOBALS['config']['upload']['max_size']);
define("NB_INITIAL_THUMBNAILS", 250);
define("THUMBNAIL_SIZE", 100);

define("PICTURES_PREFIX", 'pictures');

function is_blacklisted($url, $type)
	{
	if (isset($GLOBALS['config']['blacklist'][$type]))
		{
		foreach ($GLOBALS['config']['blacklist'][$type] as $blacklisted_url)
			{
			if (strpos($url, $blacklisted_url) === 0)
				{
				return true;
				}
			}
		}

	return false;
	}

function search_condition($search, $table = 'p', $extra_or_conditions = '')
	{
	$db = new DB();
	$conditions = array();

	foreach (explode(" ", $search) as $term)
		{
		$tribune = new Tribune();
		if ($term[0] == "@" && $tribune->load_by_name(substr($term, 1)))
			{
			$condition = "$table.tribune_id = ".(int)$tribune->id;
			}
		else if ($term[strlen($term) - 1] == "<")
			{
			$condition = "$table.user = '".$db->escape(substr($term, 0, -1))."'";
			if ($term == 'houplaboom<' && false)
				{
				$condition .= " AND $table.tags LIKE '%nsfw%'";
				}
			}
		else
			{
			$term = $db->escape($term);
			if ($term == 'tut_tut')
				{
				$term = 'tut_tu%t';
				}
			$condition = "$table.user LIKE '$term%' OR $table.title LIKE '%$term%' OR $table.url LIKE '%$term%' OR $table.tags LIKE '%$term%' ".$extra_or_conditions;
			}

		$conditions[] = "(".$condition.")";
		}

	return count($conditions) ? join(" AND ", $conditions) : "TRUE";
	}

function url($path, $random = false)
	{
	/*
	switch ($path) {
		case 'pictures/2016-07-25/1b7fd2fb064ff87467a595ec5bf0e053.pdf':
			return "http://cypris.seos.fr/".$path;
			break;
		default:
			break;
	}
	*/

	if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'sauf.ca') === FALSE)
		{
		return $path;
		}

	if ((!isset($_SERVER['HTTP']) || !$_SERVER['HTTPS']) && $random)
		{
		$SERVERS = array
			(
			'//a.img.sauf.ca',
			'//b.img.sauf.ca',
			'//c.img.sauf.ca',
			);

		$index = abs(crc32($path)) % count($SERVERS);
		$server = $SERVERS[$index];
		}
	else
		{
		$server = 'https://img.sauf.ca';
		}

	return $server.'/'.$path;
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

	/*
	if (preg_match('/\.onion$/', $this->url) or strpos($this->url, '.onion/'))
		{
		curl_setopt($c, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
		curl_setopt($c, CURLOPT_PROXY, "127.0.0.1:9050");
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($c, CURLOPT_TIMEOUT, 60);
		}
	*/

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
	list($type) = explode(';', $content_type);
	$content_types = array(
		'image/gif' => 'gif',
		'image/jpeg' => 'jpg',
		'image/jpg' => 'jpg',
		'image/png' => 'png',
		'video/webm' => 'webm',
		'video/mp4' => 'mp4',
	);

	return isset($content_types[$type]);
	}

function get_content_type(&$url)
	{
	$url = html_entity_decode($url);

	if (preg_match('/https?:\/\/(www\.)?youtube.com\/[^.]*$/', $url)) {
		$safe_url = escapeshellarg($url);
		$url = `youtube-dl --socket-timeout=10 -g -f mp4 {$safe_url}`;
		$url = trim($url);
	} elseif (false && preg_match('/https?:\/\/([a-z])*\.wikipedia\.org\/wiki\/File:(.)*$/', $url, $matches)) {
		// Needs some testing
		$filename = $matches[2];
		$md5 = md5($filename);
		$url = 'https://upload.wikimedia.org/wikipedia/commons/' . substr($md5, 0, 1) . '/' . substr($md5, 0, 2) . '/' . $filename;
	}

	$details = parse_url($url);

	$c = curl_init();

	if (isset($details['scheme']) and isset($details['host'])) {
		$referer = $details['scheme'].'://'.$details['host'];
	} else {
		fprintf(STDERR, "Cannot find scheme and host for url '{$url}'\n");
		$referer = "";
	}

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

	/*
	if (preg_match('/\.onion$/', $this->url) or strpos($this->url, '.onion/'))
		{
		curl_setopt($c, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
		curl_setopt($c, CURLOPT_PROXY, "127.0.0.1:9050");
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($c, CURLOPT_TIMEOUT, 60);
		}
	*/

	if (!$a = curl_exec($c))
		{
		Logger::error("URL was: ".$url);
		Logger::error(curl_error($c));
		return false;
		}

	if (($code = curl_getinfo($c, CURLINFO_HTTP_CODE)) >= 400)
		{
		Logger::error('HTTP error '.$code." for $url");
		return false;
		}

	if (($size = curl_getinfo($c, CURLINFO_CONTENT_LENGTH_DOWNLOAD)) > UPLOAD_MAX_SIZE)
		{
		Logger::error('File bigger than '.round(UPLOAD_MAX_SIZE/1024/1024)."MB (".round(CURLINFO_CONTENT_LENGTH_DOWNLOAD/1024/1024)."MB)");
		return false;
		}

	$content_type = curl_getinfo($c, CURLINFO_CONTENT_TYPE);
	if (strpos($content_type, ';') !== FALSE) {
		list($content_type) = explode(';', $content_type);
	}

	return $content_type;
	}

function process_url($url, &$content_type)
	{
	$url = html_entity_decode($url);

	if (preg_match('/https?:\/\/(www\.)?youtube.com\/[^.]*$/', $url)) {
		$safe_url = escapeshellarg($url);
		$url = `youtube-dl --socket-timeout=10 -g -f mp4 {$safe_url}`;
		$url = trim($url);
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
		Logger::error("URL was: ".$url);
		Logger::error(curl_error($c));
		return false;
		}

	if (($code = curl_getinfo($c, CURLINFO_HTTP_CODE)) >= 400)
		{
		Logger::error('HTTP error '.$code." for $url");
		return false;
		}

	if (($size = curl_getinfo($c, CURLINFO_CONTENT_LENGTH_DOWNLOAD)) > UPLOAD_MAX_SIZE)
		{
		Logger::error('File bigger than '.round(UPLOAD_MAX_SIZE/1024/1024)."MB (".round(CURLINFO_CONTENT_LENGTH_DOWNLOAD/1024/1024)."MB)");
		return false;
		}

	$content_type = curl_getinfo($c, CURLINFO_CONTENT_TYPE);
	if (strpos($content_type, ';') !== FALSE) {
		list($content_type) = explode(';', $content_type);
	}
	curl_setopt($c, CURLOPT_HEADER, false);
	curl_setopt($c, CURLOPT_NOBODY, false);
	curl_setopt($c, CURLOPT_HTTPGET, true);

	if (!$image_data = curl_exec($c))
		{
		Logger::error(curl_error($c));
		return false;
		}
	else
		{
		Logger::notice("Link downloaded (".strlen($image_data)." bytes)");

		if (strlen($image_data) < 200) {
			Logger::notice($image_data);
		}

		return $image_data;
		}

	return false;
	}


require_once dirname(__FILE__).'/logger.inc.php';
require_once dirname(__FILE__).'/db.inc.php';

require_once dirname(__FILE__).'/http.inc.php';
require_once dirname(__FILE__).'/url.inc.php';
require_once dirname(__FILE__).'/screenshot.inc.php';
require_once dirname(__FILE__).'/thumbnail.inc.php';
require_once dirname(__FILE__).'/video.inc.php';
require_once dirname(__FILE__).'/image.inc.php';

require_once dirname(__FILE__).'/site.inc.php';
require_once dirname(__FILE__).'/picture.inc.php';
require_once dirname(__FILE__).'/link.inc.php';
require_once dirname(__FILE__).'/tribune.inc.php';
require_once dirname(__FILE__).'/oauth.inc.php';
