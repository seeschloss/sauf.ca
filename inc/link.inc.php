<?php
class Link
	{
	public $user = "";
	public $id = 0;
	public $thumbnail_path = null;
	public $thumbnail_src = null;
	public $date = 0;
	public $url = "";
	public $title = "";
	public $description = "";
	public $tribune_id = 0;
	public $post_id = 0;
	public $tags = "";
	public $raw_tags = "";
	public $type = '';
	public $target = '';
	public $html = '';
	public $context = '';
	public $doublons = null;

	public $new = false;

	function __construct()
		{
		}

	static function acceptable($data, $content_type)
		{
		return true;
		}

	function load_by_post_id($post_id)
		{
		$data = array();
		$db = new DB();

		$query = 'SELECT l.*, t.name as tribune_name, t.url as tribune_url
			FROM links l
			LEFT JOIN tribunes t
			  ON l.tribune_id = t.id
			WHERE l.post_id = '.(int)$post_id
			;
		$result = $db->query($query);

		if ($result) while ($row = $result->fetch_assoc())
			{
			$data = $row;
			}

		foreach ($data as $key => $value)
			{
			$this->{$key} = $value;
			}

		return $this->id;
		}

	function load_by_id($id)
		{
		$data = array();
		$db = new DB();

		$query = 'SELECT l.*, t.name as tribune_name, t.url as tribune_url
			FROM links l
			LEFT JOIN tribunes t
			  ON l.tribune_id = t.id
			WHERE l.id = '.(int)$id
			;
		$result = $db->query($query);

		if ($result) while ($row = $result->fetch_assoc())
			{
			$data = $row;
			}

		foreach ($data as $key => $value)
			{
			$this->{$key} = $value;
			}

		return $this->id;
		}

	function load($data)
		{
		if (!is_array($data))
			{
			return $this->load_by_id((int)$data);
			}

		foreach ($data as $key => $value)
			{
			$this->{$key} = $value;
			}

		return $this->id;
		}

	function json_array()
		{
		$array = array
			(
			'id' => $this->id,
			'url' => $this->url,
			'title' => $this->title,
			'description' => $this->description,
			'target' => $this->target,
			'user' => $this->user,
			'tags' => explode("\n", trim($this->tags)),
			'date' => $this->date,
			'tribune-name' => $this->tribune_name,
			'tribune-url' => $this->tribune_url,
			'post-id' => $this->post_id,
			'thumbnail-src' => url(PICTURES_PREFIX.'/'.$this->thumbnail_src, true),
			'html' => $this->html,
			'context' => $this->context,
			'bloubs' => $this->doublons,
			);

		return $array;
		}

	function bloubs()
		{
		$db = new DB();
		$query = "SELECT COUNT(*)
			FROM link l
			WHERE l.target = '".$db->escape($this->target)."'"
			;
		$bloubs = (int)$db->value($query);

		$query = "UPDATE links l
			SET doublons = ".(int)$bloubs."
			WHERE l.target = '".$db->escape($this->target)."'"
			;
		$db->query($query);

		return $bloubs;
		}

	function delete()
		{
		if ($this->id)
			{
			$db = new DB();
			$query = 'DELETE FROM links
			WHERE id = '.(int)$this->id.'
			';
			$db->query($query);
			}
		}

	function insert()
		{
		$db = new DB();

		$query = 'INSERT INTO links SET
			user = \''.$db->escape($this->user).'\',
			thumbnail_path = \''.$db->escape($this->thumbnail_path).'\',
			thumbnail_src = \''.$db->escape($this->thumbnail_src).'\',
			date = \''.$db->escape($this->date).'\',
			url = \''.$db->escape($this->url).'\',
			title = \''.$db->escape($this->title).'\',
			description = \''.$db->escape($this->description).'\',
			tribune_id = \''.$db->escape($this->tribune_id).'\',
			post_id = \''.$db->escape($this->post_id).'\',
			tags = \''.$db->escape($this->tags).'\',
			raw_tags = \''.$db->escape($this->raw_tags).'\',
			type = \''.$db->escape($this->type).'\',
			target = \''.$db->escape($this->target).'\',
			html = \''.$db->escape($this->html).'\',
			context = \''.$db->escape($this->context).'\',
			doublons = '.(int)$this->doublons.'
			';

		$db->query($query);

		$this->id = $db->insert_id();

		return $this->id;
		}

	function update()
		{
		if ($this->id
		and $this->thumbnail_path
		and $this->thumbnail_src
		and $this->url
		and $this->date
		and $this->tribune_id)
			{
			$db = new DB();

			$query = 'UPDATE pictures SET
				user = \''.$db->escape($this->user).'\',
				thumbnail_path = \''.$db->escape($this->thumbnail_path).'\',
				thumbnail_src = \''.$db->escape($this->thumbnail_src).'\',
				date = \''.$db->escape($this->date).'\',
				url = \''.$db->escape($this->url).'\',
				title = \''.$db->escape($this->title).'\',
				description = \''.$db->escape($this->description).'\',
				tribune_id = \''.$db->escape($this->tribune_id).'\',
				post_id = \''.$db->escape($this->post_id).'\',
				tags = \''.$db->escape($this->tags).'\',
				raw_tags = \''.$db->escape($this->raw_tags).'\',
				type = \''.$db->escape($this->type).'\',
				target = \''.$db->escape($this->target).'\',
				html = \''.$db->escape($this->html).'\',
				context = \''.$db->escape($this->context).'\',
				doublons = '.(int)$this->doublons.'
				WHERE id = '.(int)$this->id
				;

			$db->query($query);

			return true;
			}

		return false;
		}

	function user()
		{
		$user = new User();
		$user->load($this->user_id);
		return $user;
		}

	function thumbnail()
		{
		$extra = '';

		if ($this->doublons !== null)
			{
			$extra .= ' data-bloubs="'.(int)$this->doublons.'"';
			}

		$src = url(PICTURES_PREFIX.'/'.$this->thumbnail_src, true);
		$href = url('+'.$this->id);

		$attributes = array();
		foreach ($this->json_array() as $key => $value)
			{
			$attributes[] = 'data-'.$key.'="'.htmlspecialchars($value).'"';
			}
		$attributes = join(' ', $attributes);

		return
			'<a id="thumbnail-'.$this->id.'" href="'.$href.'" class="thumbnail-link" '.
					$attributes.
					$extra.
					'>'.
				'<img height="'.THUMBNAIL_SIZE.'" width="'.THUMBNAIL_SIZE.'" src="'.$src.'" alt="" />'.
			'</a>';
		}

	function generate_thumbnail($data)
		{
		if ($this->type == 'text/html')
			{
			return $this->parse_html($data);
			}
		}

	function parse_html($data)
		{
		$dom = new DOMDocument();
		$dom->loadHTML($data, LIBXML_NOERROR);

		$titles = $dom->getElementsByTagName('title');
		foreach ($titles as $title)
			{
			$this->title = $title->textContent;
			}

		require_once __DIR__ . '/../lib/Opengraph/src/Opengraph/Meta.php';
		require_once __DIR__ . '/../lib/Opengraph/src/Opengraph/Opengraph.php';
		require_once __DIR__ . '/../lib/Opengraph/src/Opengraph/Reader.php';

		$reader = new Opengraph\Reader();
		$reader->parse($data);
		$tags = $reader->getArrayCopy();

		var_dump($tags);

		if (isset($tags['og:title']))
			{
			$this->title = $tags['og:title'];
			}

		if (isset($tags['og:description']))
			{
			$this->description = $tags['og:description'];
			}

		if (isset($tags['og:image'][0]['og:image:url']))
			{
			$image_data = file_get_contents($tags['og:image'][0]['og:image:url']);
			$finfo = new Finfo(FILEINFO_MIME);
			@list($mime, $charset) = explode(';', $finfo->buffer($image_data));

			$extension = false;
			switch ($mime)
				{
				case 'image/jpeg':
					$extension = 'jpg';
					break;
				case 'image/gif':
					$extension = 'gif';
					break;
				case 'image/png':
					$extension = 'png';
					break;
				}

			if ($extension)
				{
				$dir = date('Y-m-d', $this->date);
				$filename = md5($image).'.'.$extension;
				$image_path = UPLOAD_DIR.'/'.$dir.'/'.$filename;
				file_put_contents($image_path, $image_data);
				$this->thumbnail_path = $image_path;
				$this->thumbnail_src = $dir.'/'.$filename;
				}
			}

		$html = '';
		if ($this->title)
			{
			$html .= '<span class="link-title">'.htmlentities($this->title).'</span>';
			}
		if ($this->description)
			{
			$html .= '<span class="link-description">'.htmlentities($this->description).'</span>';
			}

		
		if (!$this->description && !$this->thumbnail_src)
			{
			$dir = date('Y-m-d', $this->date);
			$filename = md5($this->url).'.png';
			$image_path = UPLOAD_DIR.'/'.$dir.'/'.$filename;
			$phantomjs = dirname(__FILE__).'/../lib/phantomjs/phantomjs-1.9.8-linux-x86_64/bin/phantomjs';
			$phantomjs_script = dirname(__FILE__).'/../lib/phantomjs/render.js';
			$url_arg = escapeshellarg($this->url);
			trigger_error("Using phantomjs to render \"{$this->url}\" to $image_path");
			`"$phantomjs" "$phantomjs_script" $url_arg "$image_path"`;

			if (file_exists($image_path))
				{
				link_render_crop($image_path, 200, 200);
				$this->thumbnail_path = $image_path;
				$this->thumbnail_src = $dir.'/'.$filename;
				}
			}


		if ($this->thumbnail_src)
			{
			$url = url(PICTURES_PREFIX.'/'.$this->thumbnail_src, true);
			$html .= '<a class="link-thumbnail" href="'.$url.'" />';
			}

		$this->html = $html;
		}
	}

function link_render_crop($image_file, $width, $height)
	{
	$im = imagecreatefrompng($image_file);

	list($width_orig, $height_orig, $image_type) = getimagesize($image_file);
	$corig = min($width_orig, $height_orig);

	$lmarge = ($width_orig  - $corig) / 2;
	$hmarge = 0;

	$image_thumbnail = imagecreatetruecolor($width, $height);

	imagecopyresampled($image_thumbnail, $im, 0, 0, $lmarge, $hmarge, $width, $height, $corig, $corig);
	@unlink($image_file);
	imagepng($image_thumbnail, $image_file, 9);
	}

