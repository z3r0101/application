<?php
/*
 * z3r0101
 *
 * https://github.com/z3r0101
 *
 * An open source application development framework for PHP
 *
 * @package:    z3r0101
 * @author:     ryanzkizen@gmail.com
 * @version:    Beta 1.0
 *
 */

#LOAD CONFIG
if (APPPATH . 'config/config.php') {
    require_once(APPPATH . 'config/config.php');
}
if (file_exists(SITEROOTPATH.'www/config.php')) {
    require_once(SITEROOTPATH . 'www/config.php');
}

if (APPPATH . 'config/config.cms.php') {
    require_once(APPPATH . 'config/config.cms.php');
}
if (file_exists(SITEROOTPATH.'www/config.cms.php')) {
    require_once(SITEROOTPATH . 'www/config.cms.php');
}

if (APPPATH . 'config/routes.php') {
    require_once(APPPATH . 'config/routes.php');
}
if (file_exists(SITEROOTPATH.'www/routes.php')) {
    require_once(SITEROOTPATH . 'www/routes.php');
}

require_once(APPPATH.'config/cron.php');

#LOAD CLASSES
require_once(APPPATH.'classes/database.php');
require_once(APPPATH.'classes/cryptonite.php');
require_once(APPPATH.'classes/cms.tools.php');
require_once(APPPATH.'classes/page.list.php');

require_once(APPPATH.'system/common.php');
require_once(APPPATH.'system/controller.php');
require_once(APPPATH.'system/controller.cms.php');
require_once(APPPATH.'system/model.php');

#page db
$cmsPageDB = new cmsDatabaseClass();
$cmsArrPageDB = $cmsPageDB->select("SHOW TABLES LIKE 'cms_page';");
if (count($cmsArrPageDB)>0) {
    $cmsArrPageDB = $cmsPageDB->select("SELECT * FROM cms_page WHERE CMS_Page_Status = 1 ORDER BY CMS_Page_Order, CMS_Page_Id");
    foreach ($cmsArrPageDB as $cmsPageIndex => $cmsPageData) {
        eval(base64_decode($cmsPageData["CMS_Page_Route"]));
    }
}
#page db

$tPath = '';
if (php_sapi_name() == 'cli') {
    $arrOpt = getopt("p:");
    if (isset($arrOpt['p'])) {
        $tPath = ($arrOpt['p']!='/' ? strtok($arrOpt['p'],'?') : '');
        $tPath = str_replace($CONFIG['website']['path'], '/', $tPath);
        $tPath = ($tPath!='/') ? $tPath : '';
    }
} else {
    $tPath = ($_SERVER["REQUEST_URI"]!='/' ? strtok($_SERVER["REQUEST_URI"],'?') : '');
    $tPath = str_replace($CONFIG['website']['path'], '/', $tPath);
    $tPath = ($tPath!='/') ? $tPath : '';
}

define('PATH_INFO', $tPath);

define('CONFIG_ENVIRONMENT', $CONFIG['environment']);

define('WEBSITE_URL', $CONFIG['website']['path']);
define('CONFIG_CMS_DIRECTORY_NAME', $CONFIG['cms']['directory_name']);
define('CONFIG_CMS_ROUTE_NAME', $CONFIG['cms']['route_name']);

define('RES_URL', $CONFIG['website']['path'].'resources/'); #define('RES_URL', $CONFIG['website']['path'].'www/resources/');

define('RES_CMS_URL', $CONFIG['website']['path'].'application/resources/cms/');

define('VENDORS_URL', $CONFIG['website']['path'].'vendors/');

define('ASSETS_URL', $CONFIG['website']['path'].'assets/');
define('UPLOADS_URL', $CONFIG['website']['path'].'assets/uploads/');

$_pageArrPath = explode('/', substr(PATH_INFO,1));
$pageUrlData = array();

if (PATH_INFO!='') {
    $pageUrlClass = implode('/',$_pageArrPath);
    $pageUrlClass = explode('/', $pageUrlClass);

    $pageCMSBasePath = '';
    $pageCMSRoutePath = '';

    if ($_pageArrPath[0]==$CONFIG['cms']['route_name']) {
        if (php_sapi_name() != 'cli') {
            session_start();
        }

        $pageCMSBasePath = $CONFIG['cms']['directory_name'] . '/';
        $pageCMSRoutePath = $CONFIG['cms']['route_name'] . '/';

        array_shift($pageUrlClass);

        #SETUP CRON
        if (isset($CRON)) {
            $hasUpdated = false;
            $outputCronList = array();

            exec('crontab -l', $outputCronList);

            $arrTempCron = array();

            foreach($outputCronList as $Index => $CronCommand) {
                if ($CronCommand != '') {
                    $arrCron = explode('#', $CronCommand);
                    if (isset($arrCron[count($arrCron)-1])) {
                        if ($arrCron[count($arrCron)-1] == $CONFIG['website']['domain']) {
                            #print $CronCommand.'<br>';
                            $arrTempCron[] = array('index'=>$Index, 'command'=>$CronCommand);
                        }
                    }
                }
            }

            if (count($arrTempCron) > count($CRON)) {
                foreach($arrTempCron as $Index => $CronData) {
                    if (!isset($CRON[$Index])) {
                        unset($outputCronList[$arrTempCron[$Index]['index']]);
                        unset($arrTempCron[$Index]);
                    } else {
                        $arrTempCron[$Index]['command'] = $CRON[$Index].' #'.$CONFIG['website']['domain'];
                    }
                }
                $hasUpdated = true;
            }

            if (count($arrTempCron)==0) {
                #APPEND CRON
                foreach($CRON as $Index => $CronCommand) {
                    #NEW CRON
                    $arrTempCron[] = array('index'=>-1, 'command'=>$CronCommand.' #'.$CONFIG['website']['domain']);
                    $hasUpdated = true;
                }
            }

            if (count($CRON) > count($arrTempCron)) {
                foreach($CRON as $Index => $CronCommand) {
                    if (strval(array_search($Index, array_column($arrTempCron, 'index')))=="") {
                        #NEW CRON
                        $arrTempCron[] = array('index'=>-1, 'command'=>$CronCommand.' #'.$CONFIG['website']['domain']);
                        $hasUpdated = true;
                    }
                }
            }

            foreach($CRON as $Index => $CronCommand) {
                if ($arrTempCron[$Index]['command'] != ($CronCommand.' #'.$CONFIG['website']['domain'])) {
                    $arrTempCron[$Index]['command'] = $CronCommand.' #'.$CONFIG['website']['domain'];
                    $hasUpdated = true;
                }
            }

            if ($hasUpdated) {
                foreach($arrTempCron as $Index => $CronData) {
                    if ($CronData['index'] != -1) {
                        $outputCronList[$CronData['index']] = $CronData['command'];
                    } else {
                        $outputCronList[] = $CronData['command'];
                    }
                }

                shell_exec('crontab -r');

                foreach($outputCronList as $Index => $CronCommand) {
                    shell_exec("crontab -l | { cat; echo '{$CronCommand}'; } |crontab -");
                }
            }
        }
    }

    $pageUrl = $pageUrlClass;

    $pageClass = '';
    $pageMethod = '';

    #routes
    $pageArr = array();
    foreach($pageUrlClass as $Index => $Value) {
        $pageArr[] = $Value;
        if (isset($routes[$pageCMSRoutePath.implode('/', $pageArr)])) {
            $arrRoutes = explode('/', $routes[$pageCMSRoutePath.implode('/', $pageArr)]);
            if ($pageCMSRoutePath!='') array_shift($arrRoutes);
            foreach($arrRoutes as $Index => $Value) {
                $pageUrlClass[$Index] = $Value;
            }
        } else {
            if (isset($routes[implode('/', $pageArr)])) {
                $arrRoutes = explode('/', $routes[implode('/', $pageArr)]);
                array_shift($arrRoutes);
                foreach ($arrRoutes as $Index => $Value) {
                    $pageUrlClass[$Index] = $Value;
                }
            }
        }
    }

    if (count($pageUrlClass)==0) {
        $pageClass = 'index';
        $pageMethod = "index";
        $pageUrlClass[0] = 'index';
        $pageUrl = $pageUrlClass;
    }

//    $tempXML = simplexml_load_string('
//    <container>
//    <section title="Home" link="home" role="admin|editor">
//        <sub title="Default Page" link="home/default/list" role="admin|editor"></sub>
//        <sub title="Sub Menu" link="home/sub-menu/list" role="admin|editor"></sub>
//        <sub title="Content" link="home/content/list" role="admin|editor"></sub>
//    </section>
//    <section menu="false" title="Home" link="home/content-main/list" role="admin|editor" link_method_access="default"></section>
//    <section menu="false" title="Home" link="home/content-aside/list" role="admin|editor" link_method_access="default"></section>
//    </container>
//    ');
//    foreach ($tempXML->xpath('//section') as $child) {
//        print_r($child->asXML());
//    }
//    exit;

    $CMS_FN_MENU = function () {
        global $CONFIG;

        $cmsPageDB = new cmsDatabaseClass();

        $xmlSectionFile = '';
        if (file_exists(SITEROOTPATH.'www/views/cms/layout/cms_sections.xml')) {
            $xmlSectionFile = SITEROOTPATH.'www/views/cms/layout/cms_sections.xml';
        } else {
            $xmlSectionFile = APPPATH.'views/cms/layout/cms_sections.xml';
        }

        $cmsSection =  simplexml_load_string(file_get_contents($xmlSectionFile), "SimpleXMLElement", LIBXML_NOCDATA);
        $cmsArrMenuAdmin = array();
        $cmsSectionNode = isset($cmsSection->xpath('//*[@title="Users"]')[0]) ? $cmsSection->xpath('//*[@title="Users"]')[0] : null;
        if (!is_null($cmsSectionNode)) {
            $cmsArrMenuAdmin[] = $cmsSectionNode->asXML();
            foreach ($cmsSection->xpath('//*[@title="Users"]') as $child) {
                unset($child[0]);
            }
        } else {
            $hasBlockedUsers = false;
            $arrUsers = $cmsPageDB->select("SELECT * FROM cms_users WHERE CMS_Users_Website = '{$CONFIG['website']['domain']}' AND CMS_Users_Status_SSO IN (0,2) AND CMS_Users_Status = 0");
            if (count($arrUsers) > 0) {
                $hasBlockedUsers = true;
            }

            $arrUsers = $cmsPageDB->select("SELECT * FROM cms_users WHERE CMS_Users_Website = '{$CONFIG['website']['domain']}' AND CMS_Users_Status_SSO = 1");
            if (count($arrUsers) == 0) {
                if (!$hasBlockedUsers) {
                    $cmsArrMenuAdmin[] = '<section title="Users" link="administrator/users/list" role="admin"></section>';
                } else {
                    $cmsArrMenuAdmin[] = '
                    <section title="Users" link="administrator/users/list" role="admin">
                        <sub title="Active Users" link="administrator/users/list" role="admin"></sub>
                        '.(($hasBlockedUsers) ? '<sub title="Blocked Users" link="administrator/blocked-users/list" role="admin"></sub>' : '').'
                    </section>
                    ';
                }
            } else {
                $cmsArrMenuAdmin[] = '
                    <section title="Users" link="administrator" role="admin" badge_count="SELECT COUNT(*) FROM cms_users WHERE CMS_Users_Status_SSO = 1">
                        <sub title="SSO Approval" link="administrator/sso-approval/list" role="admin" badge_count="SELECT COUNT(*) FROM cms_users WHERE CMS_Users_Status_SSO = 1"></sub>
                        <sub title="Active Users" link="administrator/users/list" role="admin"></sub>
                        '.(($hasBlockedUsers) ? '<sub title="Blocked Users" link="administrator/blocked-users/list" role="admin"></sub>' : '').'
                    </section>
                ';
            }
        }
        $cmsArrMenuAsset = array();
        $cmsSectionNode = isset($cmsSection->xpath('//*[@title="Assets"]')[0]) ? $cmsSection->xpath('//*[@title="Assets"]')[0] : null;
        if (!is_null($cmsSectionNode)) {
            $cmsArrMenuAsset[] = $cmsSectionNode->asXML();
            foreach ($cmsSection->xpath('//*[@title="Assets"]') as $child) {
                unset($child[0]);
            }
        } else {
            #$cmsArrMenuAsset[] = '<section title="Assets" link="assets/list" role="admin|editor"></section>';
        }
        $cmsArrMenu = array();
        $cmsArrMenuTop = array();
        foreach ($cmsSection->children() as $child) {
            if (strval($child["order"]) != 'top') {
                $cmsArrMenu[] = $child->asXML();
            } else {
                $cmsArrMenuTop[] = $child->asXML();
            }
        }
        foreach($cmsSection->xpath('//section') as $child) {
            unset($child[0]);
        }
        foreach($cmsArrMenuTop as $tempIndex => $tempXML) {
            $target_dom = dom_import_simplexml($cmsSection->xpath('//sections')[0]);
            $insert_dom = $target_dom->ownerDocument->importNode(dom_import_simplexml(new SimpleXMLElement($tempXML)), true);
            $target_dom->appendChild($insert_dom);
        }
        $cmsArrPageDB = $cmsPageDB->select("SHOW TABLES LIKE 'cms_page';");
        if (count($cmsArrPageDB)>0) {
            $cmsArrPageDB = $cmsPageDB->select("SELECT * FROM cms_page WHERE CMS_Page_Status = 1 ORDER BY CMS_Page_Order, CMS_Page_Id");
            foreach ($cmsArrPageDB as $cmsPageIndex => $cmsPageData) {
                if ($cmsPageData["CMS_Page_Section_Menu_XML"] != '') {
                    $tempXML = simplexml_load_string("<container>{$cmsPageData["CMS_Page_Section_Menu_XML"]}</container>");
                    foreach ($tempXML->xpath('//section') as $child) {
                        $target_dom = dom_import_simplexml($cmsSection->xpath('//sections')[0]);
                        $insert_dom = $target_dom->ownerDocument->importNode(dom_import_simplexml(new SimpleXMLElement($child->asXML())), true);
                        $target_dom->appendChild($insert_dom);
                    }
                }
            }
        }
        foreach($cmsArrMenu as $tempIndex => $tempXML) {
            $target_dom = dom_import_simplexml($cmsSection->xpath('//sections')[0]);
            $insert_dom = $target_dom->ownerDocument->importNode(dom_import_simplexml(new SimpleXMLElement($tempXML)), true);
            $target_dom->appendChild($insert_dom);
        }
        if (isset($cmsArrMenuAsset[0])) {
            $target_dom = dom_import_simplexml($cmsSection->xpath('//sections')[0]);
            $insert_dom = $target_dom->ownerDocument->importNode(dom_import_simplexml(new SimpleXMLElement($cmsArrMenuAsset[0])), true);
            $target_dom->appendChild($insert_dom);
        }
        if (isset($cmsArrMenuAdmin[0])) {
            $target_dom = dom_import_simplexml($cmsSection->xpath('//sections')[0]);
            $insert_dom = $target_dom->ownerDocument->importNode(dom_import_simplexml(new SimpleXMLElement($cmsArrMenuAdmin[0])), true);
            $target_dom->appendChild($insert_dom);
        }
        return $cmsSection;
    };

    #print '<pre>';
    #print_r($CMS_FN_MENU());
    #exit;

#page db
    $CMS_Page_Controller = "";
    $cmsArrPageDB = $cmsPageDB->select("SHOW TABLES LIKE 'cms_page';");
    if (count($cmsArrPageDB)>0) {
        $cmsArrPageDB = $cmsPageDB->select("SELECT * FROM cms_page WHERE CMS_Page_Status = 1");
        foreach ($cmsArrPageDB as $dbIndex => $dbData) {
            $tStr = base64_decode($dbData["CMS_Page_Controller"]);
            if (strpos($tStr, $pageUrlClass[0]) !== false) {
                $CMS_Page_Controller = $tStr;
                break;
            }
        }
    }
    if ($CMS_Page_Controller!='') {
        eval($CMS_Page_Controller);
        if (class_exists($pageUrlClass[0])) {
            $pageClass = $pageUrlClass[0];
            $pageObj = new $pageClass(0);

            $pageMethod = (isset($pageUrlClass[1])) ? $pageUrlClass[1] : 'index';

            if (!method_exists($pageObj, $pageMethod)) {
                $pageMethod = '';
            }

            if (isset($pageUrlClass[2])) {
                if (method_exists($pageObj, "{$pageMethod}")) {
                    $pageMethod = "{$pageMethod}";
                    $pageUrlData = array_slice($pageUrlClass, 2);
                } else {
                    if (method_exists($pageObj, "index")) {
                        $pageMethod = "index";
                        $pageUrlData = array_slice($pageUrlClass, 1);
                    }
                }
            } else {
                if (isset($pageUrlClass[1])) {
                    if (method_exists($pageObj, "{$pageMethod}")) {
                        $pageMethod = "{$pageMethod}";
                        $pageUrlData = array_slice($pageUrlClass, 3);
                    } else {
                        if (method_exists($pageObj, "index")) {
                            $pageMethod = "index";
                            $pageUrlData = array_slice($pageUrlClass, 1);
                        }
                    }
                }
            }
        } else {
            if (file_exists(APPPATH.'controllers/'.$pageCMSBasePath.'index.php')) {
                include_once(APPPATH.'controllers/'.$pageCMSBasePath.'index.php');
                $pageClass = 'index';
                $pageObj = new $pageClass();

                if (method_exists($pageObj, $pageUrlClass[0])) {
                    $pageMethod = $pageUrlClass[0];
                    $pageUrlData = array_slice($pageUrlClass, 1);
                } else {
                    if (isset($routes["*"])) {
                        $pageMethod = "index";
                        $pageUrlData = array_slice($pageUrlClass, 0);
                    }

                }
            } else if (file_exists(SITEROOTPATH.'www/controllers/'.$pageCMSBasePath.'index.php')) {
                include_once(SITEROOTPATH.'www/controllers/'.$pageCMSBasePath.'index.php');
                $pageClass = 'index';
                $pageObj = new $pageClass();

                if (method_exists($pageObj, $pageUrlClass[0])) {
                    $pageMethod = $pageUrlClass[0];
                    $pageUrlData = array_slice($pageUrlClass, 1);
                } else {
                    if (isset($routes["*"])) {
                        $pageMethod = "index";
                        $pageUrlData = array_slice($pageUrlClass, 0);
                    }

                }
            }
        }
    } else {
        if (file_exists(APPPATH.'controllers/'.$pageCMSBasePath.$pageUrlClass[0].'.php')) {
            include_once(APPPATH . 'controllers/' . $pageCMSBasePath . $pageUrlClass[0] . '.php');
            if (class_exists($pageUrlClass[0])) {
                $pageClass = $pageUrlClass[0];
                $pageObj = new $pageClass(0);

                $pageMethod = (isset($pageUrlClass[1])) ? $pageUrlClass[1] : 'index';

                if (!method_exists($pageObj, $pageMethod)) {
                    $pageMethod = '';
                }

                if (isset($pageUrlClass[2])) {
                    if (method_exists($pageObj, "{$pageMethod}")) {
                        $pageMethod = "{$pageMethod}";
                        $pageUrlData = array_slice($pageUrlClass, 2);
                    } else {
                        if (method_exists($pageObj, "index")) {
                            $pageMethod = "index";
                            $pageUrlData = array_slice($pageUrlClass, 1);
                        }
                    }
                } else {
                    if (isset($pageUrlClass[1])) {
                        if (method_exists($pageObj, "{$pageMethod}")) {
                            $pageMethod = "{$pageMethod}";
                            $pageUrlData = array_slice($pageUrlClass, 3);
                        } else {
                            if (method_exists($pageObj, "index")) {
                                $pageMethod = "index";
                                $pageUrlData = array_slice($pageUrlClass, 1);
                            }
                        }
                    }
                }
            }
        } else if (file_exists(SITEROOTPATH.'www/controllers/'.$pageCMSBasePath.$pageUrlClass[0].'.php')) {
            include_once(SITEROOTPATH . 'www/controllers/' . $pageCMSBasePath . $pageUrlClass[0] . '.php');
            if (class_exists($pageUrlClass[0])) {
                $pageClass = $pageUrlClass[0];
                $pageObj = new $pageClass(0);

                $pageMethod = (isset($pageUrlClass[1])) ? $pageUrlClass[1] : 'index';

                if (!method_exists($pageObj, $pageMethod)) {
                    $pageMethod = '';
                }

                if (isset($pageUrlClass[2])) {
                    if (method_exists($pageObj, "{$pageMethod}")) {
                        $pageMethod = "{$pageMethod}";
                        $pageUrlData = array_slice($pageUrlClass, 2);
                    } else {
                        if (method_exists($pageObj, "index")) {
                            $pageMethod = "index";
                            $pageUrlData = array_slice($pageUrlClass, 1);
                        }
                    }
                } else {
                    if (isset($pageUrlClass[1])) {
                        if (method_exists($pageObj, "{$pageMethod}")) {
                            $pageMethod = "{$pageMethod}";
                            $pageUrlData = array_slice($pageUrlClass, 3);
                        } else {
                            if (method_exists($pageObj, "index")) {
                                $pageMethod = "index";
                                $pageUrlData = array_slice($pageUrlClass, 1);
                            }
                        }
                    }
                }
            }
        } else {
            #FALL TO INDEX
            if (file_exists(APPPATH.'controllers/'.$pageCMSBasePath.'index.php')) {
                include_once(APPPATH.'controllers/'.$pageCMSBasePath.'index.php');
                $pageClass = 'index';
                $pageObj = new $pageClass();

                if (method_exists($pageObj, $pageUrlClass[0])) {
                    $pageMethod = $pageUrlClass[0];
                    $pageUrlData = array_slice($pageUrlClass, 1);
                } else {
                    if (isset($routes["*"])) {
                        $pageMethod = "index";
                        $pageUrlData = array_slice($pageUrlClass, 0);
                    }

                }
            } else if (file_exists(SITEROOTPATH.'www/controllers/'.$pageCMSBasePath.'index.php')) {
                include_once(SITEROOTPATH.'www/controllers/'.$pageCMSBasePath.'index.php');
                $pageClass = 'index';
                $pageObj = new $pageClass();

                if (method_exists($pageObj, $pageUrlClass[0])) {
                    $pageMethod = $pageUrlClass[0];
                    $pageUrlData = array_slice($pageUrlClass, 1);
                } else {
                    if (isset($routes["*"])) {
                        $pageMethod = "index";
                        $pageUrlData = array_slice($pageUrlClass, 0);
                    }

                }
            }
        }
    }

    /*#FOR DEBUGGING
            print "debug [{$pageClass}::{$pageMethod}]<hr><pre>";
            print_r($pageUrlClass);
            print_r($pageUrl);

            $pageUrlSelected = array();
            foreach($pageUrlClass as $Index => $Name) {
                $pageUrlSelected[$Name] = $pageUrl[$Index];
            }

            print_r($pageUrlSelected);

            print "</pre>";
            exit;
    #*/

    $pageUrlSelected = array();
    foreach($pageUrlClass as $Index => $Name) {
        $pageUrlSelected[$Name] = $pageUrl[$Index];
    }

    if (CONFIG_ENVIRONMENT == 'development') {
        if ($pageClass == '') {
            print pageError("Class Not Found", 'Class <strong>'.$pageUrlClass[0].'</strong> not found');
            exit;
        }
        if ($pageMethod == '') {
            print pageError("Method Not Found", 'Method <strong>'.$pageUrlClass[0].'</strong> not found in <strong>'.$pageClass.'</strong>');
            exit;
        }
    } else {
        if ($pageClass == '') {
            print pageError("404 Page Not Found", 'The page you requested was not found.');
            http_response_code(404);
            exit;
        }
        if ($pageMethod == '') {
            print pageError("404 Page Not Found", 'The page you requested was not found.');
            http_response_code(404);
            exit;
        }
    }

    define("pageSelectedClass", $pageClass);
    define("pageSelectedMethod", $pageMethod);

    if (isset($pageUrlSelected[$pageClass]))
        define("pageSelectedUrlClass", $pageUrlSelected[$pageClass]);
    else
        define("pageSelectedUrlClass", "index");


    if (isset($pageUrlSelected[$pageMethod]))
        define("pageSelectedUrlMethod", $pageUrlSelected[$pageMethod]);
    else
        define("pageSelectedUrlMethod", "index");

    #region -- RUN CMS LIST --
    $pageObj = new $pageClass();
    $pageObj->requestSlug = $pageUrlData;

    $pageObj->selectedClass = $pageClass;
    $pageObj->selectedMethod = $pageMethod;


    if (isset($pageUrlSelected[$pageClass]))
        $pageObj->selectedUrlClass = pageSelectedUrlClass; #$pageUrlSelected[$pageClass];
    else
        $pageObj->selectedUrlClass = "index";


    if (isset($pageUrlSelected[$pageMethod]))
        $pageObj->selectedUrlMethod = pageSelectedUrlMethod; #$pageUrlSelected[$pageMethod];
    else
        $pageObj->selectedUrlMethod = "index";

    $pageObj->selectedUrlPath = "{$CONFIG['website']['path']}".$pageCMSRoutePath."{$pageObj->selectedUrlClass}/{$pageObj->selectedUrlMethod}";

    call_user_func( array($pageObj, $pageMethod), $pageUrlData );
    #endregion
} else {
    $pageClass = 'index';
    $pageMethod = "index";

    if (file_exists(SITEROOTPATH . 'www/controllers/' . $pageClass . '.php')) {
        include_once(SITEROOTPATH . 'www/controllers/' . $pageClass . '.php');
    } else {
        include_once(APPPATH . 'controllers/' . $pageClass . '.php');
    }

    $pageObj = new $pageClass();
    $pageObj->requestSlug = $pageUrlData;

    $pageObj->selectedClass = $pageClass;
    $pageObj->selectedMethod = $pageMethod;

    call_user_func( array($pageObj, $pageMethod), $pageUrlData );

}

//if (!headers_sent()) {
//    print pageError("404 Page Not Found", 'The page you requested was not found.');
//    http_response_code(404);
//    #header('HTTP/1.1 404 Not Found');
//    exit;
//}