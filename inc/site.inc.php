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
		$thumbnails = $this->thumbnails($params);

		$data = array();
		foreach ($thumbnails as $thumbnail)
			{
			$data[] = $thumbnail->json_array();
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
			$where .= " AND ".search_condition($_GET['search'], 'p');
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
		return $this->name;
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

	function thumbnails($params)
		{
		if (empty($params['count']))
			{
			$params['count'] = NB_INITIAL_THUMBNAILS;
			}

		$db = new DB();

		$where = "TRUE";

		if (!empty($params['search']))
			{
			$where .= ' AND '.search_condition($params['search'], 'p');
			}

		if (!empty($params['animated']))
			{
			$where .= ' AND p.animated';
			}

		if (!empty($params['md5']))
			{
			$where .= " AND p.md5='".$db->escape($params['md5'])."'";
			}

		if (isset($params['since']))
			{
			$where .= ' AND p.date > '.(int)$params['since'];
			}

		$query = 'SELECT p.*, t.name as tribune_name, t.url as tribune_url
			FROM pictures p
			LEFT JOIN tribunes t
			  ON p.tribune_id = t.id
			WHERE '.$where.'
			ORDER BY p.date DESC
			LIMIT 0,'.(int)$params['count'].'
			';
		$result = $db->query($query);

		if ($result) while ($row = $result->fetch_assoc())
			{
			$picture = new Picture();
			$picture->load($row);
			$thumbnails[] = $picture;
			}

		if ((empty($params['animated']) or !$params['animated']) && $GLOBALS['config']['show_links'])
			{
			$where = "TRUE";
			if (!empty($params['search']))
				{
				$where .= ' AND '.search_condition($params['search'], 'l');
				}
			if (isset($params['since']))
				{
				$where .= ' AND l.date > '.(int)$params['since'];
				}
			$query = 'SELECT l.*, t.name as tribune_name, t.url as tribune_url
				FROM links l
				LEFT JOIN tribunes t
				  ON l.tribune_id = t.id
				WHERE '.$where.'
				ORDER BY l.date DESC
				LIMIT 0,'.(int)$params['count'].'
				';
			$result = $db->query($query);

			if ($result) while ($row = $result->fetch_assoc())
				{
				$link = new Link();
				$link->load($row);
				$thumbnails[] = $link;
				}
			}

		usort($thumbnails, function($a, $b) {
			return $a->date < $b->date ? 1 : -1;
		});
		$thumbnails = array_slice($thumbnails, 0, $nb);

		return $thumbnails;
		}

	function show_thumbnails()
		{
		$contents = "";

		$params = array();
		$params['count'] = NB_INITIAL_THUMBNAILS;
		$uri = urldecode(substr($_SERVER['REQUEST_URI'], 1));
		if (strpos($uri, '?') === 0)
			{
			$params['search'] = substr($uri, 1);
			}
		else if (strpos($uri, '!') === 0)
			{
			$params['search'] = substr($uri, 1);
			$params['animated'] = true;
			}
		else if (strpos($uri, '=') === 0)
			{
			$params['md5'] = substr($uri, 1);
			}

		$thumbnails = $this->thumbnails($params);

		$n = 0;
		$prefetch_quantity = 20;
		$prefetch_first = "";
		$prefetch_later = "";
		$prefetch_last = "";
		foreach ($thumbnails as $thumbnail)
			{
			$contents .= $thumbnail->thumbnail();
			if ($n < $prefetch_quantity)
				{
				$n++;

				if ($thumbnail->type == 'video/webm')
					{
					$prefetch_later .= '<link rel="prefetch" href="'.$thumbnail->animated_src().'" />';
					}
				else if ($thumbnail->type == 'image/gif')
					{
					$src = url(PICTURES_PREFIX.'/'.$thumbnail->src, true);
					$prefetch_first .= '<link rel="prefetch" href="'.$thumbnail->animated_src().'" />';
					$prefetch_last  .= '<link rel="prefetch" href="'.$src.'" />';
					}
				else if (strpos('image/', $thumbnail->type) === 0)
					{
					$src = url(PICTURES_PREFIX.'/'.$thumbnail->src, true);
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
			<title>'.strip_tags($title).'</title>
			<link rel="stylesheet" type="text/css" href="style.4.css" />
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
