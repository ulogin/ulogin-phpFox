<?php
class uLogin_Component_Controller_Admincp_Index extends Phpfox_Component
{
    public function process()
    {
        $link_to_settings = Phpfox::getLib('url')->makeUrl('admincp.setting.edit.group-id_uloginsettings');
        Phpfox::getLib('url')->send($link_to_settings);
    }
}

?>
