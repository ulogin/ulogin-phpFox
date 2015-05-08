<?php
defined('PHPFOX') or exit('NO DICE!');

class uLogin_Component_Block_Uloginpanel extends Phpfox_Component {
	public function process()
	{
		$current_user = Phpfox::getUserId();
		$redirect_uri = Phpfox::getService('ulogin.panel')->getLoginRedirectUrl();
		if (Phpfox::getLib('module')->getFullControllerName() == 'ulogin.account')
		{
			if ($current_user)
			{
				$panel = Phpfox::getService('ulogin.panel')->getPanelCode(0, $redirect_uri);
				$syncpanel = Phpfox::getService('ulogin.panel')->getSyncPanel();
				$this->template()->assign(array(
					'panel' => urldecode($panel).'<br/><div class="msg">Привязанные аккаунты</div><br/>'.urldecode($syncpanel).'<br/><div class="small_msg">Вы можете удалить привязку к аккаунту, кликнув по значку</div>',
				));
				return 'block';
			}
			else
			{
				Phpfox::getLib('url')->send('');
			}
		}
		if ((Phpfox::getLib('module')->getFullControllerName() == 'user.login'
			or Phpfox::getLib('module')->getFullControllerName() == 'core.index-visitor'))
		{
			$panel = Phpfox::getService('ulogin.panel')->getPanelCode(1, $redirect_uri);
			$this->template()->assign(array('panel' => urldecode($panel),));
			return 'block';
		}

		if (Phpfox::getLib('module')->getFullControllerName() == 'mobile.index'
			or Phpfox::getLib('module')->getFullControllerName() == 'mobile.user.login')
		{
			$redirect_uri = Phpfox::getService('ulogin.panel')->getMobileLoginRedirectUrl();
			$panel = Phpfox::getService('ulogin.panel')->getPanelCode(1, $redirect_uri);
			$this->template()->assign(array(
				'panel' => urldecode($panel),
			));
			return 'block';
		}

		return 'block';
	}
}

?>
