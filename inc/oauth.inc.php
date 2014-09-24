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
			$this->store_token_in_cookie($data->access_token, $data->refresh_token, time() + $data->expires_in);
			return true;
			}
		else
			{
			return false;
			}
		}

	function store_token_in_cookie($token, $refresh, $expire)
		{
		setcookie('dlfp_token', $token . ':' . $refresh, $expire);
		}

	function token_info()
		{
		if (isset($_COOKIE['dlfp_token']))
			{
			list($token, $refresh) = explode(':', $_COOKIE['dlfp_token']);

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
		if (isset($_COOKIE['dlfp_token']))
			{
			list($token, $refresh) = explode(':', $_COOKIE['dlfp_token']);

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
		if (isset($_COOKIE['dlfp_token']))
			{
			list($token, $refresh) = explode(':', $_COOKIE['dlfp_token']);

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

			if ($answer and $data = json_decode($answer))
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

		return $posts;
		}
	}
