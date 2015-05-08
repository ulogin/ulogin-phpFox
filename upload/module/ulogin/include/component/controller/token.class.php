<?php
define('PHPFOX_SKIP_POST_PROTECTION', true);

class uLogin_Component_Controller_Token extends Phpfox_Component {
	public function process()
	{
		$uLogin = Phpfox::getService('ulogin');
		$this->template()->assign('message', '');
		if (isset($_POST['identity']))
		{
			$current_user = Phpfox::getUserId();
			if ($current_user > 0) $uLogin->ulogin_deleteaccount_request($_POST['identity']);
		}
		$this->uloginParseRequest();
	}

	/**
	 * Обработка ответа сервера авторизации
	 */
	public function uloginParseRequest()
	{
		$uLogin = Phpfox::getService('ulogin');
		$url = Phpfox::getLib('url');
		$this->template()->assign('message', '');
		if (!isset($_POST['token'])) return false; // не был получен токен uLogin
		$s = $uLogin->uloginGetUserFromToken($_POST['token']);
		if ($s)
		{
			$error = $uLogin->getError();
			$this->template()->assign('message', $error);
		}
		$u_user = json_decode($s, true);
		$u_user['nickname'] = isset($u_user['nickname']) ? $u_user['nickname'] : $u_user['nickname'] = '';
		$check = $uLogin->uloginCheckTokenError($u_user);
		if (!$check)
		{
			$error = $uLogin->getError();
			$this->template()->assign('message', $error);
			return false;
		}
		$user_id = $uLogin->getUserIdByIdentity($u_user['identity']);
		if ($user_id)
		{
			$pf_user = $uLogin->getPhpFoxUser($user_id);
			if ($user_id > 0 && $pf_user > 0)
			{
				$uLogin->uloginCheckUserId($user_id);
			}
			else
			{
				$user_id = $uLogin->uloginRegistrationUser($u_user, 1);
			}
		}
		else
		{
			$user_id = $uLogin->uloginRegistrationUser($u_user);
		}
		$error = $uLogin->getError();
		if ($error)
		{
			$this->template()->assign('message', $error);
			return false;
		}
		if ($user_id > 0)
		{
			$uLogin->loginUser($u_user, $user_id);
		}
		else
		{
			$error = $uLogin->getError();
			$this->template()->assign('message', $error);
			return false;
		}
		return true;
	}
}

?>
