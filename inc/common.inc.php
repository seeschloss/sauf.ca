<?php

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
			$condition = "p.user LIKE '$term%' OR p.title LIKE '%$term%' OR p.url LIKE '%$term%' OR p.tags LIKE '%$term%'";
			}

		$conditions[] = "(".$condition.")";
		}

	return count($conditions) ? join(" AND ", $conditions) : "TRUE";
	}

function url($path, $random = false)
	{
	if ($random)
		{
		$SERVERS = array
			(
			'img.sauf.ca',
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

require_once dirname(__FILE__).'/db.inc.php';
require_once dirname(__FILE__).'/site.inc.php';
require_once dirname(__FILE__).'/picture.inc.php';
require_once dirname(__FILE__).'/tribune.inc.php';
