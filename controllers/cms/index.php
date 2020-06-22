<?php

class index extends BaseController
{
    function __construct()
    {
        parent::__construct();
    }

    function index() {
        global $CONFIG;

        $loginUrl = "{$CONFIG['cms']['route_name']}/login";
        if (isset($_GET['sso'])) {
            $sso_index = intval($_GET['sso']);
            if (isset($CONFIG['cms']['sso'][$sso_index]['oauth']) && isset($CONFIG['cms']['sso'][$sso_index]['oauth']['authorize_url'])) {
                $loginUrl = $CONFIG['cms']['sso'][$sso_index]['oauth']['authorize_url'];
            }
        }
        header("location: {$loginUrl}");
    }

    function sso() {
        global $CONFIG;
        $CMS_Users_Login_SSO_Id = 0;

        $error_account_message = 'Single Sign-On Error: Account not found.';
        $error_message = 'Single Sign-On Error. Contact administrator.';

        if (!isset($CONFIG['cms']['sso'][$CMS_Users_Login_SSO_Id]['oauth']['get_user_url'])) {
            cmsPageMessage($CONFIG['cms']['title'], 'Single Sign-On Error: URL not found.');
            exit;
        }

        if (isset($_GET['access_token'])) {
            $pAccessToken = $_GET['access_token'];

            $arrHTTP = array();

            $arrHeader = array();
            $arrHeader[] = "Accept: application/json";
            $arrHeader[] = "Authorization: Bearer {$pAccessToken}";

            $arrHTTP["method"] = "GET";

            $arrHTTP["header"] = implode("\r\n", $arrHeader);

            $opts = array(
                'http' => $arrHTTP
            );
            $context = stream_context_create($opts);

            $jsonAuth = file_get_contents($CONFIG['cms']['sso'][$CMS_Users_Login_SSO_Id]['oauth']['get_user_url'], false, $context);
            if (trim($jsonAuth) == '') {
                cmsPageMessage($CONFIG['cms']['title'], $error_account_message);
                exit;
            }

            $arrAuth = json_decode($jsonAuth, true);
            $arrData =$this->dbClass->select(sprintf("SELECT * FROM cms_users WHERE CMS_Users_Website = '%s' AND CMS_Users_Name = '%s'", $this->dbClass->mysqli->real_escape_string($CONFIG['website']['domain']), $this->dbClass->mysqli->real_escape_string($arrAuth['data']['con_email'])));
            $arrRet = array();
            if (count($arrData) == 0) {
                $arrRet = $this->dbClass->insert("cms_users",
                    array(
                        'CMS_Users_Name'=>$arrAuth['data']['con_email'],
                        'CMS_Users_Password'=>'',
                        'CMS_Users_Name_First'=>$arrAuth['data']['con_fname'],
                        'CMS_Users_Name_Last'=>$arrAuth['data']['con_lname'],
                        'CMS_Users_Email'=>$arrAuth['data']['con_email'],
                        'CMS_Users_Type'=>-1,
                        'CMS_Users_Status_SSO'=>1,
                        'CMS_Users_Website'=>$CONFIG['website']['domain'],
                        'CMS_Users_Date_Created'=>date("Y-m-d H:i:s"),
                        'CMS_Editor_IP'=>cmsTools::getClientIP()
                    )
                );
            } else {
                $arrRet['value'] = $arrData[0]['CMS_Users_Id'];

                if ($arrData[0]['CMS_Users_Status_SSO'] == 3) {
                    $this->dbClass->update("cms_users",
                        array(
                            'CMS_Users_Status_SSO'=>1
                        ),
                        $arrRet['value']
                    );
                }
            }
            if (isset($arrRet['value'])) {
                $arrData = $this->dbClass->select(sprintf("SELECT * FROM cms_users WHERE CMS_Users_Website = '%s' AND CMS_Users_Id = %d", $this->dbClass->mysqli->real_escape_string($CONFIG['website']['domain']), $this->dbClass->mysqli->real_escape_string($arrRet['value'])));
                if (count($arrData) > 0) {
                    if ($arrData[0]['CMS_Users_Status_SSO'] == 1) {
                        cmsPageMessage($CONFIG['cms']['title'], 'Your account is waiting for approval from an administrator.<br>You will not be able to log in until you receive this approval.');
                    } else {
                        $arrJSONReturn = cmsLogin($arrData[0], 1, $CMS_Users_Login_SSO_Id);
                        if (isset($arrJSONReturn['success'])) {
                            header("location: {$arrJSONReturn['success']}");
                        }
                    }
                } else {
                    cmsPageMessage($CONFIG['cms']['title'], $error_message);
                    exit;
                }
            } else {
                cmsPageMessage($CONFIG['cms']['title'], $error_message);
                exit;
            }
        } else {
            cmsPageMessage($CONFIG['cms']['title'], 'Redirecting, please wait',
                '
                    var arrURL = window.location.toString().split(\'#\');
                    if (arrURL.length == 2) {
                        window.location.replace(arrURL[0]+\'?\'+arrURL[1]);
                    } else {
                        tDialog.setMessage("'.$error_account_message.'");
                    }
                '
            );
        }
    }
}