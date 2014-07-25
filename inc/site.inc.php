<?php
class Site
	{
	private $name = null;
	private $page = null;

	private $user = null;

	function __construct($name)
		{
		$this->name = $name;
		}

	function latest_json($params)
		{
		$db = new DB();

		$pictures = array();

		$where = 'TRUE';

		$count = 500;
		if (!empty($params['count']))
			{
			$count = min(500, $params['count']);
			}

		if (isset($params['since']))
			{
			$where .= ' AND p.id > '.(int)$params['since'];
			}

		if (isset($params['search']))
			{
			$where .= ' AND '.search_condition($params['search']);
			}

		if (isset($params['animated']))
			{
			$where .= ' AND animated';
			}

		$query = 'SELECT p.*, t.name as tribune_name, t.url as tribune_url
			FROM pictures p
			LEFT JOIN tribunes t
				ON p.tribune_id = t.id
			WHERE '.$where.'
			ORDER BY p.date DESC
			LIMIT '.$count
		;
		$result = $db->query($query);

		if ($result) while ($row = $result->fetch_assoc())
			{
			$picture = new Picture();
			$picture->load($row);
			$pictures[] = $picture;
			}

		$data = array();

		foreach ($pictures as $picture)
			{
			$data[] = $picture->json_array();
			}

		return json_encode($data);
		}

	function history_json($until_id, $count = 250)
		{
		$db = new DB();

		$count = min(500, $count);

		$pictures = array();

		$where = "p.id < ".(int)$until_id;
		if (isset($_GET['search']))
			{
			$where .= " AND ".search_condition($_GET['search']);
			}

		if (isset($_GET['animated']))
			{
			$where .= ' AND animated';
			}

		$query = 'SELECT p.*, t.name as tribune_name, t.url as tribune_url
			FROM pictures p
			LEFT JOIN tribunes t
				ON p.tribune_id = t.id
			WHERE '.$where.'
			ORDER BY p.date DESC
			LIMIT '.$count
		;
		$result = $db->query($query);

		if ($result) while ($row = $result->fetch_assoc())
			{
			$picture = new Picture();
			$picture->load($row);
			$pictures[] = $picture;
			}

		$data = array();

		foreach ($pictures as $picture)
			{
			$data[] = $picture->json_array();
			}

		return json_encode($data);
		}

	function name()
		{
		return htmlentities($this->name, ENT_QUOTES, 'UTF-8');
		}

	function viewer()
		{
		$uri = substr($_SERVER['REQUEST_URI'], 1);
		if ($n = strpos($uri, '?'))
			{
			$uri = substr($uri, 0, $n);
			}

		if ($uri[0] == '+')
			{
			$picture_id = substr($uri, 1);
			$picture = new Picture();
			$picture->load($picture_id);

			$src = url(PICTURES_PREFIX.'/'.$picture->src, true);

			$picture_test = new Picture();
			if ($picture_test->load($picture_id + 1)) {
				$arrow_left = "<a href='".url('+'.($picture_id + 1))."' id='arrow-left'>&lt;</a>";
			} else {
				$arrow_left = "<a href='' id='arrow-left' class='hidden'>&lt;</a>";
			}
			
			if ($picture_test->load($picture_id - 1)) {
				$arrow_right = "<a href='".url('+'.($picture_id - 1))."' id='arrow-right'>&gt;</a>";
			} else {
				$arrow_right = "<a href='' id='arrow-right' class='hidden'>&gt;</a>";
			}
			
			if (preg_match('/.webm$/', $picture->path))
				{
				return <<<HTML
	<div id='viewer'>
		<div class='extra'>
		</div>
		<div class='picture'>
			<div class='video-container displayed-picture'>
				{$arrow_left}<video muted autoplay loop src='{$src}' data-picture-id='{$picture->id}'></video>{$arrow_right}
			</div>
		</div>
		<div class='info'>
		</div>
	</div>
HTML;
				}
			else
				{
				return <<<HTML
	<div id='viewer'>
		<div class='extra'>
		</div>
		<div class='picture'>
			{$arrow_left}
			<img class='displayed-picture zoomable' src='{$src}' data-picture-id='{$picture->id}' />
			{$arrow_right}
		</div>
		<div class='info'>
		</div>
	</div>
HTML;
				}
			}
		else
			{
			return '';
			}
		}

	function thumbnails()
		{
		$nb = NB_INITIAL_THUMBNAILS;
		$db = new DB();

		$where = "TRUE";
		$uri = urldecode(substr($_SERVER['REQUEST_URI'], 1));
		if (strpos($uri, '?') === 0)
			{
			$term = substr($uri, 1);
			$where .= ' AND '.search_condition($term);
			}
		else if (strpos($uri, '!') === 0)
			{
			$term = substr($uri, 1);
			$where .= ' AND '.search_condition($term);
			$where .= ' AND animated';
			}

		$pictures = array();

		$query = 'SELECT p.*, t.name as tribune_name, t.url as tribune_url
			FROM pictures p
			LEFT JOIN tribunes t
			  ON p.tribune_id = t.id
			WHERE '.$where.'
			ORDER BY p.date DESC
			LIMIT 0,'.(int)$nb.'
			';
		$result = $db->query($query);

		if ($result) while ($row = $result->fetch_assoc())
			{
			$picture = new Picture();
			$picture->load($row);
			$pictures[] = $picture;
			}

		$contents = "";

		foreach ($pictures as $picture)
			{
			$contents .= $picture->thumbnail();
			}

		return $contents;
		}

	function header()
		{
		$html = '';

		$db = new DB();
		$query = 'SELECT name, count(*) as n
			FROM pictures
			GROUP BY name
			HAVING n > 1
			ORDER BY n DESC
		';
		$result = $db->query($query);
		$bloubs = array();

		if ($result) while ($row = $result->fetch_assoc())
			{
			$bloubs[] = $row;
			}

		$html .= count($bloubs).' bloubs';

		return $html;
		}

	function head($desc = "")
		{
		$title = $this->name;
		if ($desc) {
			$title .= " | ".$this->name;
		}
		return
'	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
		<head>
		<meta name="verify-v1" content="n17g0PlfuQUJL57geBEf7j+Nc22f+1pPdcvM2HWTX1c=" />
			<title>'.htmlspecialchars($title).'</title>
			<link rel="stylesheet" type="text/css" href="style.3.css" />
		</head>
		<body>';
		}

	function foot()
		{
		return '
			<script type="text/javascript" src="sauf.2.js"></script>
		</body>
	</html>';
		}
	}
