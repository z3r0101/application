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

class cms_login extends BaseController
{
    function __construct()
    {
        parent::__construct();
    }

    function index() {
        global $CONFIG, $CMS_FN_MENU;

        $hasGoogleCaptcha = (isset($CONFIG['website']['google']['g-captcha']['key']) && isset($CONFIG['website']['google']['g-captcha']['secret'])) ? (($CONFIG['website']['google']['g-captcha']['key']!="" && $CONFIG['website']['google']['g-captcha']['secret']!="") ? true : false) : false;

        $tFnLogin = function ($cmsLoginUser, $cmsLoginPass) {
            global $CONFIG, $CMS_FN_MENU;

            $arrJSONReturn = array();

            $arrData = $this->dbClass->select(sprintf("SELECT CMS_Users_Id, CMS_Users_Password, CMS_Users_Type, CMS_Users_Access, IFNULL(CMS_Users_Date_LastLogin, '') AS CMS_Users_Date_LastLogin, IFNULL(CMS_Users_Date_Login, '') AS CMS_Users_Date_Login FROM cms_users WHERE CMS_Users_Name='%s' AND CMS_Users_Status = 1 AND CMS_Users_Website = '%s'", $this->dbClass->mysqli->real_escape_string($cmsLoginUser), $this->dbClass->mysqli->real_escape_string($CONFIG['website']['domain'])));
            if (count($arrData) > 0) {

                $CMS_Users_Id = $arrData[0]["CMS_Users_Id"];
                $CMS_Users_Password = $arrData[0]["CMS_Users_Password"];
                $CMS_Users_Type = $arrData[0]["CMS_Users_Type"];

                $CMS_Users_Access = array();
                if ($arrData[0]["CMS_Users_Access"]!='') {
                    $CMS_Users_Access = json_decode(base64_decode($arrData[0]["CMS_Users_Access"]), true);
                }

                $crypt = new cmsCryptonite();

                #$arrJSONReturn["debug"] = print_r($arrData, true); #sprintf("SELECT * FROM cms_users WHERE CMS_Users_Name='%s' AND CMS_Users_Status = 1 AND CMS_Users_Website = '%s'", $cmsLoginUser, $CONFIG['website']['domain']); #$cmsLoginUser . "::" . $db->mysqli->real_escape_string($cmsLoginUser); #mysqli_real_escape_string($db->mysqli, $cmsLoginUser); #sprintf("SELECT * FROM cms_users WHERE CMS_Users_Name='%s' AND CMS_Users_Status = 1 AND CMS_Users_Website = '%s'", mysqli_real_escape_string($db->mysqli, $cmsLoginUser), mysqli_real_escape_string($db->mysqli, $CONFIG['website']['domain']));

                if ($crypt->decode($CMS_Users_Password) == $cmsLoginPass) {

                    /*$arrSession = array(
                        $crypt->encode($CMS_Users_Id),
                        $crypt->encode(time())
                    );
                    $cmsSession = base64_encode(json_encode($arrSession));*/

                    $arrJSONReturn = cmsLogin($arrData[0]);

                    /*$arrSession = array(
                        $CMS_Users_Id,
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
                    if ($arrData[0]["CMS_Users_Date_Login"] != '') {
                        if (isset($arrData[0]["CMS_Users_Date_LastLogin"])) {
                            $arrUpdateUsers['CMS_Users_Date_LastLogin'] = $arrData[0]["CMS_Users_Date_Login"];
                        }
                    }

                    $this->dbClass->update("cms_users",
                        $arrUpdateUsers,
                        array('CMS_Users_Id'=>$CMS_Users_Id)
                    );

                    if ($CMS_Users_Type == 1) {
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
                    $this->dbClass->insert("cms_users_login",
                        array(
                            'CMS_Users_Id'=>$CMS_Users_Id,
                            'CMS_Users_SessionId'=>$cmsSession,
                            'CMS_Users_Login_DateTime'=>$logDate,
                            'CMS_Users_Login_ClientIP'=>$ipAdd
                        )
                    );*/
                } else {
                    $arrJSONReturn['message'] = "Invalid Username / Password";
                    $arrJSONReturn['error'] = true;
                }

            } else {
                $arrJSONReturn['message'] = "Invalid Username / Password";
                $arrJSONReturn['error'] = true;
            }

            $arrJSONReturn['type'] = 0;
            return $arrJSONReturn;
        };

        $tfnResetPwd = function ($cmsLoginUser) {
            global $CONFIG, $CMS_FN_MENU;
            $arrJSONReturn = array();

            $arrData = $this->dbClass->select(sprintf("SELECT * FROM cms_users WHERE CMS_Users_Name='%s' AND CMS_Users_Status = 1 AND CMS_Users_Website = '%s'", $this->dbClass->mysqli->real_escape_string($cmsLoginUser), $this->dbClass->mysqli->real_escape_string($CONFIG['website']['domain'])));
            if (count($arrData) > 0) {
                $CMS_Users_Id = $arrData[0]["CMS_Users_Id"];

                $to = $arrData[0]["CMS_Users_Name"];
                if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                    $to = $arrData[0]["CMS_Users_Email"];
                }

                $subject = $CONFIG['cms']['title']." : Reset Password";
                $bodySiteTitle = $CONFIG['cms']['title'];
                $bodyName = $arrData[0]["CMS_Users_Name_First"];

                $crypt = new cmsCryptonite();
                $arrSession = array(
                    $CMS_Users_Id,
                    time()
                );
                $cmsSession = $crypt->encode(json_encode($arrSession));
                $this->dbClass->update("cms_users",
                    array(
                        'CMS_Users_SessionId'=>$cmsSession
                    ),
                    array('CMS_Users_Id'=>$CMS_Users_Id)
                );

                $bodyResetLink = "{$CONFIG['website']['url']}/{$CONFIG['cms']['route_name']}/reset-password?data={$cmsSession}";
                $bodyTimeStamp = date("Y-m-d H:i:s");
                $message = <<<HTML
                    <div style="font-family: Tahoma; font-size: 14px;">
                        <p>Dear {$bodyName},</p>
                        <p>This email was sent automatically in response to your request to recover your password. This is done for your protection; only you, the recipient of this email can take the next step in the password recovery process.</p>
                        <p>To reset your password and access your account click on the link below:</p>
                        <p><a href="{$bodyResetLink}">{$bodyResetLink}</a></p>
                        <p>{$bodySiteTitle}</p>
                        <p style="font-size: 10px">{$bodyTimeStamp}</p>
                    </div>
HTML;

                $arrEmailHeader = array();
                $arrEmailHeader[] = "From: noreply@{$CONFIG['website']['domain']}";
                $arrEmailHeader[] = "MIME-Version: 1.0";
                $arrEmailHeader[] = "Content-type: text/html; charset=utf-8";

                // send email
                if (isset($CONFIG['cms']['smtp']['host'])) {
                    require_once(VENDORSPATH . 'PHPMailer/src/Exception.php');
                    require_once(VENDORSPATH . 'PHPMailer/src/PHPMailer.php');
                    require_once(VENDORSPATH . 'PHPMailer/src/SMTP.php');

                    $mail = new PHPMailer\PHPMailer\PHPMailer();
                    $mail->IsSMTP(); // enable SMTP
                    if (isset($CONFIG['cms']['smtp']['debug'])) $mail->SMTPDebug = 1; // debugging: 1 = errors and messages, 2 = messages only
                    $mail->SMTPAuth = true; // authentication enabled
                    if (isset($CONFIG['cms']['smtp']['secure'])) $mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for Gmail
                    $mail->Host = $CONFIG['cms']['smtp']['host'];
                    if (isset($CONFIG['cms']['smtp']['port'])) $mail->Port = $CONFIG['cms']['smtp']['port']; // or 587
                    $mail->IsHTML(true);
                    $mail->Username = $CONFIG['cms']['smtp']['username'];
                    $mail->Password = $CONFIG['cms']['smtp']['password'];
                    if (isset($CONFIG['cms']['email']['from'])) $mail->SetFrom($CONFIG['cms']['email']['from']);
                    $mail->Subject = $subject;
                    $mail->Body = $message;
                    $mail->AddAddress($to);

                    if (!$mail->Send()) {
                        #echo "Mailer Error: " . $mail->ErrorInfo;
                        $arrRet['error'] = $mail->ErrorInfo;
                    } else {
                        #echo "Message has been sent";
                    }
                } else {
                    mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $message, implode("\r\n", $arrEmailHeader));
                }

                #DEBUG
                if ($CONFIG['environment'] == 'development') {
                    file_put_contents(SITEROOTPATH . "uploads/debug.txt", date("Y-m-d H:i:s") . "\nTo:{$to}\nSubject:{$subject}\n{$message}\n".implode("\r\n", $arrEmailHeader)."\n\n\n" . PHP_EOL, FILE_APPEND);
                }

                $arrJSONReturn['message'] = "A message has been sent to {$arrData[0]["CMS_Users_Name"]} with instructions to reset your password.";
                $arrJSONReturn['type'] = 1;
                $arrJSONReturn['error'] = false;
            } else {
                $arrJSONReturn['message'] = "Invalid email address";
                $arrJSONReturn['type'] = 0;
                $arrJSONReturn['error'] = true;
            }
            return $arrJSONReturn;
        };

        if (isset($_POST['cmsPostData'])) {
            $posData = json_decode(base64_decode($_POST['cmsPostData']), true);

            if ($posData['type'] == 0) {
                $cmsLoginUser = $posData['username'];
                $cmsLoginPass = $posData['password'];
                $arrJSONReturn = array();

                if ($hasGoogleCaptcha) {
                    $cmsReCaptcha = (isset($posData['g_recaptcha_response'])) ? $posData['g_recaptcha_response'] : '';
                    require(VENDORSPATH . 'recaptcha/autoload.php');
                    $recaptcha = new \ReCaptcha\ReCaptcha($CONFIG['website']['google']['g-captcha']['secret']);
                    $resp = $recaptcha->verify($cmsReCaptcha, $_SERVER['REMOTE_ADDR']);

                    if ($resp->isSuccess()) {
                        $arrJSONReturn = $tFnLogin($cmsLoginUser, $cmsLoginPass);
                    } else {
                        $arrJSONReturn['message'] = "Invalid Captcha";
                        $arrJSONReturn['type'] = 0;
                        $arrJSONReturn['error'] = true;
                    }
                } else {
                    $arrJSONReturn = $tFnLogin($cmsLoginUser, $cmsLoginPass);
                }

                print json_encode($arrJSONReturn);
            } else if ($posData['type'] == 1) {
                #RESET PASSWORD

                $cmsLoginUser = $posData['username'];
                if ($hasGoogleCaptcha) {
                    $cmsReCaptcha = (isset($posData['g_recaptcha_response'])) ? $posData['g_recaptcha_response'] : '';
                    require(VENDORSPATH . 'recaptcha/autoload.php');
                    $recaptcha = new \ReCaptcha\ReCaptcha($CONFIG['website']['google']['g-captcha']['secret']);
                    $resp = $recaptcha->verify($cmsReCaptcha, $_SERVER['REMOTE_ADDR']);
                    if ($resp->isSuccess()) {
                        $arrJSONReturn = $tfnResetPwd($cmsLoginUser);
                    } else {
                        $arrJSONReturn['message'] = "Invalid Captcha";
                        $arrJSONReturn['type'] = 0;
                        $arrJSONReturn['error'] = true;
                    }
                } else {
                    $arrJSONReturn = $tfnResetPwd($cmsLoginUser);
                }
                print json_encode($arrJSONReturn);
            }
            exit;
        }

        #$this->loadView("cms/cms_login");

        #include VENDORSPATH.'BladeOne/BladeOne.php';
        #$blade=new \eftec\bladeone\BladeOne(APPPATH.'views', null, \eftec\bladeone\BladeOne::MODE_DEBUG);
        #echo $blade->run("cms/cms_login.php", ['CONFIG'=>$CONFIG]);

        $this->loadView("cms/cms_login", 1, ['CONFIG'=>$CONFIG]);
    }
}