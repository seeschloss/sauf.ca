<?php
class Link
	{
	public $user = "";
	public $id = 0;
	public $thumbnail_path = null;
	public $thumbnail_src = null;
	public $screenshot_path = null;
	public $screenshot_src = null;
	public $date = 0;
	public $url = "";
	public $title = "";
	public $description = "";
	public $tribune_id = 0;
	public $tribune_name = "";
	public $post_id = 0;
	public $tags = "";
	public $raw_tags = "";
	public $type = '';
	public $target = '';
	public $html = '';
	public $context = '';
	public $doublons = null;
	public $unique_id = 0;
	public $random_id = "";
	public $published = 0;

	public $new = false;

	function __construct()
		{
		$this->random_id = substr(sha1(rand() . $this->html . $this->url), 0, 32);
		// Will get overridden when loading an existing link
		}

	static function acceptable($content_type)
		{
		return true;
		}

	function post_time() {
		return date('YmdHis', $this->date);
	}

	function post_clock() {
		return date('H:i:s', $this->date);
	}

	function xml_element() {
		$text = array();
		if ($this->title) {
			$text[] = $this->title;
		}
		if ($this->description) {
			$text[] = $this->description;
		}

		$message = $this->post_clock().
			'@'.$this->tribune_name;

		if (file_exists($this->screenshot_path))
			{
			$screenshot_png = url(PICTURES_PREFIX.'/'.$this->screenshot_src, false);

			$message .= " <a href=\"".htmlspecialchars($screenshot_png)."\">[png]</a> ";

			$pdf_screenshot_path = str_replace('.png', '.pdf', $this->screenshot_path);
			if (file_exists($pdf_screenshot_path) and !is_blacklisted($this->url, "pdf"))
				{
				$pdf_src = str_replace('.png', '.pdf', $this->screenshot_src);
				$screenshot_pdf = url(PICTURES_PREFIX.'/'.$pdf_src, false);
				$message .= " <a href=\"".htmlspecialchars($screenshot_pdf)."\">[pdf]</a> ";
				}
			}

		$message .=
			' - <a href="'.htmlspecialchars($this->url).'">[url]</a> '.htmlspecialchars(html_entity_decode(join(' - ', $text), ENT_QUOTES, "UTF-8"));
		$login = htmlspecialchars($this->user);

		$xml = <<<XML
	<post time="{$this->post_time()}" id="{$this->unique_id}">
		<info></info>
		<message>$message</message>
		<login>$login</login>
	</post>

XML;

		return $xml;
	}

	function tsv_line() {
		$text = array();
		if ($this->title) {
			$text[] = $this->title;
		}
		if ($this->description) {
			$text[] = $this->description;
		}

		$message = $this->post_clock().'@'.$this->tribune_name;

		if (file_exists($this->screenshot_path))
			{
			$screenshot_png = url(PICTURES_PREFIX.'/'.$this->screenshot_src, false);

			$message .= " <a href=\"".$screenshot_png."\">[png]</a> ";

			$pdf_screenshot_path = str_replace('.png', '.pdf', $this->screenshot_path);
			if (file_exists($pdf_screenshot_path) and !is_blacklisted($this->url, "pdf"))
				{
				$pdf_src = str_replace('.png', '.pdf', $this->screenshot_src);
				$screenshot_pdf = url(PICTURES_PREFIX.'/'.$pdf_src, false);
				$message .= " <a href=\"".$screenshot_pdf."\">[pdf]</a> ";
				}
			}

		$message .= ' - <a href="'.$this->url.'">[url]</a> '.join(' - ', $text);

		$array = array(
			$this->unique_id,
			$this->post_time(),
			"",
			$this->user,
			$message,
		);

		foreach ($array as &$value) {
			$value = str_replace("\t", " ", $value);
			$value = str_replace("\n", " ", $value);
		}

		return join("\t", $array);
	}

	function animated_src()
		{
		return '';
		}

	function load_by_post_id($post_id)
		{
		$data = array();
		$db = new DB();

		$query = 'SELECT l.*, t.name as tribune_name, t.url as tribune_url
			FROM links AS l
			LEFT JOIN tribunes AS t
			  ON l.tribune_id = t.id
			WHERE l.post_id = '.(int)$post_id
			;
		$result = $db->query($query);

		if ($result) while ($row = $result->fetch(PDO::FETCH_ASSOC))
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
			FROM links AS l
			LEFT JOIN tribunes AS t
			  ON l.tribune_id = t.id
			WHERE l.id = '.(int)$id
			;
		$result = $db->query($query);

		if ($result) while ($row = $result->fetch(PDO::FETCH_ASSOC))
			{
			$data = $row;
			}

		foreach ($data as $key => $value)
			{
			$this->{$key} = $value;
			}

		return $this->id;
		}

	function load_by_random_id($random_id)
		{
		$data = array();
		$db = new DB();

		$query = 'SELECT l.*, t.name as tribune_name, t.url as tribune_url
			FROM links AS l
			LEFT JOIN tribunes AS t
			  ON l.tribune_id = t.id
			WHERE l.random_id = \''.$db->escape($random_id).'\'';
			;
		$result = $db->query($query);

		if ($result) while ($row = $result->fetch(PDO::FETCH_ASSOC))
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
		$screenshot_png = "";
		$screenshot_pdf = "";
		if (file_exists($this->screenshot_path))
			{
			$screenshot_png = url(PICTURES_PREFIX.'/'.$this->screenshot_src, false);
			$pdf_screenshot_path = str_replace('.png', '.pdf', $this->screenshot_path);
			if (file_exists($pdf_screenshot_path) and !is_blacklisted($this->url, "pdf"))
				{
				$pdf_src = str_replace('.png', '.pdf', $this->screenshot_src);
				$screenshot_pdf = url(PICTURES_PREFIX.'/'.$pdf_src, false);
				}
			}
		$array = array
			(
			'id' => $this->id,
			'unique-id' => $this->unique_id,
			'media' => 'link',
			'url' => $this->url,
			'title' => $this->title,
			'description' => $this->description,
			'target' => $this->target,
			'user' => $this->user,
			'user-name' => ($this->user == "-" && $this->post_info != "") ? $this->post_info : $this->user,
			'tags' => explode("\n", trim($this->tags)),
			'date' => $this->date,
			'tribune-name' => $this->tribune_name,
			'tribune-url' => $this->tribune_url,
			'post-id' => $this->post_id,
			'thumbnail-src' => $this->thumbnail_src ? url(PICTURES_PREFIX.'/'.$this->thumbnail_src, true) : "",
			'screenshot-png' => $screenshot_png,
			'screenshot-pdf' => $screenshot_pdf,
			'html' => $this->html,
			'context' => $this->context,
			'bloubs' => $this->doublons,
			'type' => $this->type,
			);

		return $array;
		}

	function bloubs()
		{
		$db = new DB();
		$query = "SELECT COUNT(*)
			FROM links AS l
			WHERE l.url = '".$db->escape($this->url)."'"
			;
		$bloubs = (int)$db->value($query);

		$query = "UPDATE links AS l
			SET doublons = ".(int)$bloubs."
			WHERE l.url = '".$db->escape($this->url)."'"
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

		$db->insert('links', [
			'user' => "'".$db->escape($this->user)."'",
			'thumbnail_path' => "'".$db->escape($this->thumbnail_path)."'",
			'thumbnail_src' => "'".$db->escape($this->thumbnail_src)."'",
			'screenshot_path' => "'".$db->escape($this->screenshot_path)."'",
			'screenshot_src' => "'".$db->escape($this->screenshot_src)."'",
			'date' => "'".$db->escape($this->date)."'",
			'url' => "'".$db->escape($this->url)."'",
			'title' => "'".$db->escape($this->title)."'",
			'description' => "'".$db->escape($this->description)."'",
			'tribune_id' => "'".$db->escape($this->tribune_id)."'",
			'post_id' => "'".$db->escape($this->post_id)."'",
			'tags' => "'".$db->escape($this->tags)."'",
			'raw_tags' => "'".$db->escape($this->raw_tags)."'",
			'type' => "'".$db->escape($this->type)."'",
			'target' => "'".$db->escape($this->target)."'",
			'html' => "'".$db->escape($this->html)."'",
			'context' => "'".$db->escape($this->context)."'",
			'random_id' => "'".$db->escape($this->random_id)."'",
			'doublons' => (int)$this->doublons,
			'published' => (int)$this->published
		]);

		$this->id = $db->insert_id();

		$db->query('INSERT INTO unique_ids (link_id) VALUES ('.$this->id.')');
		$this->unique_id = $db->insert_id();

		return $this->id;
		}

	function update()
		{
		if ($this->id
		and $this->url
		and $this->date
		and $this->tribune_id)
			{
			$db = new DB();

			$query = 'UPDATE links SET
				user = \''.$db->escape($this->user).'\',
				thumbnail_path = \''.$db->escape($this->thumbnail_path).'\',
				thumbnail_src = \''.$db->escape($this->thumbnail_src).'\',
				screenshot_path = \''.$db->escape($this->screenshot_path).'\',
				screenshot_src = \''.$db->escape($this->screenshot_src).'\',
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
				random_id = \''.$db->escape($this->random_id).'\',
				doublons = '.(int)$this->doublons.',
				published = '.(int)$this->published.'
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

		$attributes = array();
		foreach ($this->json_array() as $key => $value)
			{
			if ($key == 'tags')
				{
				$value = implode(' ', $value);
				}
			$attributes[] = 'data-'.$key.'="'.htmlspecialchars($value).'"';
			}

		$title = '';
		$html = '';
		if ($this->thumbnail_src)
			{
			$url = url(PICTURES_PREFIX.'/'.$this->thumbnail_src, true);
			$html .= '<img class="link-preview" src="'.$url.'" />';
			}

		$text = '';
		if ($this->title)
			{
			$text .= '<span class="link-title">'.htmlspecialchars($this->title).'</span>';
			$title .= $this->title;
			}
		if ($this->description)
			{
			$text .= '<span class="link-description">'.htmlspecialchars($this->description).'</span>';
			$title .= "\n\n".$this->description;
			}

		$links = "";
		if (file_exists($this->screenshot_path))
			{
			$attributes[] = 'data-screenshot-png="'.htmlspecialchars(url(PICTURES_PREFIX.'/'.$this->screenshot_src, false)).'"';
			$links = "<a target='_blank' class='link-png' href='".url(PICTURES_PREFIX.'/'.$this->screenshot_src, false)."'>PNG</a>";
			$pdf_screenshot_path = str_replace('.png', '.pdf', $this->screenshot_path);
			if (file_exists($pdf_screenshot_path) and !is_blacklisted($this->url, "pdf"))
				{
				$pdf_src = str_replace('.png', '.pdf', $this->screenshot_src);
				$attributes[] = 'data-screenshot-pdf="'.htmlspecialchars(url(PICTURES_PREFIX.'/'.$pdf_src, false)).'"';
				$links .= "<a target='_blank' class='link-pdf' href='".url(PICTURES_PREFIX.'/'.$pdf_src)."'>PDF</a>";
				}
			}

		$html .= '<span class="link-text" title="'.str_replace('"', '\'', $title).'">';
		$html .= $text;

		$html .= '</span>';

		$attributes = join(' ', $attributes);

		$html =
			'<span class="thumbnail"><a target="_blank" id="thumbnail-'.$this->id.'" href="'.$this->url.'" class="thumbnail-link link" '.
					$attributes.
					$extra.
					'>'.$html.
			'</a>';

		if ($links)
			{
			$html .= '<span class="link-extra">'.$links.'</span>';
			}

		$html .= '</span>';

		return $html;
		}

	function generate_thumbnail()
		{
		$html = '';
		if ($this->title)
			{
			$html .= '<span class="link-title">'.htmlentities($this->title).'</span>';
			}
		if ($this->description)
			{
			$html .= '<span class="link-description">'.htmlentities($this->description).'</span>';
			}

		if (file_exists($this->screenshot_path))
			{
			$extra = "<a class='link-png' href='".$this->screenshot_src."'>PNG</a>";
			$pdf_screenshot_path = str_replace('.png', '.pdf', $this->screenshot_path);
			if (file_exists($pdf_screenshot_path) and !is_blacklisted($this->url, "pdf"))
				{
				$extra = "<a class='link-pdf' href='".str_replace('.png', '.pdf', $this->screenshot_src)."'>PDF</a>";
				}
			$html .= '<span class="link-extra">'.$extra.'</span>';
			}

		if ($this->thumbnail_src)
			{
			$url = url(PICTURES_PREFIX.'/'.$this->thumbnail_src, true);
			$html .= '<img class="link-preview" src="'.$url.'" />';
			}

		$this->html = $html;
		}

	function parse_fprintf_data($data)
		{
		if (isset($data['title']))
			{
			$this->title = mb_substr($data['title'], 0, 1024);
			}

		if (isset($data['description']))
			{
			$this->description = mb_substr($data['description'], 0, 2048);
			}

		if (isset($data['image_url']))
			{
			$image_data = file_get_contents($data['image_url']);
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
				$this->thumbnail_path = File::path($this->date, 't'.md5($this->url), 'png');
				$this->thumbnail_src = File::src($this->date, 't'.md5($this->url), 'png');

				File::store($png, $this->date, 't'.md5($this->url), $extension);

				$this->thumbnail_path = File::path($this->date, 't'.md5($this->url), 'png');
				$this->thumbnail_src = File::src($this->date, 't'.md5($this->url), 'png');

				link_render_crop($this->thumbnail_path, $this->thumbnail_path, 50, 50, true, $mime);
				}
			}

		if (isset($data['screenshot_png_full']))
			{
			$png = file_get_contents($data['screenshot_png_full']);
			File::store($png, $this->date, md5($this->url), 'png');

			$this->screenshot_path = File::path($this->date, md5($this->url), 'png');
			$this->screenshot_src = File::src($this->date, md5($this->url), 'png');
			}

		if (!file_exists($this->thumbnail_path) && isset($data['screenshot_png']))
			{
			$png = file_get_contents($data['screenshot_png']);
			File::store($png, $this->date, 't'.md5($this->url), 'png');

			$this->thumbnail_path = File::path($this->date, 't'.md5($this->url), 'png');
			$this->thumbnail_src = File::src($this->date, 't'.md5($this->url), 'png');
			}

		if (isset($data['screenshot_pdf']))
			{
			$pdf = file_get_contents($data['screenshot_pdf']);
			File::store($pdf, $this->date, md5($this->url), 'pdf');
			}
		}

	function retrieve_embed()
		{
		$url = 'http://fprin.tf/?url=' . urlencode($this->url) . '&data=' . $this->random_id . '&callback=http://sauf.ca/callback.php';
		//$url = 'http://fprin.tf/?url=' . urlencode($this->url) . '&data=' . $this->random_id ;

		Logger::notice("Asking for info on '".$this->url."' at '".$url."'");
		$json = file_get_contents($url);
		if ($data = json_decode($json, TRUE))
			{
			$this->parse_fprintf_data($data);
			}
		}
	}

function link_render_crop($image_filename, $destination_filename, $width, $height, $vertical_center = false, $mime)
	{
	switch ($mime)
		{
		case 'image/gif':  $im = imagecreatefromgif($image_filename); break;
		case 'image/jpeg': $im = imagecreatefromjpeg($image_filename); break;
		case 'image/png':  $im = imagecreatefrompng($image_filename); break;
		}

	list($width_orig, $height_orig, $image_type) = getimagesize($image_filename);
	$corig = min($width_orig, $height_orig);

	$lmarge = ($width_orig  - $corig) / 2;
	$hmarge = $vertical_center ? ($height_orig  - $corig) / 2 : 0;

	$image_thumbnail = imagecreatetruecolor($width, $height);

	imagecopyresampled($image_thumbnail, $im, 0, 0, $lmarge, $hmarge, $width, $height, $corig, $corig);
	@unlink($destination_filename);

	switch ($mime)
		{
		case 'image/png':
			$white = imagecolorallocate($image_thumbnail, 255, 255, 255);
			imagecolortransparent($image_thumbnail, $white);
			imagealphablending($image_thumbnail, false);
			imagesavealpha($image_thumbnail, true);
		case 'image/gif':
			imagepng($image_thumbnail, $destination_filename, 9);
			break;
		case 'image/jpeg':
			imagejpeg($image_thumbnail, $destination_filename, 99);
			break;
		}
	}

