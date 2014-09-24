<?php

class OAuth
	{
	function init()
		{
		$url = $GLOBALS['config']['oauth']['dlfp']['server'].'/api/oauth/authorize'
			.'?client_id='.$GLOBALS['config']['oauth']['dlfp']['app_id']
			.'&redirect_uri=http://sauf.ca/oauth/dlfp/callback'
			.'&response_type=code'
			.'&scope=board+account';

		header("Location: ".$url);
		}

	function process()
		{
		if ($_GET['code'])
			{
			$code = $_GET['code'];

			$this->get_access_token($code);
			}
		}

	function get_access_token($code)
		{
		$url = $GLOBALS['config']['oauth']['dlfp']['server'].'/api/oauth/token';

		$c = curl_init();

		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_POST, true);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_POSTFIELDS, array(
				'client_id' => $GLOBALS['config']['oauth']['dlfp']['app_id'],
				'client_secret' => $GLOBALS['config']['oauth']['dlfp']['app_secret'],
				'code' => $code,
				'grant_type' => 'authorization_code',
				'redirect_uri' => 'http://sauf.ca/oauth/dlfp/callback',
			));
		$answer = curl_exec($c);
		if ($answer and $data = json_decode($answer))
			{
			$this->store_token($data->access_token, $data->refresh_token, time() + $data->expires_in);
			return true;
			}
		else
			{
			return false;
			}
		}

	function get_token()
		{
		if (isset($_COOKIE['dlfp_token']) and $_COOKIE['dlfp_token'])
			{
			$key = hash('sha256', $GLOBALS['config']['oauth']['dlfp']['secret'], TRUE);

			$plaintext = base64_decode($_COOKIE['dlfp_token']);
			$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);

			$iv = substr($plaintext, 0, $iv_size);
			$crypted = substr($plaintext, $iv_size + 1);

			$decrypted = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $crypted, MCRYPT_MODE_CBC, $iv));

			if ($value = json_decode($decrypted) and isset($value->token))
				{
				return $value->token;
				}
			}

		return '';
		}

	function store_token($token, $refresh, $expire)
		{
		$value = json_encode(array(
			'token' => $token,
			'refresh' => $refresh,
		));

		$key = hash('sha256', $GLOBALS['config']['oauth']['dlfp']['secret'], TRUE);
		$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC));
		$encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $value, MCRYPT_MODE_CBC, $iv);
		setcookie('dlfp_token', base64_encode($iv.':'.$encrypted), $expire, NULL, NULL, FALSE, TRUE);
		}

	function token_info()
		{
		if ($token = $this->get_token())
			{
			$url = $GLOBALS['config']['oauth']['dlfp']['server'].'/api/oauth/token/info';

			$c = curl_init();

			curl_setopt($c, CURLOPT_URL, $url);
			curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($c, CURLOPT_POST, false);
			curl_setopt($c, CURLOPT_HTTPHEADER, array(
				'Authorization: Bearer ' . $token,
			));
			curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
			$answer = curl_exec($c);

			if ($answer and $data = json_decode($answer))
				{
				return $data;
				}
			}

		return array();
		}

	function can_post()
		{
		$info = $this->token_info();

		return isset($info->scopes) and in_array('board', $info->scopes);
		}

	function user_info()
		{
		if ($token = $this->get_token())
			{
			$url = $GLOBALS['config']['oauth']['dlfp']['server'].'/api/v1/me';

			$c = curl_init();

			curl_setopt($c, CURLOPT_URL, $url);
			curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($c, CURLOPT_POST, false);
			curl_setopt($c, CURLOPT_HTTPHEADER, array(
				'Authorization: Bearer ' . $token,
			));
			curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
			$answer = curl_exec($c);

			if ($answer and $data = json_decode($answer))
				{
				return $data;
				}
			}

		return array();
		}

	function tribune_post($message)
		{
		if ($token = $this->get_token())
			{
			$url = $GLOBALS['config']['oauth']['dlfp']['server'].'/api/v1/board';

			$c = curl_init();

			curl_setopt($c, CURLOPT_URL, $url);
			curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($c, CURLOPT_POST, true);
			curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($c, CURLOPT_HTTPHEADER, array(
				'Authorization: Bearer ' . $token,
			));
			curl_setopt($c, CURLOPT_POSTFIELDS, array(
				'message' => $message,
			));
			$answer = curl_exec($c);

			if ($answer and $data = json_decode($answer) and $data->id > 0)
				{
				return $this->new_posts($data->id);
				}
			}

		return array();
		}

	function new_posts($from_id)
		{
		$posts = array();

		$url = $GLOBALS['config']['oauth']['dlfp']['server'].'/board/index.xml';

		$c = curl_init();

		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_POST, false);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		$answer = curl_exec($c);

		if ($answer)
			{
			$xml = new SimpleXMLElement($answer);

			header("Content-Type: text/plain");

			foreach ($xml->post as $post)
				{
				if ($post['id'] < $from_id)
					{
					continue;
					}

				$posts[] = array(
					'id' => (int)$post['id'],
					'time' => (string)$post['time'],
					'info' => (string)$post->info,
					'message' => (string)$post->message,
					'login' => (string)$post->login,
				);
				}
			}

		return array_reverse($posts);
		}
	}
