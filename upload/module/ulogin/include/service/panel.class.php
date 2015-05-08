<?php
defined('PHPFOX') or exit('NO DICE!');
define('PHPFOX_SKIP_POST_PROTECTION', true);

class uLogin_Service_Panel extends Phpfox_Service {

	public function __construct()
	{
	}

	public function getLoginRedirectUrl()
	{
		return urlencode(Phpfox::getLib('url')->makeUrl('ulogin', array('token')));
	}

	public function getMobileLoginRedirectUrl()
	{
		return urlencode(Phpfox::getLib('url')->makeUrl('ulogin', array('token')));
	}

	public function showPanel()
	{
		Phpfox::getBlock('ulogin.uloginpanel', array());
	}

	public function getPanelCode($place = 0, $redirect_link)
	{
		/*
		 * Выводит в форму html для генерации виджета
		 */
		$ulogin_default_options = array();
		$ulogin_default_options['display'] = 'small';
		$ulogin_default_options['providers'] = 'vkontakte,odnoklassniki,mailru,facebook';
		$ulogin_default_options['fields'] = 'first_name,last_name,email,photo,photo_big';
		$ulogin_default_options['optional'] = 'phone';
		$ulogin_default_options['hidden'] = 'other';
		$ulogin_options = array();
		$ulogin_options['ulogin_id1'] = Phpfox::getLib('setting')->getParam('ulogin.uloginid1');
		$ulogin_options['ulogin_id2'] = Phpfox::getLib('setting')->getParam('ulogin.uloginid2');
		$default_panel = false;
		$redirect_uri = $redirect_link;
		switch ($place)
		{
			case 0:
				$ulogin_id = $ulogin_options['ulogin_id1'];
				break;
			case 1:
				$ulogin_id = $ulogin_options['ulogin_id2'];
				break;
			default:
				$ulogin_id = $ulogin_options['ulogin_id1'];
		}
		if (empty($ulogin_id))
		{
			$ul_options = $ulogin_default_options;
			$default_panel = true;
		}
		$panel = '';
		$panel .= '<div id="uloginpanel" class="ulogin_panel"';
		if ($default_panel)
		{
			$ul_options['redirect_uri'] = urlencode($redirect_uri);
			$x_ulogin_params = '';
			foreach ($ul_options as $key => $value) $x_ulogin_params .= $key.'='.$value.';';
			if ($ul_options['display'] != 'window') $panel .= ' data-ulogin="'.$x_ulogin_params.'"></div>';
			else
				$panel .= ' data-ulogin="'.$x_ulogin_params.'" href="#"><img src="https://ulogin.ru/img/button.png" width=187 height=30 alt="МультиВход"/></div>';
		}
		else
			$panel .= ' data-uloginid="'.$ulogin_id.'" data-ulogin="redirect_uri='.urlencode($redirect_uri).'"></div>';
		$panel = '<div class="ulogin_block place'.$place.'" style="padding-top: 6px;">'.$panel.'</div><div style="clear:both"></div>';
		return $panel;
	}

	/**
	 * Вывод списка аккаунтов пользователя
	 *
	 * @param int $user_id - ID пользователя (если не задан - текущий пользователь)
	 *
	 * @return string
	 */
	public function getSyncPanel($user_id = 0)
	{
		$current_user = Phpfox::getUserId();
		$user_id = empty($user_id) ? $current_user : $user_id;
		if (empty($user_id)) return '';
		$networks = $this->database()->select('network,identity')->from(Phpfox::getT('ulogin_user'))->where('uid = \''.$current_user.'\'')->execute('getRows');
		$output = '';
		if ($networks)
		{
			$output .= '<div id="ulogin_accounts">';
			foreach ($networks as $network)
			{
				$output .= "<div data-ulogin-network='{$network['network']}' data-ulogin-identity='{$network['identity']}' class='ulogin_network big_provider {$network['network']}_big'></div>";
			}
			$output .= '</div><div style="clear: both"></div>';
		}
		return $output;
	}
}

?>
