<?php

defined('PHPFOX') or exit('NO DICE!');
define('PHPFOX_SKIP_POST_PROTECTION', true);

class uLogin_Service_uLogin extends Phpfox_Service {
	/*
	 *
	 */
	private $_back = '';
	private $_error = '';

	/**
	 * @param $user_id
	 *
	 * @return bool
	 */
	public function uloginCheckUserId($user_id)
	{
		$current_user = Phpfox::getUserId();
		if (($current_user > 0) && ($user_id > 0) && ($current_user != $user_id))
		{
			$this->_error = 'Данный аккаунт привязан к другому пользователю. Вы не можете использовать этот аккаунт';
			return false;
		}
		return true;
	}

	/*
	 *
	 */
	public function ajaxDeleteUser($identity)
	{
		try
		{
			$this->database()->delete(Phpfox::getT('ulogin_user'), 'identity = \''.$this->database()->escape($identity).'\'');
			echo json_encode(array('answerType' => 'ok', 'identity' => $identity));
			exit;
		} catch (Exception $e)
		{
			echo json_encode(array('title' => "Ошибка при удалении аккаунта", 'msg' => "Exception: ".$e->getMessage(), 'answerType' => 'error'));
			exit;
		}
	}

	/*
	 *
	 */
	public function loginUser($u_user, $user_id)
	{
		Phpfox::getLib('session')->set('cache_user_id', $user_id);
		if ($Plugin = Phpfox_Plugin::get('user.service_auth_login__cookie_start'))
		{
			eval($Plugin);
		}
		$user_data = $this->database()->select('password, password_salt, user_name')->from(Phpfox::getT('user'))->where('user_id = '.$user_id)->execute('getRow');
		if (!isset($user_data['password']))
		{
			$this->_error = 'Unknown error';
			return false;
		}
		$passwordHash = Phpfox::getLib('hash')->setRandomHash(Phpfox::getLib('hash')->setHash($user_data['password'], $user_data['password_salt']));
		Phpfox::setCookie('user_id', $user_id, 0);
		Phpfox::setCookie('user_hash', $passwordHash, 0);
		$this->database()->update(Phpfox::getT('user'), array('last_login' => PHPFOX_TIME), 'user_id = '.$user_id);
		$this->database()->insert(Phpfox::getT('user_ip'), array('user_id' => $user_id, 'type_id' => 'login', 'ip_address' => Phpfox::getIp(), 'time_stamp' => PHPFOX_TIME));
		if ($Plugin = Phpfox_Plugin::get('user.service_auth_login__cookie_end'))
		{
			eval($Plugin);
		}
		$url = Phpfox::getLib('url');
		$user_url = $url->makeUrl($user_data['user_name']);
		if ($url->isUrl($user_url)) $url->send($user_url);
		else {
			if(Phpfox::getUserId()>0){
				$url->send($url->makeUrl('ulogin.account'));
			}
			else
				$url->send('');
		}

	}

	/*
	 *
	 */
	public function getError()
	{
		return $this->_error;
	}

	/*
	 *
	 */
	private function _getSalt($iTotal = 3)
	{
		$sSalt = '';
		for ($i = 0;$i < $iTotal;$i++)
		{
			$sSalt .= chr(rand(33, 91));
		}
		return $sSalt;
	}

	/*
	 *
	 */
	private function _uploadPhoto($url, $filename)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_NOBODY, 1);
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		$result = curl_exec($ch);
		if (!$result) return false;
		preg_match('/Content-Type: \w+(\/)(?<value>\w+)/', $result, $value);
		$ext = $value['value'] == 'jpeg' ? 'jpg' : $value['value'];
		$savepath = PHPFOX_DIR_FILE.'pic'.PHPFOX_DS.'user'.PHPFOX_DS.$filename.'.'.$ext;
		$from = fopen($url, 'rb');
		$to = fopen($savepath, "wb");
		$size = 0;
		if ($from && $to)
		{
			while (!feof($from))
			{
				$size += fwrite($to, fread($from, 1024 * 8), 1024 * 8);
			}
		}
		else
			return false;
		fclose($from);
		fclose($to);
		return $filename.'.'.$ext;
	}

	/*
	 *
	 */
	public static function uloginGetUserFromToken($token = false)
	{
		$response = false;
		if ($token)
		{
			$data = array('cms' => 'phpfox', 'version' => PhpFox::getVersion());
			$request = 'http://ulogin.ru/token.php?token='.$token.'&host='.$_SERVER['HTTP_HOST'].'&data='.base64_encode(json_encode($data));
			if (function_exists('curl_init'))
			{
				if (in_array('curl', get_loaded_extensions()))
				{
					$c = curl_init($request);
					curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
					$response = curl_exec($c);
					curl_close($c);
				}
				elseif (function_exists('file_get_contents') && ini_get('allow_url_fopen')) $response = file_get_contents($request);
			}
		}
		return $response;
	}

	/**
	 * Проверка пользовательских данных, полученных по токену
	 *
	 * @param $u_user - пользовательские данные
	 *
	 * @return bool
	 */
	public function uloginCheckTokenError($u_user)
	{
		if (!is_array($u_user))
		{
			$this->_error = 'Ошибка работы uLogin: Данные о пользователе содержат неверный
			 формат.';
			return false;
		}
		if (isset($u_user['error']))
		{
			$strpos = strpos($u_user['error'], 'host is not');
			if ($strpos)
			{
				$this->_error = 'Ошибка работы uLogin: адрес хоста не совпадает с оригиналом';
			}
			switch ($u_user['error'])
			{
				case 'token expired':
					$this->_error = 'Ошибка работы uLogin: время жизни токена истекло';
					return false;
					break;
				case 'invalid token':
					$this->_error = 'Ошибка работы uLogin: неверный токен';
					return false;
					break;
				default:
					$this->_error = 'Ошибка работы uLogin:';
					return false;
					break;
			}
		}
		if (!isset($u_user['identity']))
		{
			$this->_error = 'Ошибка работы uLogin: В возвращаемых данных отсутствует переменная
			 "identity"';
			return false;
		}
		return true;
	}

	public function getUserIdByIdentity($identity)
	{
		return $this->database()->select('u.uid')->from(Phpfox::getT('ulogin_user'), 'u')->where('u.identity =  \''.$identity.'\'')->execute('getField');
	}

	public function getPhpFoxUser($id)
	{
		return $this->database()->select('user_id')->from(Phpfox::getT('user'))->where('user_id = '.$id)->execute('getField');
	}

	/**
	 * Регистрация на сайте и в таблице uLogin
	 *
	 * @param Array $u_user - данные о пользователе, полученные от uLogin
	 * @param int $in_db - при значении 1 необходимо переписать данные в таблице uLogin
	 *
	 * @return bool|int|Error
	 */
	public function uloginRegistrationUser($u_user, $in_db = 0)
	{
		if (!isset($u_user['email']))
		{
			$this->_error = 'Через данную форму выполнить регистрацию невозможно. Сообщите администратору сайта о следующей ошибке:
            Необходимо указать "email" в возвращаемых полях uLogin';
			return false;
		}
		$u_user['network'] = isset($u_user['network']) ? $u_user['network'] : '';
		// данные о пользователе есть в ulogin_table, но отсутствуют в WP
		if ($in_db == 1) $this->database()->delete(Phpfox::getT('ulogin_user'), 'identity = \''.$u_user['identity'].'\'');
		$user_id = $this->database()->select('user_id')->from(Phpfox::getT('user'))->where('email = \''.$u_user['email'].'\'')->execute('getField');
		// $check_m_user == true -> есть пользователь с таким email
		$check_m_user = $user_id > 0 ? true : false;
		$current_user = Phpfox::getUserId();
		// $is_logged_in == true -> ползователь онлайн
		$is_logged_in = $current_user > 0 ? true : false;
		if (($check_m_user == false) && !$is_logged_in)
		{
			if (isset($u_user['bdate']))
			{
				$bdate = explode('.', $u_user['bdate']);
				$day = intval($bdate[0]) < 10 ? '0'.intval($bdate[0]) : $bdate[0];
				$month = intval($bdate[1]) < 10 ? '0'.intval($bdate[1]) : $bdate[1];;
				$year = $bdate[2];
				$u_user['bdate'] = $month.$day.$year;
			}
			else $u_user['bdate'] = '';
			if (isset($u_user['sex']))
			{
				$gender = $u_user['sex'] == 1 ? '2' : '1';
			}
			else $gender = '2';
			$user_login = $this->ulogin_generateNickname($u_user['first_name'], $u_user['last_name'], $u_user['nickname'], $u_user['bdate']);
			$user_pass = md5($user_login);
			$UserFields['username'] = $user_login;
			$salt = $this->_getSalt(7);
			$password = Phpfox::getLib('hash')->setHash($user_pass, $salt);
			try
			{
				$uid = $this->database()->insert(Phpfox::getT('user'), array(
					'email' => $u_user['email'],
					'full_name' => $this->ulogin_translitIt($u_user['first_name']).' '.$this->ulogin_translitIt($u_user['last_name']),
					'user_name' => $user_login,
					'user_group_id' => '2',
					'gender' => $gender,
					'birthday' => $u_user['bdate'],
					'joined' => time(),
					'password_salt' => $salt,
					'password' => $password));
			} catch (Exception $e)
			{
				$this->_error = 'Database error';
			}
			if ($uid)
			{
				try
				{
					$this->database()->insert(Phpfox::getT('user_activity'), array('user_id' => $uid));
					$this->database()->insert(Phpfox::getT('user_field'), array('user_id' => $uid));
					$this->database()->insert(Phpfox::getT('user_space'), array('user_id' => $uid));
					$this->database()->insert(Phpfox::getT('user_count'), array('user_id' => $uid));
				} catch (Exception $e)
				{
					$this->_error = 'Database error';
				}
				$this->database()->insert(Phpfox::getT('ulogin_user'), array('uid' => $uid, 'identity' => $u_user['identity'], 'network' => $u_user['network']));
				$photo_url = $u_user['photo_big'] == 'http://ulogin.ru/img/photo_big.png' ? $u_user['photo'] : $u_user['photo_big'];
				if ($photo = $this->_uploadPhoto($photo_url, $uid))
				{
					$this->database()->update(Phpfox::getT('user'), array('user_image' => $photo), 'user_id = '.$uid);
				}
			}
			else
			{
				$this->_error = 'Internal error';
			}
			return $uid;
//			var_dump($uid);
//			exit;
//			//send email refactoring
//			if (XenForo_Application::getOptions()->uLoginEmail == 1)
//			{
//				$this->sendEmail($user);
//			}
//			if (isset($u_user['photo'])) $u_user['photo'] = $u_user['photo'] === "https://ulogin.ru/img/photo.png" ? '' : $u_user['photo'];
//			if (isset($u_user['photo_big'])) $u_user['photo_big'] = $u_user['photo_big'] === "https://ulogin.ru/img/photo_big.png" ? '' : $u_user['photo_big'];
//			$this->_uploadAvatar((isset($u_user['photo_big']) and !empty($u_user['photo_big'])) ? $u_user['photo_big'] : ((isset($u_user['photo']) and !empty($u_user['photo'])) ? $u_user['photo'] : ''));
//			return $user['user_id'];
		}
		else
		{ // существует пользователь с таким email или это текущий пользователь
			if (!isset($u_user["verified_email"]) || intval($u_user["verified_email"]) != 1)
			{
				$this->_error = '<script src="//ulogin.ru/js/ulogin.js"  type="text/javascript"></script><script type="text/javascript">uLogin.mergeAccounts("'.$_POST['token'].'")</script>'."Электронный адрес данного аккаунта совпадает с электронным адресом существующего пользователя. Требуется подтверждение на владение указанным email.";
				return false;
			}
			if (intval($u_user["verified_email"]) == 1)
			{
				$user_id = $is_logged_in ? $current_user : $user_id;
				$other_u = $this->database()->select('identity')->from(Phpfox::getT('ulogin_user'))->where('uid = '.$user_id)->execute('getRow');
				if ($other_u)
				{
					if (!$is_logged_in && !isset($u_user['merge_account']))
					{
						$this->_error = '<script src="//ulogin.ru/js/ulogin.js"  type="text/javascript"></script><script type="text/javascript">uLogin.mergeAccounts("'.$_POST['token'].'","'.$other_u['identity'].'")</script>'.("С данным аккаунтом уже связаны данные из другой социальной сети. Требуется привязка новой учётной записи социальной сети к этому аккаунту.");
						return false;
					}
				}
				$this->database()->insert(Phpfox::getT('ulogin_user'), array('uid' => $user_id, 'identity' => $u_user['identity'], 'network' => $u_user['network']));
				return $user_id;
			}
		}
		return false;
	}

	/**
	 * Гнерация логина пользователя
	 * в случае успешного выполнения возвращает уникальный логин пользователя
	 *
	 * @param $first_name
	 * @param string $last_name
	 * @param string $nickname
	 * @param string $bdate
	 * @param array $delimiters
	 *
	 * @return string
	 */
	public function ulogin_generateNickname($first_name, $last_name = "", $nickname = "", $bdate = "", $delimiters = array('.', '_'))
	{
		$delim = array_shift($delimiters);
		$first_name = $this->ulogin_translitIt($first_name);
		$first_name_s = substr($first_name, 0, 1);
		$variants = array();
		if (!empty($nickname))
		{
			$variants[] = $nickname;
		}
		$variants[] = $first_name;
		if (!empty($last_name))
		{
			$last_name = $this->ulogin_translitIt($last_name);
			$variants[] = $first_name.$delim.$last_name;
			$variants[] = $last_name.$delim.$first_name;
			$variants[] = $first_name_s.$delim.$last_name;
			$variants[] = $first_name_s.$last_name;
			$variants[] = $last_name.$delim.$first_name_s;
			$variants[] = $last_name.$first_name_s;
		}
		if (!empty($bdate))
		{
			$date = explode('.', $bdate);
			$variants[] = $first_name.$date[2];
			$variants[] = $first_name.$delim.$date[2];
			$variants[] = $first_name.$date[0].$date[1];
			$variants[] = $first_name.$delim.$date[0].$date[1];
			$variants[] = $first_name.$delim.$last_name.$date[2];
			$variants[] = $first_name.$delim.$last_name.$delim.$date[2];
			$variants[] = $first_name.$delim.$last_name.$date[0].$date[1];
			$variants[] = $first_name.$delim.$last_name.$delim.$date[0].$date[1];
			$variants[] = $last_name.$delim.$first_name.$date[2];
			$variants[] = $last_name.$delim.$first_name.$delim.$date[2];
			$variants[] = $last_name.$delim.$first_name.$date[0].$date[1];
			$variants[] = $last_name.$delim.$first_name.$delim.$date[0].$date[1];
			$variants[] = $first_name_s.$delim.$last_name.$date[2];
			$variants[] = $first_name_s.$delim.$last_name.$delim.$date[2];
			$variants[] = $first_name_s.$delim.$last_name.$date[0].$date[1];
			$variants[] = $first_name_s.$delim.$last_name.$delim.$date[0].$date[1];
			$variants[] = $last_name.$delim.$first_name_s.$date[2];
			$variants[] = $last_name.$delim.$first_name_s.$delim.$date[2];
			$variants[] = $last_name.$delim.$first_name_s.$date[0].$date[1];
			$variants[] = $last_name.$delim.$first_name_s.$delim.$date[0].$date[1];
			$variants[] = $first_name_s.$last_name.$date[2];
			$variants[] = $first_name_s.$last_name.$delim.$date[2];
			$variants[] = $first_name_s.$last_name.$date[0].$date[1];
			$variants[] = $first_name_s.$last_name.$delim.$date[0].$date[1];
			$variants[] = $last_name.$first_name_s.$date[2];
			$variants[] = $last_name.$first_name_s.$delim.$date[2];
			$variants[] = $last_name.$first_name_s.$date[0].$date[1];
			$variants[] = $last_name.$first_name_s.$delim.$date[0].$date[1];
		}
		$i = 0;
		$exist = true;
		while (true)
		{
			if ($exist = $this->ulogin_userExist($variants[$i]))
			{
				foreach ($delimiters as $del)
				{
					$replaced = str_replace($delim, $del, $variants[$i]);
					if ($replaced !== $variants[$i])
					{
						$variants[$i] = $replaced;
						if (!$exist = $this->ulogin_userExist($variants[$i])) break;
					}
				}
			}
			if ($i >= count($variants) - 1 || !$exist) break;
			$i++;
		}
		if ($exist)
		{
			while ($exist)
			{
				$nickname = $first_name.mt_rand(1, 100000);
				$exist = $this->ulogin_userExist($nickname);
			}
			return $nickname;
		}
		else
			return $variants[$i];
	}

	/**
	 * Транслит
	 */
	public function ulogin_translitIt($str)
	{
		$tr = array("А" => "a", "Б" => "b", "В" => "v", "Г" => "g", "Д" => "d", "Е" => "e", "Ж" => "j", "З" => "z", "И" => "i", "Й" => "y", "К" => "k", "Л" => "l", "М" => "m", "Н" => "n", "О" => "o", "П" => "p", "Р" => "r", "С" => "s", "Т" => "t", "У" => "u", "Ф" => "f", "Х" => "h", "Ц" => "ts", "Ч" => "ch", "Ш" => "sh", "Щ" => "sch", "Ъ" => "", "Ы" => "yi", "Ь" => "", "Э" => "e", "Ю" => "yu", "Я" => "ya", "а" => "a", "б" => "b", "в" => "v", "г" => "g", "д" => "d", "е" => "e", "ж" => "j", "з" => "z", "и" => "i", "й" => "y", "к" => "k", "л" => "l", "м" => "m", "н" => "n", "о" => "o", "п" => "p", "р" => "r", "с" => "s", "т" => "t", "у" => "u", "ф" => "f", "х" => "h", "ц" => "ts", "ч" => "ch", "ш" => "sh", "щ" => "sch", "ъ" => "y", "ы" => "y", "ь" => "", "э" => "e", "ю" => "yu", "я" => "ya");
		if (preg_match('/[^A-Za-z0-9\_\-]/', $str))
		{
			$str = strtr($str, $tr);
			$str = preg_replace('/[^A-Za-z0-9\_\-\.]/', '', $str);
		}
		return $str;
	}

	/**
	 * Проверка существует ли пользователь с заданным логином
	 */
	function ulogin_userExist($login)
	{
		$check = $this->database()->select('user_id')->from(Phpfox::getT('user'))->where('user_name = '.$login)->execute('getField');
		if ($check == '')
		{
			return false;
		}
		return true;
	}
}

?>
