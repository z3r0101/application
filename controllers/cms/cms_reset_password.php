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

class cms_reset_password extends BaseController
{
    function __construct()
    {
        parent::__construct();
    }

    function index()
    {
        global $CONFIG, $CMS_FN_MENU;

        $arrValidation = array();

        $getData = (isset($_GET["data"])) ? trim($_GET["data"]) : "";
        if ($getData == "") {
            $arrValidation['message'] = "Invalid password reset data";
            $arrValidation['error'] = true;
        } else {
            $arrData = $this->dbClass->select(sprintf("SELECT * FROM cms_users WHERE CMS_Users_SessionId='%s' AND CMS_Users_Status = 1 AND CMS_Users_Website = '%s'", $this->dbClass->mysqli->real_escape_string($getData), $this->dbClass->mysqli->real_escape_string($CONFIG['website']['domain'])));
            if (count($arrData) == 0) {
                $arrValidation['message'] = "Invalid password reset data";
                $arrValidation['error'] = true;
            } else {
                $CMS_Users_Id = $arrData[0]["CMS_Users_Id"];
                $crypt = new cmsCryptonite();
                $CMS_Users_SessionId = $arrData[0]["CMS_Users_SessionId"];
                $dCMS_Users_SessionId = $crypt->decrypt($CMS_Users_SessionId);
                if ($dCMS_Users_SessionId == "") {
                    $arrValidation['message'] = "Invalid password reset data";
                    $arrValidation['error'] = true;
                } else {
                    $arrSession = json_decode($dCMS_Users_SessionId, true);
                    if (!is_array($arrSession)) {
                        $arrValidation['message'] = "Invalid password reset data";
                        $arrValidation['error'] = true;
                    } else {
                        if ($arrSession[0]!=$CMS_Users_Id) {
                            $arrValidation['message'] = "Invalid password reset data";
                            $arrValidation['error'] = true;
                        }
                    }
                }
            }
        }

        if (isset($_POST["cmsPostData"])) {
            $crypt = new cmsCryptonite();
            $posData = json_decode(base64_decode($_POST['cmsPostData']), true);
            $arrJSONReturn = array();
            $arrInputError = array();

            $posData["data"] = isset($posData["data"]) ? $posData["data"] : '';

            $arrData = $this->dbClass->select(sprintf("SELECT * FROM cms_users WHERE CMS_Users_SessionId='%s' AND CMS_Users_Status = 1 AND CMS_Users_Website = '%s'", $this->dbClass->mysqli->real_escape_string($posData["data"]), $this->dbClass->mysqli->real_escape_string($CONFIG['website']['domain'])));
            if (count($arrData) == 0) {
                $arrJSONReturn['message'] = "Invalid password reset data";
                $arrJSONReturn['error'] = true;
            } else {
                $CMS_Users_Type = $arrData[0]["CMS_Users_Type"];
                $CMS_Users_Id = $arrData[0]["CMS_Users_Id"];
                $CMS_Users_Access = array();
                if ($arrData[0]["CMS_Users_Access"]!='') {
                    $CMS_Users_Access = json_decode(base64_decode($arrData[0]["CMS_Users_Access"]), true);
                }
                $CMS_Users_SessionId = $arrData[0]["CMS_Users_SessionId"];
                $dCMS_Users_SessionId = $crypt->decrypt($CMS_Users_SessionId);
                if ($dCMS_Users_SessionId == "") {
                    $arrJSONReturn['message'] = "Invalid password reset data";
                    $arrJSONReturn['error'] = true;
                } else {
                    $arrSession = json_decode($dCMS_Users_SessionId, true);
                    if (!is_array($arrSession)) {
                        $arrJSONReturn['message'] = "Invalid password reset data";
                        $arrJSONReturn['error'] = true;
                    } else {
                        if ($arrSession[0]!=$CMS_Users_Id) {
                            $arrJSONReturn['message'] = "Invalid password reset data";
                            $arrJSONReturn['error'] = true;
                        }
                    }
                }

                if (!isset($arrJSONReturn['error'])) {
                    if ($posData["password"] == '') {
                        $arrInputError[] = "&bull; Password is required";
                    }
                    if ($posData["password_confirm"] == '') {
                        $arrInputError[] = "&bull; Confirm Password is required";
                    }

                    if (count($arrInputError) > 0) {
                        $arrJSONReturn['message'] = implode("<br>", $arrInputError);
                        $arrJSONReturn['error'] = true;
                    } else {
                        if ($posData["password"] != $posData["password_confirm"]) {
                            $arrInputError[] = "&bull; Password does not match the confirm password";
                        } else {
                            if ($posData["password"] != '' && $posData["password_confirm"] != '') {
                                if (strlen($posData["password"]) < 12) {
                                    $arrInputError[] = "&bull; Password must at least 12 characters";
                                }
                                if (!preg_match("#[A-Z]+#", $posData["password"])) {
                                    $arrInputError[] = "&bull; Password must include at least one capital letter";
                                }
                                if (!preg_match("#[a-z]+#", $posData["password"])) {
                                    $arrInputError[] = "&bull; Password must include at least one lower case letter";
                                }
                            }
                        }
                        if (count($arrInputError) > 0) {
                            $arrJSONReturn['message'] = implode("<br>", $arrInputError);
                            $arrJSONReturn['error'] = true;
                        } else {
                            $arrJSONReturn['message'] = "You successfully updated your new password.<br>Please wait while you are redirected to the login page.";
                            $arrJSONReturn['error'] = false;
                            $arrJSONReturn['url'] = $CONFIG['website']['path'] . $CONFIG['cms']['route_name'] . "/login";

                            $this->dbClass->update("cms_users",
                                array(
                                    'CMS_Users_Password'=>$crypt->encrypt($posData["password"]),
                                ),
                                array('CMS_Users_Id'=>$CMS_Users_Id)
                            );
                        }
                    }
                }
            }

            print json_encode($arrJSONReturn);
            exit;
        }

        $this->loadView("cms/cms_reset_password", 1, ['CONFIG'=>$CONFIG, 'arrValidation'=>$arrValidation]);
    }
}