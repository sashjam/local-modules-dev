<?php
IncludeModuleLangFile(__FILE__);

if (class_exists("dev"))
    return;

class dev extends CModule
{
    var $MODULE_ID = "dev";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;

    function __construct()
    {
        $arModuleVersion = array();

        include(dirname(__FILE__)."/version.php");

        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

        $this->MODULE_NAME = GetMessage("DEV_INSTALL_NAME");
        $this->MODULE_AUTHOR = GetMessage("DEV_AUTHOR");
        $this->MODULE_DESCRIPTION = GetMessage("DEV_INSTALL_DESCRIPTION");
    }

    function InstallDB()
    {
        global $DB;


        RegisterModule("dev");


        return true;
    }

    function UnInstallDB()
    {
        global $DB;


        UnRegisterModule("dev");

        return true;
    }

    function UnInstallFiles()
    {

        return true;
    }

    function DoInstall()
    {
        $this->InstallFiles();
        $this->InstallDB();
    }

    function DoUninstall()
    {
        $this->UnInstallDB();
        $this->UnInstallFiles();
    }
}