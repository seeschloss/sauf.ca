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

	public $new = false;

	function __construct()
		{
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

		$message = $this->post_clock().'@'.$this->tribune_name.' <a href="'.$this->url.'">[url]</a> '.join(' - ', $text);
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
		$array = array(
			$this->unique_id,
			$this->post_time(),
			"",
			$this->user,
			$this->post_clock().'@'.$this->tribune_name.' <a href="'.$this->url.'">[url]</a> '.join(' - ', $text),
		);

		foreach ($array as &$value) {
			$value = str_replace("\t", " ", $value);
			$value = str_replace("\n", " ", $value);
		}

		return join("\t", $array);
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
		$screenshot_png = "";
		$screenshot_pdf = "";
		if (file_exists($this->screenshot_path))
			{
			$screenshot_png = url(PICTURES_PREFIX.'/'.$this->screenshot_src, false);
			$pdf_screenshot_path = str_replace('.png', '.pdf', $this->screenshot_path);
			if (file_exists($pdf_screenshot_path))
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
			'user-name' => $this->user,
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
			FROM links l
			WHERE l.url = '".$db->escape($this->url)."'"
			;
		$bloubs = (int)$db->value($query);

		$query = "UPDATE links l
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

		$query = 'INSERT INTO links SET
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
			doublons = '.(int)$this->doublons.'
			';

		$db->query($query);

		$this->id = $db->insert_id();

		$db->query('INSERT INTO unique_ids (link_id) VALUES ('.$this->id.')');

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
			if (file_exists($pdf_screenshot_path))
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
		if ($this->type == 'text/html')
			{
			$this->retrieve_embed();
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

		if (file_exists($this->screenshot_path))
			{
			$extra = "<a class='link-png' href='".$this->screenshot_src."'>PNG</a>";
			$pdf_screenshot_path = str_replace('.png', '.pdf', $this->screenshot_path);
			if (file_exists($pdf_screenshot_path))
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

	function retrieve_embed()
		{
		$url = 'http://fprin.tf/?url=' . urlencode($this->url);
		Logger::notice("Asking for info on '".$this->url."' at '".$url."'");
		$json = file_get_contents($url);
		if ($data = json_decode($json, TRUE))
			{
			if (isset($data['title']))
				{
				$this->title = $data['title'];
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
					$dir = date('Y-m-d', $this->date);
					$filename = md5($this->url).'.'.$extension;
					$image_path = UPLOAD_DIR.'/'.$dir.'/t'.$filename;
					file_put_contents($image_path, $image_data);
					link_render_crop($image_path, $image_path, 50, 50, true, $mime);
					$this->thumbnail_path = $image_path;
					$this->thumbnail_src = $dir.'/t'.$filename;
					}
				}

			if (isset($data['screenshot_png_full']))
				{
				$dir = date('Y-m-d', $this->date);
				$filename = md5($this->url).'.png';
				$screenshot_path = UPLOAD_DIR.'/'.$dir.'/'.$filename;

				$png = file_get_contents($data['screenshot_png_full']);
				file_put_contents($screenshot_path, $png);

				$this->screenshot_path = $screenshot_path;
				$this->screenshot_src = $dir.'/'.$filename;
				}

			if (!file_exists($this->thumbnail_path) && isset($data['screenshot_png']))
				{
				$dir = date('Y-m-d', $this->date);
				$filename = md5($this->url).'.png';
				$thumbnail_path = UPLOAD_DIR.'/'.$dir.'/t'.$filename;

				$png = file_get_contents($data['screenshot_png']);
				file_put_contents($thumbnail_path, $png);

				$this->thumbnail_path = $thumbnail_path;
				$this->thumbnail_src = $dir.'/t'.$filename;
				}

			if (isset($data['screenshot_pdf']))
				{
				$dir = date('Y-m-d', $this->date);
				$filename_pdf = md5($this->url).'.pdf';
				$screenshot_path_pdf = UPLOAD_DIR.'/'.$dir.'/'.$filename_pdf;
				$pdf_path = UPLOAD_DIR.'/'.$dir.'/'.$filename_pdf;

				$pdf = file_get_contents($data['screenshot_pdf']);
				file_put_contents($pdf_path, $pdf);
				}
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

