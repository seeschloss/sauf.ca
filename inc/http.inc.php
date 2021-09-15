<?php

class HTTP {
	public $url = "";

	public $data = "";
	public $error = "";
	public $code = 0;
	public $length = 0;
	public $content_type = "";

	public function __construct($url) {
		$this->url = $url;
	}

	public function get($opts = []) {
		$c = curl_init();

		curl_setopt($c, CURLOPT_USERAGENT, "fprin.tf Mozilla/5.0 (X11; Linux x86_64; rv:29.0) Gecko/20100101 Firefox/29.0");
		curl_setopt($c, CURLOPT_URL, $this->url);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($c, CURLOPT_TIMEOUT, 15);
		curl_setopt($c, CURLOPT_AUTOREFERER, true);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($c, CURLOPT_MAXREDIRS, 5);

		foreach ($opts as $opt_key => $opt_value) {
			curl_setopt($c, $opt_key, $opt_value);
		}

		if (!$this->data = curl_exec($c)) {
			$this->error = curl_error($c);
			return false;
		}

		$this->code = curl_getinfo($c, CURLINFO_HTTP_CODE);
		$this->length = curl_getinfo($c, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
		$this->content_type = curl_getinfo($c, CURLINFO_CONTENT_TYPE);

		return $this->data;
	}
}
