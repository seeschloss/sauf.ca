<?php

class URL {
	public $url = "";
	public $headers = array();
	public $code;
	public $length = -1;
	public $encoding;

	public $data;

	function __construct($url) {
		$this->url = $url;
	}

	function headers() {
		$details = parse_url($this->url);

		$c = curl_init();

		if (strpos($details['host'], 'ecx.images-amazon') !== FALSE) {
			$nobody = false;
		} else {
			$nobody = true;
		}

		curl_setopt($c, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Linux x86_64; rv:29.0) Gecko/20100101 Firefox/29.0");
		curl_setopt($c, CURLOPT_REFERER, $this->referer());
		curl_setopt($c, CURLOPT_URL, $this->url);
		curl_setopt($c, CURLOPT_HEADER, true);
		curl_setopt($c, CURLOPT_NOBODY, $nobody);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($c, CURLOPT_TIMEOUT, 15);
		curl_setopt($c, CURLOPT_AUTOREFERER, true);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($c, CURLOPT_MAXREDIRS, 5);

		if (!$a = curl_exec($c)) {
			$this->error = curl_error($c);
			return false;
		}

		$this->code = curl_getinfo($c, CURLINFO_HTTP_CODE);
		$this->length = curl_getinfo($c, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
		$this->content_type = curl_getinfo($c, CURLINFO_CONTENT_TYPE);

		foreach (explode("\n", $a) as $line) {
			@list($key, $value) = explode(":", $line, 2);
			$key = strtolower(trim($key));
			$value = trim($value);
			if ($key) {
				$this->headers[$key] = $value;
			}
		}

		return $this->headers;
	}

	function referer() {
		if (empty($this->referer)) {
			$details = parse_url($this->url);
			$this->referer = $details['scheme'].'://'.$details['host'];
		}

		return $this->referer;
	}

	function body() {
		$c = curl_init();

		curl_setopt($c, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Linux x86_64; rv:29.0) Gecko/20100101 Firefox/29.0");
		curl_setopt($c, CURLOPT_REFERER, $this->referer());
		curl_setopt($c, CURLOPT_URL, $this->url);
		curl_setopt($c, CURLOPT_HEADER, false);
		curl_setopt($c, CURLOPT_NOBODY, false);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($c, CURLOPT_TIMEOUT, 15);
		curl_setopt($c, CURLOPT_AUTOREFERER, true);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($c, CURLOPT_MAXREDIRS, 5);

		if (!$this->data = curl_exec($c)) {
			$this->error = curl_error($c);
			return false;
		}

		$this->code = curl_getinfo($c, CURLINFO_HTTP_CODE);
		$this->length = curl_getinfo($c, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
		$this->content_type = curl_getinfo($c, CURLINFO_CONTENT_TYPE);

		return $this->data;
	}

	function is_image() {
		if (!isset($this->content_type)) {
			$this->headers();
		}

		list($type) = explode(';', $this->content_type);
		$content_types = array(
			'image/gif' => 'gif',
			'image/jpeg' => 'jpg',
			'image/jpg' => 'jpg',
			'image/png' => 'png',
			'video/webm' => 'webm',
		);
		return isset($content_types[$type]);
	}

	function is_html() {
		if (!isset($this->content_type)) {
			$this->headers();
		}

		list($type) = explode(';', $this->content_type);
		$content_types = array(
			'text/html' => 'html',
			'application/xml' => 'xml',
			'application/xhtml+xml' => 'xhtml',
		);
		return isset($content_types[$type]);
	}

	function encoding() {
		if (!empty($this->encoding)) {
			return $this->encoding;
		}

		if (!$this->is_html()) {
			$this->encoding = 'binary';
			return $this->encoding;
		}

		// For HTML documents, the charset might be overridden in several ways
		if (!$this->data) {
			$this->body();
		}

		if (preg_match("/<meta[^>]*charset=(?<quote>['\"])(?<charset>.+?)\k<quote>/i", $this->data, $matches)) {
			if (!empty($matches['charset'])) {
				$this->encoding = $matches['charset'];
			}
		} else if (preg_match("/<meta[^>]*http-equiv=(?<quote>['\"])content-type\k<quote>[^>]content=\k<quote>(?<content_type>.+?)\k<quote>/i", $this->data, $matches)) {
			if (!empty($matches['content_type'])) {
				$this->content_type = $matches['content_type'];
			}
		}

		$parts = explode(';', $this->content_type);
		foreach ($parts as $part) {
			if (strpos(strtolower(trim($part)), 'charset=') === 0) {
				$this->encoding = substr(trim($part), 8);
			}
		}

		if (empty($this->encoding)) {
			$this->encoding = mb_detect_encoding($this->data);
		}

		return $this->encoding;
	}

	function og_tags() {
		if (!$this->is_html()) {
			return array();
		}

		mb_detect_order(array('UTF-8', 'ISO-8859-15'));
		$dom = new DOMDocument('1.0', 'UTF-8');

		if ($this->encoding()) {
			$data = iconv($this->encoding(), 'UTF-8', $this->data);
		} else {
			$data = $this->data;
		}

		// Say no to libxml error flooding
		$dom->encoding = 'UTF-8';
		@$dom->loadHTML($data, LIBXML_NOERROR);
		$dom->encoding = 'UTF-8';

		$titles = $dom->getElementsByTagName('title');
		foreach ($titles as $title) {
			var_dump($title);
			if ($encoding = mb_detect_encoding($title->textContent)) {
				$this->title = iconv($encoding, 'UTF-8', $title->textContent);
				}
			else {
				$this->title = $title->textContent;
				}
			}

		require_once __DIR__ . '/../lib/Opengraph/src/Opengraph/Meta.php';
		require_once __DIR__ . '/../lib/Opengraph/src/Opengraph/Opengraph.php';
		require_once __DIR__ . '/../lib/Opengraph/src/Opengraph/Reader.php';

		$reader = new Opengraph\Reader();
		$reader->parse($data);
		return $reader;
	}

	function tags() {
		$og = $this->og_tags();

		if (!$og) {
			return array();
		}

		$tags = array();
		foreach ($og->getMetas() as $meta) {
			if ($meta->getProperty() == 'og:video:tag') {
				$tags[] = $meta->getContent();
			}
		}

		return $tags;
	}
}
