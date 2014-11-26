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
			<div class='row'>
				<div class='video-container displayed-picture'>
					{$arrow_left}<video class='media' muted autoplay loop src='{$src}' data-picture-id='{$picture->id}'></video>{$arrow_right}
				</div>
			</div>
			<div class='info'>
			</div>
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
			<div class='row'>
				<div class='image-container displayed-picture zoomable'>
					{$arrow_left}
					<img class='media' src='{$src}' data-picture-id='{$picture->id}' />
					{$arrow_right}
					</div>
			</div>
			<div class='info'>
			</div>
		</div>
	</div>
HTML;
				}
			}
		else if (strpos($uri, 'upload') === 0)
			{
			return <<<HTML
	<div id='viewer'>
		<div class='picture'>
			<form id='upload-form' method='post' enctype='multipart/form-data'>
				<p id='upload-explanation'>Vous pouvez poster un lien vers une image ou une vidéo, ou bien uploader un fichier qui sera hébergé sur <a href='http://pomf.se'>pomf.se</a>.</p>
				<span id='upload-span'>
					<input type='file' name='filedata' id='upload-file' />
					<input type='text' name='url' id='upload-url' placeholder='URL' />
				</span>
				<input type='text' name='comment' id='upload-comment' placeholder='commentaire' />
				<input type='submit' name='post' id='upload-post' value='⏎' />
			</form>
		</div>
	</div>
HTML;
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
		else if (strpos($uri, '=') === 0)
			{
			$term = substr($uri, 1);
			$where .= " AND md5='".$db->escape($term)."'";
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

		$n = 0;
		$prefetch_quantity = 20;
		$prefetch_first = "";
		$prefetch_later = "";
		$prefetch_last = "";
		foreach ($pictures as $picture)
			{
			$contents .= $picture->thumbnail();
			if ($n < $prefetch_quantity)
				{
				$n++;

				if ($picture->type == 'video/webm')
					{
					$prefetch_later .= '<link rel="prefetch" href="'.$picture->animated_src().'" />';
					}
				else if ($picture->type == 'image/gif')
					{
					$src = url(PICTURES_PREFIX.'/'.$picture->src, true);
					$prefetch_first .= '<link rel="prefetch" href="'.$picture->animated_src().'" />';
					$prefetch_last  .= '<link rel="prefetch" href="'.$src.'" />';
					}
				else
					{
					$src = url(PICTURES_PREFIX.'/'.$picture->src, true);
					$prefetch_later .= '<link rel="prefetch" href="'.$src.'" />';
					}
				}
			}

		return $contents.$prefetch_first.$prefetch_later.$prefetch_last;
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
			<title>'.htmlspecialchars($title).'</title>
			<link rel="stylesheet" type="text/css" href="style.3.css" />
			<link rel="icon" type="image/png" href="sauf.png" />
			<link rel="dns-prefetch" href="img.sauf.ca" />
			<link rel="dns-prefetch" href="a.img.sauf.ca" />
			<link rel="dns-prefetch" href="b.img.sauf.ca" />
			<link rel="dns-prefetch" href="c.img.sauf.ca" />
		</head>
		<body>';
		}

	function foot()
		{
		return '
			<script type="text/javascript" src="sauf.3.js"></script>
		</body>
	</html>';
		}
	}
