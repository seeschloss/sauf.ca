<?php

class URL {
	public $id = 0;

	public $screenshot_id = 0;
	public $thumbnail_id = 0;
	public $video_id = 0;
	public $image_id = 0;

	public $random_id = "";
	public $published = 0;
	public $url = "";
	public $date = 0;
	public $post_tribune_id = 0;
	public $post_id = 0;
	public $post_user = "";
	public $post_message = "";
	public $post_info = "";

	public $tags = "";
	public $title = "";
	public $description = "";

	public $unique_id = 0;

	function __construct($url = "") {
		$this->url = $url;
	}

	function load_by_random_id($random_id) {
		$data = array();
		$db = new DB();

		$query = 'SELECT u.*, t.name as tribune_name, t.url as tribune_url
			FROM urls u
			LEFT JOIN tribunes t
			  ON u.post_tribune_id = t.id
			WHERE u.random_id = \''.$db->escape($random_id).'\'';
			;
		$result = $db->query($query);

		if ($result) while ($row = $result->fetch_assoc()) {
			$data = $row;
		}

		foreach ($data as $key => $value) {
			$this->{$key} = $value;
		}

		return $this->id;
	}

	function insert() {
		$db = new DB();

		$query = 'INSERT INTO urls SET
			unique_id = '.(int)$this->unique_id.',
			screenshot_id = '.(int)$this->screenshot_id.',
			thumbnail_id = '.(int)$this->thumbnail_id.',
			image_id = '.(int)$this->image_id.',
			video_id = '.(int)$this->video_id.',
			random_id = \''.$db->escape($this->random_id).'\',
			published = '.(int)$this->published.',
			url = \''.$db->escape($this->url).'\',
			date = '.(int)$this->date.',
			post_tribune_id = '.(int)$this->post_tribune_id.',
			post_id = '.(int)$this->post_id.',
			post_user = \''.$db->escape($this->post_user).'\',
			post_message = \''.$db->escape($this->post_message).'\',
			post_info = \''.$db->escape($this->post_info).'\',
			title = \''.$db->escape($this->title).'\',
			description = \''.$db->escape($this->description).'\',
			tags = \''.$db->escape($this->tags).'\'
			';

		$db->query($query);

		$this->id = $db->insert_id();

		return $this->id;
	}

	function update() {
		if (!$this->id) {
			return false;
		}

		$db = new DB();

		$query = 'UPDATE urls SET
			unique_id = '.(int)$this->unique_id.',
			screenshot_id = \''.$db->escape($this->screenshot_id).'\',
			thumbnail_id = '.(int)$this->thumbnail_id.',
			image_id = '.(int)$this->image_id.',
			video_id = '.(int)$this->video_id.',
			random_id = \''.$db->escape($this->random_id).'\',
			published = '.(int)$this->published.',
			url = \''.$db->escape($this->url).'\',
			date = '.(int)$this->date.',
			post_tribune_id = '.(int)$this->post_tribune_id.',
			post_id = '.(int)$this->post_id.',
			post_user = \''.$db->escape($this->post_user).'\',
			post_message = \''.$db->escape($this->post_message).'\',
			post_info = \''.$db->escape($this->post_info).'\',
			title = \''.$db->escape($this->title).'\',
			description = \''.$db->escape($this->description).'\',
			tags = \''.$db->escape($this->tags).'\'
			WHERE id = '.(int)$this->id.'
			';

		$db->query($query);

		return true;
	}

	function retrieve_embed() {
		$url = 'http://fprin.tf/'.
			'?url=' . urlencode($this->url).
			'&data=' . $this->random_id.
			'&callback=http://sauf.ca/callback-fprintf.php';

		Logger::notice("Asking for info on '".$this->url."' at '".$url."'");
		$http = new HTTP($url);
		if (!$http->get()) {
			Logger::error("Could not request info to '".$url."': ".$http->error);
		}
	}

	function parse_fprintf_data($data) {
		Logger::notice(print_r($data, TRUE));

		if (isset($data['title'])) {
			$this->title = $data['title'];
		}

		if (isset($data['description'])) {
			$this->description = $data['description'];
		}

		if (isset($data['screenshot_pdf']) or isset($data['screenshot_png'])) {
			$screenshot = new Screenshot();
			$screenshot->random_id = $this->random_id;
			
			if (isset($data['screenshot_pdf'])) {
				$screenshot->retrieve_image($data['screenshot_pdf'], 'pdf');
			}
			
			if (isset($data['screenshot_png'])) {
				$screenshot->retrieve_image($data['screenshot_png'], 'png_cropped');
			}
			
			if (isset($data['screenshot_png_full'])) {
				$screenshot->retrieve_image($data['screenshot_png_full'], 'png_full');
			}

			if ($screenshot->insert()) {
				$this->screenshot_id = $screenshot->id;
			}
		}

		if (isset($data['thumbnail'])) {
			$thumbnail = new Thumbnail();
			$thumbnail->random_id = $this->random_id;
			
			if (isset($data['thumbnail'])) {
				$thumbnail->retrieve_image($data['thumbnail'], 'jpg');
			}
			
			if (isset($data['thumbnail_webm'])) {
				$thumbnail->retrieve_image($data['thumbnail_webm'], 'webm');
			}
			
			if ($thumbnail->insert()) {
				$this->thumbnail_id = $thumbnail->id;
			}
		}

		if (isset($data['webm']) or isset($data['mp4'])) {
			$video = new Video();
			$video->random_id = $this->random_id;

			if (isset($data['webm'])) {
				$video->retrieve_video($data['webm'], 'webm');
			}

			if (isset($data['mp4'])) {
				$video->retrieve_video($data['mp4'], 'mp4');
			}
			
			if ($video->insert()) {
				$this->video_id = $video->id;
			}
		}

		if (isset($data['image'])) {
			$image = new Image();
			$image->random_id = $this->random_id;

			if ($image->retrieve_image($data['image'])) {
				if ($image->insert()) {
					$this->image_id = $image->id;
				}
			}
		}
	}

	function generate_thumbnail() {
	}

	/*
	function render_thumbnail() {
		$extra = '';
		if ($this->animated)
			{
			$extra .= ' data-animated="'.$this->animated_src().'"';
			}

		if ($this->doublons !== null)
			{
			$extra .= ' data-bloubs="'.(int)$this->doublons.'"';
			}

		$src = $this->thumbnail_src ? url(PICTURES_PREFIX.'/'.$this->thumbnail_src, true) : "http://img.sauf.ca/blank.png";
		$href = '+'.$this->id;
		$html = <<<HTML
			<span class="thumbnail"><a id="thumbnail-{$this->id}" href="{$this->target_url()}" class="thumbnail-link picture"
					data-id="{$this->id}"
					data-unique-id="{$this->unique_id}"
					data-media="{($this->animated ? 'animated' : 'image')}"
					data-title="{$this->attr($this->title)}"
					data-url="{$this->attr($this->url)}"
					data-tags="{$this->attr(implode(', ', explode("\n", trim($this->tags))))}"
					data-date="{$this->date}"
					data-user-name="{$this->attr($this->user)}"
					data-tribune-name="{$this->attr($this->tribune_name)}"
					data-tribune-url="{$this->attr($this->tribune_url)}"
					data-post-id="{$this->attr($this->post_id)}"
					data-md5="{$this->attr($this->md5)}"
					data-type="{$this->attr($this->type)}"
					data-src="{url(PICTURES_PREFIX.'/'.$this->src, true)}"
					$extra.
					'>'.
				'<img height="'.THUMBNAIL_SIZE.'" width="'.THUMBNAIL_SIZE.'" src="'.$src.'" alt="" />'.
			'</a></span>
HTML;

		return $html;
	}
	*/

	function attr($string) {
		return htmlspecialchars($string);
	}

	function target_url() {
		if ($this->video_id) {
			return $this->video()->url();
		} else if ($this->image_id && !$this->screenshot_id) {
			return $this->image()->url();
		} else {
			return $this->url;
		}

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
}
