<?php
class Picture
	{
	public $user = "";
	public $id = 0;
	public $path = null;
	public $thumbnail_path = null;
	public $src = null;
	public $thumbnail_src = null;
	public $name = "";
	public $date = 0;
	public $title = "";
	public $url = "";
	public $tribune_id = 0;
	public $tribune_name = "";
	public $post_id = 0;
	public $tags = "";
	public $raw_tags = "";
	public $animated = 0;
	public $type = '';
	public $md5 = '';
	public $doublons = null;
	public $unique_id = 0;

	public $new = false;

	function __construct()
		{
		}

	static function acceptable($content_type)
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

	function post_time() {
		return date('YmdHis', $this->date);
	}

	function post_clock() {
		return date('H:i:s', $this->date);
	}

	function xml_element() {
		$message = $this->post_clock().
			"@".$this->tribune_name.
			" <a href=\"".htmlspecialchars(url(PICTURES_PREFIX.'/'.$this->src, false))."\">[".$this->type."]</a> - ".
			" <a href=\"".htmlspecialchars($this->url)."\">[url]</a> <i>".htmlspecialchars($this->tags)."</i>";
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
		$array = array(
			$this->unique_id,
			$this->post_time(),
			"",
			$this->user,
			$this->post_clock().
				'@'.$this->tribune_name.
				" <a href=\"".url(PICTURES_PREFIX.'/'.$this->src, false)."\">[".$this->type."]</a> - ".
				' <a href="'.$this->url.'">[url]</a> <i>'.$this->tags.'</i>',
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

		$query = 'SELECT p.*, u.id as unique_id, t.name as tribune_name, t.url as tribune_url
			FROM pictures AS p
			LEFT JOIN tribunes AS t
			  ON p.tribune_id = t.id
			LEFT JOIN unique_ids AS u
			  ON p.id = u.picture_id
			WHERE p.post_id = '.(int)$post_id
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

		$query = 'SELECT p.*, u.id as unique_id, t.name as tribune_name, t.url as tribune_url
			FROM pictures AS p
			LEFT JOIN tribunes AS t
			  ON p.tribune_id = t.id
			LEFT JOIN unique_ids AS u
			  ON p.id = u.picture_id
			WHERE p.id = '.(int)$id
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

	function animated_path()
		{
		switch ($this->type)
			{
			case 'image/gif':
				return $this->thumbnail_path.'.animated.gif';
			case 'video/webm':
				return $this->thumbnail_path.'.animated.webm';
			default:
				return '';
			}
		}

	function animated_src()
		{
		switch ($this->type)
			{
			case 'image/gif':
				return url(PICTURES_PREFIX.'/'.$this->thumbnail_src.'.animated.gif', true);
			case 'video/webm':
				return url(PICTURES_PREFIX.'/'.$this->thumbnail_src, true).'.animated.webm';
			default:
				return '';
			}
		}

	function json_array()
		{
		$array = array
			(
			'id' => $this->id,
			'unique-id' => $this->unique_id,
			'media' => $this->animated ? 'animated' : 'image',
			'title' => $this->title,
			'url' => $this->url,
			'user' => $this->user,
			'tags' => explode("\n", trim($this->tags)),
			'date' => $this->date,
			'tribune-name' => $this->tribune_name,
			'tribune-url' => $this->tribune_url,
			'post-id' => $this->post_id,
			'src' => url(PICTURES_PREFIX.'/'.$this->src, true),
			'thumbnail-src' => $this->thumbnail_src ? url(PICTURES_PREFIX.'/'.$this->thumbnail_src, true) : "",
			'animated' => '',
			'md5' => $this->md5,
			'bloubs' => $this->doublons,
			'type' => $this->type,
			);

		if ($this->animated)
			{
			$array['animated'] = $this->animated_src();
			}

		return $array;
		}

	function bloubs()
		{
		$db = new DB();
		$query = "SELECT COUNT(*)
			FROM pictures AS p
			WHERE p.md5 = '".$db->escape($this->md5)."'"
			;
		$bloubs = (int)$db->value($query);

		$query = "UPDATE pictures AS p
			SET doublons = ".(int)$bloubs."
			WHERE p.md5 = '".$db->escape($this->md5)."'"
			;
		$db->query($query);

		return $bloubs;
		}

	function write($data)
		{
		Logger::notice('Writing picture, data '.strlen($data).' bytes long');
		$finfo = new Finfo(FILEINFO_MIME);
		@list($mime, $charset) = explode(';', $finfo->buffer($data));
		Logger::notice('Mimetype is '.$mime);
		switch ($mime)
			{
			case 'video/webm':
				$extension = 'webm';
				break;
			case 'image/jpeg':
				$extension = 'jpg';
				break;
			case 'image/gif':
				$extension = 'gif';
				break;
			case 'image/png':
				$extension = 'png';
				break;
			default:
				Logger::warning("Mime-type not acceptable: ".$mime);
				return false;
			}
		$this->type = $mime;

		$this->md5 = substr(hash('sha256', $data), 0, 32);

		Logger::notice('MD5 is '.$this->md5);

		if (empty($this->path))
			{
			$dir = date('Y-m-d', $this->date);
			$full_dir = UPLOAD_DIR.'/'.$dir;
			if (!file_exists($full_dir))
				{
				mkdir($full_dir);
				}
			$this->path = $full_dir.'/'.$this->md5.'.'.$extension;
			$this->thumbnail_path = $full_dir.'/t'.$this->md5.'.'.$extension;

			$this->src = $dir.'/'.$this->md5.'.'.$extension;
			$this->thumbnail_src = $dir.'/t'.$this->md5.'.'.$extension;
			}

		$f = fopen($this->path, 'w');
		if (!$f)
			{
			echo 'Unable to open the file for writing.';
			return false;
			}

		$length = strlen($data);

		if (fwrite($f, $data, $length) === false)
			{
			echo 'Unable to save the file.';
			return false;
			}

		fclose($f);

		$this->generate_thumbnail();

		return true;
		}

	function delete()
		{
		if ($this->id)
			{
			$db = new DB();
			$query = 'DELETE FROM pictures
			WHERE id = '.(int)$this->id.'
			';
			$db->query($query);
			}
		}

	function insert()
		{
		$db = new DB();

		$db->insert('pictures', [
			'path' => "'".$db->escape($this->path)."'",
			'thumbnail_path' => "'".$db->escape($this->thumbnail_path)."'",
			'src' => "'".$db->escape($this->src)."'",
			'thumbnail_src' => "'".$db->escape($this->thumbnail_src)."'",
			'name' => "'".$db->escape($this->name)."'",
			'title' => "'".$db->escape($this->title)."'",
			'url' => "'".$db->escape($this->url)."'",
			'date' => "'".$db->escape($this->date)."'",
			'tribune_id' => "'".$db->escape($this->tribune_id)."'",
			'post_id' => "'".$db->escape($this->post_id)."'",
			'tags' => "'".$db->escape($this->tags)."'",
			'raw_tags' => "'".$db->escape($this->raw_tags)."'",
			'user' => "'".$db->escape($this->user)."'",
			'type' => "'".$db->escape($this->type)."'",
			'md5' => "'".$db->escape($this->md5)."'",
			'animated' => (int)$this->animated
		]);

		if ($this->id = $db->insert_id())
			{
			$db->query('INSERT INTO unique_ids (picture_id) VALUES ('.$this->id.')');
			$this->unique_id = $db->insert_id();
			}
		else
			{
			Logger::error('Could not insert picture, query was: '.$query);
			}


		return $this->id;
		}

	function update()
		{
		if ($this->id and $this->path and $this->thumbnail_path and $this->src and $this->thumbnail_src and $this->name and $this->title and $this->url and $this->date and $this->tribune_id)
			{
			$db = new DB();

			$query = 'UPDATE pictures SET
				path = \''.$db->escape($this->path).'\',
				thumbnail_path = \''.$db->escape($this->thumbnail_path).'\',
				src = \''.$db->escape($this->src).'\',
				thumbnail_src = \''.$db->escape($this->thumbnail_src).'\',
				name = \''.$db->escape($this->name).'\',
				title = \''.$db->escape($this->title).'\',
				url = \''.$db->escape($this->url).'\',
				date = \''.$db->escape($this->date).'\',
				tribune_id = \''.$db->escape($this->tribune_id).'\',
				post_id = \''.$db->escape($this->post_id).'\',
				tags = \''.$db->escape($this->tags).'\',
				raw_tags = \''.$db->escape($this->raw_tags).'\',
				user = \''.$db->escape($this->user).'\',
				type = \''.$db->escape($this->type).'\',
				md5 = \''.$db->escape($this->md5).'\',
				animated = '.(int)$this->animated.'
				WHERE id = '.(int)$this->id
				;

			$db->query($query);

			return true;
			}

		return false;
		}

	function find_tags_imagga()
		{
		$url = 'https://api.imagga.com/v2/tags?image_url='.url(PICTURES_PREFIX.'/'.$this->src, false);

		$c = curl_init();

		curl_setopt($c, CURLOPT_USERAGENT, "Sauf.ca");
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_HTTPHEADER, array(
			'Authorization: Basic '.$GLOBALS['config']['autotagging']['imagga-auth'],
		));
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($c, CURLOPT_TIMEOUT, 15);

		Logger::notice('Asking imagga for tags: '.$url);

		$a = curl_exec($c);
		$data = json_decode($a, true);
		$tags = array();
		if (is_array($data) and isset($data['result']['tags']))
			{
			foreach ($data['result']['tags'] as $tag)
				{
				$tags[] = array(
					'tag' => $tag['tag']['en'],
					'confidence' => $tag['confidence'] / 100,
				);
				}
			}

		return $tags;
		}

	function find_nsfw_imagga()
		{
		$url = 'https://api.imagga.com/v2/categories/nsfw_beta?image_url='.url(PICTURES_PREFIX.'/'.$this->src, false);

		$c = curl_init();

		curl_setopt($c, CURLOPT_USERAGENT, "Sauf.ca");
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_HTTPHEADER, array(
			'Authorization: Basic '.$GLOBALS['config']['autotagging']['imagga-auth'],
		));
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($c, CURLOPT_TIMEOUT, 15);

		Logger::notice('Asking imagga for tags: '.$url);

		$a = curl_exec($c);
		$data = json_decode($a, true);
		$tags = array();
		if (is_array($data) and isset($data['result']['categories']))
			{
			foreach ($data['result']['categories'] as $category)
				{
				if ($category['name']['en'] == 'nsfw')
					{
					$tags[] = array(
						'tag' => 'nsfw',
						'confidence' => $category['confidence'] / 100,
					);
					}
				}
			}

		return $tags;
		}

	function find_tags_clarifai()
		{
		$src = $this->src;
		if ($this->type == 'video/webm')
			{
			$path = $this->thumbnail_src;
			}

		$url = 'https://api.clarifai.com/v1/tag?model=nsfw-v1.0&url='.url(PICTURES_PREFIX.'/'.$src, false);

		$c = curl_init();

		curl_setopt($c, CURLOPT_USERAGENT, "Sauf.ca");
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_HTTPHEADER, array(
			'Authorization: Bearer '.$GLOBALS['config']['autotagging']['clarifai-token'],
		));
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($c, CURLOPT_TIMEOUT, 15);

		Logger::notice('Asking clarifai for tags: '.$url);

		$a = curl_exec($c);
		$data = json_decode($a, true);
		$tags = array
			(
			'nsfw' => array(
				'tag' => 'nsfw',
				'confidence' => NULL,
				),
			'sfw' => array(
				'tag' => 'sfw',
				'confidence' => 1,
				),
			);

		if (is_array($data) and isset($data['results'][0]['result']['tag']))
			{
			if (isset($data['results'][0]['result']['tag']['timestamps']))
				{
				foreach ($data['results'][0]['result']['tag']['classes'] as $frame => $classes)
					{
					foreach ($data['results'][0]['result']['tag']['classes'][$frame] as $i => $class)
						{
						switch ($class)
							{
							case 'nsfw':
								$tags['nsfw']['confidence'] = max(
									$tags['nsfw']['confidence'],
									$data['results'][0]['result']['tag']['probs'][$frame][$i]
								);
								break;
							case 'sfw':
								$tags['sfw']['confidence'] = min(
									$tags['sfw']['confidence'],
									$data['results'][0]['result']['tag']['probs'][$frame][$i]
								);
								break;
							}
						}
					}
				}
			else
				{
				foreach ($data['results'][0]['result']['tag']['classes'] as $i => $class)
					{
					$tags[$class] = array
						(
						'tag' => $class,
						'confidence' => $data['results'][0]['result']['tag']['probs'][$i],
						);
					}
				}
			}

		return array_values($tags);
		}

	function find_tags_deepai()
		{
		$src = $this->src;
		if ($this->type == 'video/webm')
			{
			$path = $this->thumbnail_src;
			}

		$url = 'https://api.deepai.org/api/nsfw-detector';

		$c = curl_init();

		curl_setopt($c, CURLOPT_USERAGENT, "Sauf.ca");
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_HTTPHEADER, array(
			'Api-Key: '.$GLOBALS['config']['autotagging']['deepai-token'],
		));
		curl_setopt($c, CURLOPT_POSTFIELDS, array(
			'image' => url(PICTURES_PREFIX.'/'.$src, false),
		));
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($c, CURLOPT_TIMEOUT, 15);

		Logger::notice('Asking deepai for NSFW: '.$url);

		$a = curl_exec($c);
		$data = json_decode($a, true);

		$tags = array();

		if (isset($data['output']['nsfw_score'])) {
			$tags[] = array(
				'tag' => 'nsfw',
				'confidence' => $data['output']['nsfw_score'],
			);
		}

		return array_values($tags);
		}

	function find_tags()
		{
		$tags = [];

		if (isset($GLOBALS['config']['autotagging']['imagga-auth']))
			{
			$tags = array_merge($tags, $this->find_tags_imagga());
			$tags = array_merge($tags, $this->find_nsfw_imagga());
			}

		if (isset($GLOBALS['config']['autotagging']['clarifai-token']))
			{
			//$tags = array_merge($tags, $this->find_tags_clarifai());
			}

		if (isset($GLOBALS['config']['autotagging']['deepai-token']))
			{
			//$tags = array_merge($tags, $this->find_tags_deepai());
			}

		return json_encode($tags);

		if ($this->type == 'video/webm')
			{
			$path = $this->thumbnail_path;
			}
		else
			{
			$path = $this->path;
			}
		$tags = `curl --user-agent "Mozilla/5.0 (Windows NT 6.1; rv:6.0.2) Gecko/20100101 Firefox/6.0.2" --silent -XPOST -F numberOfKeywords=5 -F "File=@$path" "http://viscomp1.f4.htw-berlin.de/tomcat/akiwi/AkiwiServlet?ajax=1.4" --referer "http://viscomp1.f4.htw-berlin.de/tomcat/akiwi/AkiwiServlet" | jshon -e keywords -a -e word -u`;

		$tags = implode(' ', explode("\n", $tags));

		return $tags;
		}

	function init_tags()
		{
		$tags = array();

		$data = json_decode($this->raw_tags, true);
		if (is_array($data))
			{
			foreach ($data as $tag)
				{
				if ($tag['tag'] == 'nsfw')
					{
					if ($tag['confidence'] > 0.8)
						{
						$tags[] = 'nsfw';
						}
					}
				else if ($tag['confidence'] > 0.2)
					{
					$tags[] = $tag['tag'];
					}
				}
			}

		$this->tags = implode(', ', $tags);
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
		return
			'<span class="thumbnail"><a id="thumbnail-'.$this->id.'" href="'.$href.'" class="thumbnail-link picture" '.
					' data-id="'.$this->id.'"'.
					' data-unique-id="'.$this->unique_id.'"'.
					' data-media="'.($this->animated ? 'animated' : 'image').'"'.
					' data-title="'.htmlspecialchars($this->title).'"'.
					' data-url="'.htmlspecialchars($this->url).'"'.
					' data-tags="'.htmlspecialchars(implode(', ', explode("\n", trim($this->tags)))).'"'.
					' data-date="'.$this->date.'"'.
					' data-user-name="'.htmlspecialchars($this->user).'"'.
					' data-tribune-name="'.htmlspecialchars($this->tribune_name).'"'.
					' data-tribune-url="'.htmlspecialchars($this->tribune_url).'"'.
					' data-post-id="'.htmlspecialchars($this->post_id).'"'.
					' data-md5="'.htmlspecialchars($this->md5).'"'.
					' data-type="'.htmlspecialchars($this->type).'"'.
					' data-src="'.url(PICTURES_PREFIX.'/'.$this->src, true).'"'.
					$extra.
					'>'.
				'<img height="'.THUMBNAIL_SIZE.'" width="'.THUMBNAIL_SIZE.'" src="'.$src.'" alt="" />'.
			'</a></span>';
		}

	function generate_thumbnail()
		{
		$theight = THUMBNAIL_SIZE;
		$twidth = THUMBNAIL_SIZE;

		if ($this->type == 'video/webm')
			{
			$this->animated = true;

			$dir = date('Y-m-d', $this->date);
			$full_dir = UPLOAD_DIR.'/'.$dir;

			$this->thumbnail_path = $full_dir.'/t'.$this->md5.'.jpg';
			$this->thumbnail_src = $dir.'/t'.$this->md5.'.jpg';

			$animated_path = $this->animated_path();
			$tmp = tempnam('/tmp', 'sauf_');
			unlink($tmp);
			$tmp = $tmp.'.jpg';
			`ffmpeg -i "{$this->path}" -frames 1 "{$tmp}" &>/dev/null`;
			@unlink($animated_path);
			//`ffmpeg -i "{$this->path}" -vf cropdetect=24:10:0,scale="'if(gt(a,1),-1,100)':'if(gt(a,1),100,-1)',crop=100:100" "{$animated_path}" &>/dev/null`;
			`ffmpeg -i "{$this->path}" -vf scale="'if(gt(a,1),-1,100)':'if(gt(a,1),100,-1)',crop=100:100" -aspect 1 "{$animated_path}" &>/dev/null`;

			list($width_orig, $height_orig, $image_type) = getimagesize($tmp);
			$im = imagecreatefromjpeg($tmp);

			$tmp_img = imagecreatetruecolor(1, 1);
			imagecopyresampled($tmp_img, $im, 0, 0, 0, 0, 1, 1, 0, 0);
			$rgb = imagecolorat($tmp_img, 0, 0);

			if ($rgb == 0)
				{
				$length = `ffprobe -i {$this->path} -show_format 2>/dev/null | grep duration | sed 's/duration=//'`;

				if ((int)$length > 0) {
					$skip = str_pad(min(10, (int)$length/10), 2, '0', STR_PAD_LEFT);
				} else {
					$skip = "00";

					fprintf(STDERR, "Bad length: '$length' for file '{$this->path}'.\n");
				}

				unlink($tmp);
				`ffmpeg -i "{$this->path}" -ss '00:00:{$skip}' -frames 1 "{$tmp}" &>/dev/null`;
				list($width_orig, $height_orig, $image_type) = getimagesize($tmp);
				$im = imagecreatefromjpeg($tmp);
				}

			unlink($tmp);
			}
		else
			{
			list($width_orig, $height_orig, $image_type) = getimagesize($this->path);

			switch ($this->type)
				{
				case 'image/gif':  $im = imagecreatefromgif($this->path); break;
				case 'image/jpeg': $im = imagecreatefromjpeg($this->path); break;
				case 'image/png':  $im = imagecreatefrompng($this->path); break;
				}
			}

		if ($im)
			{
			$corig = min($width_orig, $height_orig);

			$lmarge = ($width_orig  - $corig) / 2;
			$hmarge = ($height_orig - $corig) / 2;

			$image_thumbnail = imagecreatetruecolor($twidth, $theight);

			imagecopyresampled($image_thumbnail, $im, 0, 0, $lmarge, $hmarge, $twidth, $theight, $corig, $corig);

			switch ($this->type)
				{
				case 'image/gif':
					$this->animated_gif_thumbnail($this->path, $this->animated_path());
					@unlink($this->thumbnail_path);
					imagepng($image_thumbnail, $this->thumbnail_path, 9);
					break;
				case 'image/png':
					@unlink($this->thumbnail_path);
					imagepng($image_thumbnail, $this->thumbnail_path, 9);
					break;
				case 'video/webm':
				case 'image/jpeg':
					$image_thumbnail = UnsharpMask($image_thumbnail, 80, 0.5, 3);
					@unlink($this->thumbnail_path);
					imagejpeg($image_thumbnail, $this->thumbnail_path, 99);
					break;
				}
			}
		}

	function animated_gif_thumbnail($original_path, $destination_path)
		{
		if (file_exists($destination_path))
			{
			return false;
			}

		$theight = THUMBNAIL_SIZE;
		$twidth = THUMBNAIL_SIZE;

		list($width_orig, $height_orig, $image_type) = getimagesize($original_path);

		$corig = min($width_orig, $height_orig);

		$lmarge = ($width_orig  - $corig) / 2;
		$hmarge = ($height_orig - $corig) / 2;

		try
			{
			$image = new Imagick($original_path);
			$image = $image->coalesceImages();
			}
		catch (Exception $e)
			{
			return false;
			}

		if ($image->getNumberImages() <= 1)
			{
			return false;
			}

		foreach ($image as $frame)
			{
			$frame->cropThumbnailImage($twidth, $theight);
			$frame->setImagePage($twidth, $theight, 0, 0);
			}

		$image = $image->deconstructImages();
		$image->writeImages($destination_path, true); 

		$image->clear();

		$this->animated = true;

		return true;
		}

	function dimensions()
		{
		if ($this->type == 'video/webm')
			{
			$output = `ffprobe -v error -show_entries stream=width,height -of default=noprint_wrappers=1:nokey=1 "{$this->path}"`;
			list($w, $h) = explode("\n", $output);
			return [$w, $h];
			}
		else if (file_exists($this->path))
			{
			list($width_orig, $height_orig, $image_type) = getimagesize($this->path);

			return [$width_orig, $height_orig];
			}
		else
			{
			return [0, 0];
			}
		}
	}

function UnsharpMask($img, $amount, $radius, $threshold)    {

	////////////////////////////////////////////////////////////////////////////////////////////////
	////
	////                  Unsharp Mask for PHP - version 2.0
	////
	////    Unsharp mask algorithm by Torstein Hønsi 2003-06.
	////             thoensi_at_netcom_dot_no.
	////               Please leave this notice.
	////
	///////////////////////////////////////////////////////////////////////////////////////////////


	// $img is an image that is already created within php using
	// imgcreatetruecolor. No url! $img must be a truecolor image.

	// Attempt to calibrate the parameters to Photoshop:
	if ($amount > 500)    $amount = 500;
	$amount = $amount * 0.016;
	if ($radius > 50)    $radius = 50;
	$radius = $radius * 2;
	if ($threshold > 255)    $threshold = 255;

	$radius = abs(round($radius));     // Only integers make sense.
	if ($radius == 0) return $img;
	$w = imagesx($img); $h = imagesy($img);
	$imgCanvas = imagecreatetruecolor($w, $h);
	$imgCanvas2 = imagecreatetruecolor($w, $h);
	$imgBlur = imagecreatetruecolor($w, $h);
	$imgBlur2 = imagecreatetruecolor($w, $h);
	imagecopy ($imgCanvas, $img, 0, 0, 0, 0, $w, $h);
	imagecopy ($imgCanvas2, $img, 0, 0, 0, 0, $w, $h);


	// Gaussian blur matrix:
	//
	//    1    2    1
	//    2    4    2
	//    1    2    1
	//
	//////////////////////////////////////////////////

	imagecopy      ($imgBlur, $imgCanvas, 0, 0, 0, 0, $w, $h); // background


	for ($i = 0; $i < $radius; $i++)    {

		if (function_exists('imageconvolution')) { // PHP >= 5.1
			$matrix = array(
					array( 1, 2, 1 ),
					array( 2, 4, 2 ),
					array( 1, 2, 1 )
					);
			imageconvolution($imgCanvas, $matrix, 16, 0);

		} else {

			// Move copies of the image around one pixel at the time and merge them with weight
			// according to the matrix. The same matrix is simply repeated for higher radii.

			imagecopy      ($imgBlur, $imgCanvas, 0, 0, 1, 1, $w - 1, $h - 1); // up left
			imagecopymerge ($imgBlur, $imgCanvas, 1, 1, 0, 0, $w, $h, 50); // down right
			imagecopymerge ($imgBlur, $imgCanvas, 0, 1, 1, 0, $w - 1, $h, 33.33333); // down left
			imagecopymerge ($imgBlur, $imgCanvas, 1, 0, 0, 1, $w, $h - 1, 25); // up right

			imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 1, 0, $w - 1, $h, 33.33333); // left
			imagecopymerge ($imgBlur, $imgCanvas, 1, 0, 0, 0, $w, $h, 25); // right
			imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 20 ); // up
			imagecopymerge ($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 16.666667); // down

			imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 0, 0, $w, $h, 50); // center
			imagecopy ($imgCanvas, $imgBlur, 0, 0, 0, 0, $w, $h);

			// During the loop above the blurred copy darkens, possibly due to a roundoff
			// error. Therefore the sharp picture has to go through the same loop to
			// produce a similar image for comparison. This is not a good thing, as processing
			// time increases heavily.
			imagecopy ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h);
			imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 50);
			imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 33.33333);
			imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 25);
			imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 33.33333);
			imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 25);
			imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 20 );
			imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 16.666667);
			imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 50);
			imagecopy ($imgCanvas2, $imgBlur2, 0, 0, 0, 0, $w, $h);

		}
	}
	//return $imgBlur;

	// Calculate the difference between the blurred pixels and the original
	// and set the pixels
	for ($x = 0; $x < $w; $x++)    { // each row
		for ($y = 0; $y < $h; $y++)    { // each pixel

			$rgbOrig = ImageColorAt($imgCanvas2, $x, $y);
			$rOrig = (($rgbOrig >> 16) & 0xFF);
			$gOrig = (($rgbOrig >> 8) & 0xFF);
			$bOrig = ($rgbOrig & 0xFF);

			$rgbBlur = ImageColorAt($imgCanvas, $x, $y);

			$rBlur = (($rgbBlur >> 16) & 0xFF);
			$gBlur = (($rgbBlur >> 8) & 0xFF);
			$bBlur = ($rgbBlur & 0xFF);

			// When the masked pixels differ less from the original
			// than the threshold specifies, they are set to their original value.
			$rNew = (abs($rOrig - $rBlur) >= $threshold)
				? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig))
				: $rOrig;
			$gNew = (abs($gOrig - $gBlur) >= $threshold)
				? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig))
				: $gOrig;
			$bNew = (abs($bOrig - $bBlur) >= $threshold)
				? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig))
				: $bOrig;



			if (($rOrig != $rNew) || ($gOrig != $gNew) || ($bOrig != $bNew)) {
				$pixCol = ImageColorAllocate($img, $rNew, $gNew, $bNew);
				ImageSetPixel($img, $x, $y, $pixCol);
			}
		}
	}
	return $img;

}
