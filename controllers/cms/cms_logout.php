<?php
/*
 * z3r0101
 *
 * An open source application development framework for PHP
 *
 * @package:    z3r0101
 * @author:     ryanzkizen@gmail.com
 * @version:    Beta 1.0
 *
 */

class cms_logout extends BaseController
{
    function __construct()
    {
        parent::__construct();
    }

    function index()
    {
        global $CONFIG;

        $CMS_Users_SessionId = $_SESSION[$CONFIG['cookie']['prefix']."_cms_session"];
        $_SESSION[$CONFIG['cookie']['prefix']."_cms_session"] = "";
        unset($_SESSION[$CONFIG['cookie']['prefix']."_cms_session"]);

        $urlRedirect = $CONFIG['website']['path'].$CONFIG['cms']['route_name']."/login";
        $arrData = $this->dbClass->select(sprintf("SELECT * FROM cms_users_login WHERE CMS_Users_SessionId = '%s'", $this->dbClass->mysqli->real_escape_string($CMS_Users_SessionId)));
        if (count($arrData) > 0) {
            $CMS_Users_Login_Type = $arrData[0]["CMS_Users_Login_Type"];
            $CMS_Users_Login_SSO_Id = $arrData[0]["CMS_Users_Login_SSO_Id"];
            if ($CMS_Users_Login_Type == 1) {
                if (isset($CONFIG['cms']['sso'][$CMS_Users_Login_SSO_Id]['oauth']) && isset($CONFIG['cms']['sso'][$CMS_Users_Login_SSO_Id]['oauth']['logout_url'])) {
                    $urlRedirect = $CONFIG['cms']['sso'][$CMS_Users_Login_SSO_Id]['oauth']['logout_url'];
                }
            }
        }

        header("location: {$urlRedirect}");
        exit;
    }
}