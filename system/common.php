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

function cmsPageMessage($titleHead, $mesage, $scriptAfter = '') {
    global $CONFIG;

    $VENDORS_URL = VENDORS_URL;
    $RES_CMS_URL = RES_CMS_URL;

    print <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <title>{$titleHead}</title>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
            
            <!-- Bootstrap CSS -->
            <link rel="stylesheet" href="{$VENDORS_URL}bootstrap/4.3.1/css/bootstrap.min.css">
            
            <link href="{$VENDORS_URL}open-iconic/1.1.1/font/css/open-iconic-bootstrap.min.css" rel="stylesheet">
            
            <link href="{$VENDORS_URL}fontawesome/free-5.9.0/css/all.css" rel="stylesheet">
            
            <link href="{$VENDORS_URL}bootstrap4-dialog/css/bootstrap-dialog.min.css" rel="stylesheet">
            
            <link href="{$VENDORS_URL}jquery.ui/jquery-ui.min.css" rel="stylesheet" type="text/css">
            <link href="{$VENDORS_URL}jquery.ui/jquery-ui.structure.min.css" rel="stylesheet" type="text/css">
            <link href="{$VENDORS_URL}jquery.ui/jquery-ui.theme.min.css" rel="stylesheet" type="text/css">
            
            <link href="{$RES_CMS_URL}css/global.css" rel="stylesheet">
            
            <script src="{$VENDORS_URL}jquery/jquery-3.4.1.min.js"></script>
            <script src="{$VENDORS_URL}phpjs/phpjs.js"></script>
            
            <script src="{$VENDORS_URL}purl/purl.js"></script>
        </head>
        <body>
            <!-- Optional JavaScript -->
            <!-- jQuery first, then Popper.js, then Bootstrap JS -->
            <script src="{$VENDORS_URL}popper/popper.min.js"></script>
            <script src="{$VENDORS_URL}bootstrap/4.3.1/js/bootstrap.min.js"></script>
            
            <script src="{$VENDORS_URL}bootstrap4-dialog/js/bootstrap-dialog.min.js"></script>
            
            <script src="{$VENDORS_URL}moment/2.18.1/moment.js"></script>
            
            <script src="{$VENDORS_URL}jquery.ui/jquery-ui.min.js"></script>
            <script src="{$VENDORS_URL}jquery.ui.touch/jquery.ui.touch-punch.min.js"></script>
            
            <script src="{$RES_CMS_URL}js/global.js"></script>
            
            <script>
                var tDialog = BootstrapDialog.show(
                    {
                        title: '{$titleHead}',
                        message: '{$mesage}',
                        closable: false
                    }
                );
                
                {$scriptAfter}
            </script>  
        </body>
        </html>   
HTML;
}

function cmsLogin($arrUser, $loginType = 0, $sso_id = 0) {
    global $CONFIG, $CMS_FN_MENU;

    $crypt = new cmsCryptonite();
    $dbClass = new cmsDatabaseClass();

    $arrSession = array(
        $arrUser['CMS_Users_Id'],
        time()
    );
    $cmsSession = $crypt->encode(json_encode($arrSession));

    $_SESSION[$CONFIG['cookie']['prefix']."_cms_session"] = $cmsSession;

    $ipAdd = cmsTools::getClientIP(); #$_SERVER['REMOTE_ADDR'];

    $logDate = date("Y-m-d H:i:s");
    $arrUpdateUsers = array(
        'CMS_Users_SessionId'=>$cmsSession,
        'CMS_Users_Date_Login'=>$logDate,
        'CMS_Editor_IP'=>$ipAdd
    );
    if ($arrUser["CMS_Users_Date_Login"] != '') {
        if (isset($arrUser["CMS_Users_Date_LastLogin"])) {
            $arrUpdateUsers['CMS_Users_Date_LastLogin'] = $arrUser["CMS_Users_Date_Login"];
        }
    }

    $dbClass->update("cms_users",
        $arrUpdateUsers,
        array('CMS_Users_Id'=>$arrUser['CMS_Users_Id'])
    );

    $arrJSONReturn = array();

    $CMS_Users_Access = array();
    if ($arrUser["CMS_Users_Access"]!='') {
        $CMS_Users_Access = json_decode(base64_decode($arrUser["CMS_Users_Access"]), true);
    }

    if ($arrUser['CMS_Users_Type'] == 1) {
        #USER
        foreach($CMS_Users_Access as $Id => $Obj) {
            if ($Obj['options']['view']) {
                $arrJSONReturn['success'] = $Obj['options']['url'];
                break;
            }
        }
    } else {
        #ADMIN
        $tXmlMenu = $CMS_FN_MENU()->xpath('//section');
        if (!is_null($tXmlMenu[0])) $arrJSONReturn['success'] = strval($tXmlMenu[0]['link']);

        if (isset($CONFIG['cms']['default_page'])) {
            if ($CONFIG['cms']['default_page']!='') {
                $arrJSONReturn['success'] = $CONFIG['website']['path'].$CONFIG['cms']['route_name']."/".$CONFIG['cms']['default_page'];
            }
        }
    }

    #Register login
    $dbClass->insert("cms_users_login",
        array(
            'CMS_Users_Id'=>$arrUser['CMS_Users_Id'],
            'CMS_Users_SessionId'=>$cmsSession,
            'CMS_Users_Login_DateTime'=>$logDate,
            'CMS_Users_Login_ClientIP'=>$ipAdd,
            'CMS_Users_Login_Type'=>$loginType,
            'CMS_Users_Login_SSO_Id'=>$sso_id
        )
    );

    return $arrJSONReturn;
}

function pageError($errorName, $errorDescription) {
    global $CONFIG;

    return <<<EOL
        <!DOCTYPE html>
        <html lang="en">
        <head>
        <title>{$errorName}</title>
        <style type="text/css">

        ::selection{ background-color: #E13300; color: white; }
        ::moz-selection{ background-color: #E13300; color: white; }
        ::webkit-selection{ background-color: #E13300; color: white; }

        body {
            background-color: #fff;
            margin: 40px;
            font: 13px/20px normal Helvetica, Arial, sans-serif;
            color: #4F5155;
        }

        a {
            color: #003399;
            background-color: transparent;
            font-weight: normal;
        }

        h1 {
            color: #444;
            background-color: transparent;
            border-bottom: 1px solid #D0D0D0;
            font-size: 19px;
            font-weight: normal;
            margin: 0 0 14px 0;
            padding: 14px 15px 10px 15px;
        }

        code {
            font-family: Consolas, Monaco, Courier New, Courier, monospace;
            font-size: 12px;
            background-color: #f9f9f9;
            border: 1px solid #D0D0D0;
            color: #002166;
            display: block;
            margin: 14px 0 14px 0;
            padding: 12px 10px 12px 10px;
        }

        #container {
            margin: 10px;
            border: 1px solid #D0D0D0;
            -webkit-box-shadow: 0 0 8px #D0D0D0;
        }

        .container-body {
            margin: 12px 15px 12px 15px;
        }
        </style>
        </head>
        <body>
            <div id="container">
                <h1>{$errorName}</h1>
                <div class="container-body">{$errorDescription}</div>	
            </div>
        </body>
        </html>
EOL;

}