<?php
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Config as Conf;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Entity\Base;
use \Bitrix\Main\Application;

Loc::loadMessages(__FILE__);
class zup extends CModule {

    function __construct(){
        $this->MODULE_ID = 'zup';
        $this->MODULE_VERSION = '0.0.1';
        $this->MODULE_VERSION_DATE = '2022-11-01 00:00:00';
        $this->MODULE_NAME = 'Главстрой: Zup';
        $this->MODULE_DESCRIPTION = '';
        $this->MODULE_GROUP_RIGHTS = 'Y';
        $this->PARTNER_NAME = 'Главстрой';
        $this->PARTNER_URI = '/';
        $this->MODULE_SORT = 1;
        $this->SHOW_SUPER_ADMIN_GROUP_RIGHTS='Y';
    }


    function doInstall(){
        \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
    }

    function doUninstall(){
        \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    function InstallDB(){

    }

    function GetModuleRightList(){
        return \CMain::GetDefaultRightList();
    }
}
