<?php
define('PHPFOX_SKIP_POST_PROTECTION', true);

class uLogin_Component_Controller_Account extends Phpfox_Component {
	public function process()
	{
		if (isset($_POST['identity']))
		{
			$current_user = Phpfox::getUserId();
			if ($current_user > 0) Phpfox::getService('ulogin')->ajaxDeleteUser($_POST['identity']);
		}
		Phpfox::setCookie('ul_attach_hash', md5(Phpfox::getUserId().Phpfox::getIp().Phpfox::getUserField('email').Phpfox::getUserField('user_name')), 0);
		$this->template()->setTitle('uLogin account settings');
		$this->template()->setBreadcrumb('uLogin account settings');
		$this->template()->setHeader(array('account.css' => 'module_ulogin', 'account.js' => 'module_ulogin', 'ajax.js' => 'module_ulogin'));
	}
}

?>
