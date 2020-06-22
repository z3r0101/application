<?php
/*
 * URI ROUTING
 *
 * Pattern:
 *
 * CMS
 *      example.com/cms/class/method/(id or slug)
 *
 * Front-End
 *      example.com/class/method/(id or slug)
 *
 */

#FRONT-END
$routes["*"] = "";

#CMS
$routes[$CONFIG['cms']['route_name']."/login"] = "cms/cms_login";
$routes[$CONFIG['cms']['route_name']."/logout"] = "cms/cms_logout";
$routes[$CONFIG['cms']['route_name']."/reset-password"] = "cms/cms_reset_password";
$routes[$CONFIG['cms']['route_name']."/administrator"] = "cms/cms_administrator";
$routes[$CONFIG['cms']['route_name']."/administrator/sso-approval"] = "cms/cms_administrator/sso_approval";
$routes[$CONFIG['cms']['route_name']."/administrator/blocked-users"] = "cms/cms_administrator/blocked_users";
$routes[$CONFIG['cms']['route_name']."/administrator/my-account"] = "cms/cms_administrator/my_account";
$routes[$CONFIG['cms']['route_name']."/assets"] = "cms/cms_assets";


