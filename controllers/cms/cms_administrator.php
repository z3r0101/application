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

class cms_administrator extends BaseControllerCMS {
    private $cms_temp_data = "";

    function __construct() {
        parent::__construct();
    }

    function index() {
        #print 'You are in root directory of administrator';
        header("location: ".WEBSITE_PATH.CONFIG_CMS_ROUTE_NAME."/administrator/users/list");
    }

    function users($data) {
        #$this->menuSubIndex(0);
        $this->renderPage(
            function () {
                global $CONFIG;

                #LIST INITIALIZE

                if (isset($CONFIG['cms']['administrator']['list_ini_inc'])) {
                    if (file_exists(APPPATH.'controllers/cms/'.$CONFIG['cms']['administrator']['list_ini_inc'])) {
                        include_once(APPPATH.'controllers/cms/'.$CONFIG['cms']['administrator']['list_ini_inc']);
                    }
                }

                $this->attributeById("users", "table_select",
                    "
                        SELECT
                            CMS_Users_Id,
                            CMS_Users_Name,
                            CONCAT(CMS_Users_Name_First, ' ',CMS_Users_Name_Last) AS CMS_Users_FullName,
                            CMS_Users_Date_LastActivity,
                            CMS_Users_Date_Login,
                            CMS_Editor_IP,
                            CMS_Users_Date_Created,
                            CMS_Users_Status,
                            CMS_Users_Type
                        FROM
                           cms_users
                        WHERE
                           CMS_Users_Website = '{$CONFIG['website']['domain']}' AND
                           CMS_Users_Status = 1 AND  
                           CMS_Users_Type >= 0
                    "
                );

                $this->dataTable("users", "table_name", "cms_users");
                $CMS_Users_Type = (isset($_GET['CMS_Users_Type'])) ? intval($_GET['CMS_Users_Type']) : 0;
                $this->dataTableFormatter("users", "CMS_Users_Date_LastActivity",
                    function ($d, $row) {
                        return ($d!='') ? date("F d Y h:i A", strtotime($d)) : '';
                    }
                );
                $this->dataTableFormatter("users", "CMS_Users_Type",
                    function ($d, $row) use ($CONFIG) {
                        $tType = '';
                        if (isset($CONFIG['cms']['role'])) {
                            if (isset($CONFIG['cms']['role']['type'])) {
                                if ((isset($CONFIG['cms']['role']['type'][$d]))) {
                                    $tType = (isset($CONFIG['cms']['role']['type'][$d]['name'])) ? $CONFIG['cms']['role']['type'][$d]['name'] : '';
                                }
                            }
                        }

                        return $tType;
                    }
                );
                $this->dataTableFormatter("users", "CMS_Users_Status",
                    function ($d, $row) {
                        return ($d==1) ? 'Active' : 'Hold';
                    }
                );
                #$this->dataTableWhere("users", "CMS_Users_Type = 1");
            },
            function () {
                #POST INITIALIZE
                global $CONFIG, $CMS_FN_MENU;

                $tCrypt = new cmsCryptonite();

                if (isset($CONFIG['cms']['administrator']['post_ini_inc'])) {
                    if (file_exists(APPPATH.'controllers/cms/'.$CONFIG['cms']['administrator']['post_ini_inc'])) {
                        include_once(APPPATH.'controllers/cms/'.$CONFIG['cms']['administrator']['post_ini_inc']);
                    }
                }

                if (isset($this->requestSlug[0])) {
                    if (CMS_Users_Name != 'dev') {
                        #$this->deleteTagById("passwordOpener");
                        #$this->attributeById("CMS_Users_Password", "container-obj-class", "");
                    }
                }


                if (isset($_GET['cms-access'])) {
                    $sectionXML =  $CMS_FN_MENU(); #simplexml_load_string(file_get_contents(APPPATH.'views/cms/layout/cms_sections.xml'), "SimpleXMLElement", LIBXML_NOCDATA);

                    print '<table class="table cms-access-table">';
                    foreach ($sectionXML->children() as $tagObjects) {
                        $tLink = strval($tagObjects['link']);
                        $arrLink = explode('/', $tLink);

                        $sectionId = $arrLink[0].(isset($arrLink[1]) ? ':'.$arrLink[1] : '');

                        $tMenu = (isset($tagObjects['menu'])) ? filter_var($tagObjects['menu'], FILTER_VALIDATE_BOOLEAN) : true;

                        while($arrLink[count($arrLink)-1]=='post' || $arrLink[count($arrLink)-1]=='list' || is_numeric($arrLink[count($arrLink)-1])) {
                            unset($arrLink[count($arrLink)-1]);
                        }
                        $tId = implode(':', $arrLink);

                        if ($tMenu) {
                            $tArrRole = explode('|', strval($tagObjects['role']));

                            if (in_array('editor', $tArrRole, true)) {

                                $arrAccessOptions = array();
                                foreach($tagObjects->children() as $tagSubObjects) {
                                    if (strval($tagSubObjects->getName()) == 'cms_access') {
                                        foreach($tagSubObjects->children() as $cmsAccessOption) {
                                            $tSId = $tId.'_'.((isset($cmsAccessOption['id'])) ? $cmsAccessOption['id'] : cmsTools::makeSlug(strval($cmsAccessOption)));
                                            $arrAccessOptions[] = '<li><label><input class="cms-access-parent-options" type="checkbox" id="'.$tSId.'" parent-id="'.$tId.'" onclick="cmdFnAccessOptions(this, \''.$tId.'\')"> '.strval($cmsAccessOption).'</label></li>';
                                        }
                                    }
                                }

                                print '<tr>
                                    <td width="20%">
                                        <label><input class="cms-access-parent" type="checkbox" id="' . $tId . '" onclick="cmdFnAccess(this)"> ' . strval($tagObjects['title']) . '</label>
                                    </td>
                                    <td width="80%">
                                        '.((count($arrAccessOptions)> 0) ? '<ul class="cms-access-option">'.implode('',$arrAccessOptions).'</ul>' : '').'
                                    </td>
                                </tr>';

                                foreach($tagObjects->children() as $tagSubObjects) {
                                    if (strval($tagSubObjects->getName()) == 'sub') {
                                        $tSubArrRole = explode('|', strval($tagSubObjects['role']));
                                        $arrLink = explode('/', strval($tagSubObjects['link']));
                                        $sectionSubId = $arrLink[0].(isset($arrLink[1]) ? ':'.$arrLink[1] : '');

                                        $arrSubAccessOptions = array();
                                        foreach($tagSubObjects as $cmsAccessOption) {
                                            if (strval($cmsAccessOption->getName()) == 'cms_access') {
                                                foreach($cmsAccessOption->children() as $cmsSubAccessOption) {
                                                    $tSId = $sectionSubId . '_' . ((isset($cmsSubAccessOption['id'])) ? $cmsSubAccessOption['id'] : cmsTools::makeSlug(strval($cmsSubAccessOption)));
                                                    $arrSubAccessOptions[] = '<li><label><input class="cms-access-items-options" type="checkbox" id="'.$tSId.'" item-parent-id="'.$tId.'" onclick="cmdFnAccessOptions(this, \''.$tId.'\', true)"> '.strval($cmsSubAccessOption).'</label></li>';
                                                }
                                            }
                                        }

                                        if (in_array('editor', $tSubArrRole, true)) {
                                            print '<tr>
                                            <td width="20%" style="padding-left: 40px">
                                                <label><input class="cms-access-items" type="checkbox" id="' . $sectionSubId . '" item-parent-id="' . $tId . '" onclick="cmdFnAccess(this, true)"> ' . strval($tagSubObjects['title']) . '</label>
                                            </td>
                                            <td width="80%">
                                                ' . ((count($arrSubAccessOptions) > 0) ? '<ul class="cms-access-option">' . implode('', $arrSubAccessOptions) . '</ul>' : '') . '
                                            </td>
                                            </tr>';
                                        }

                                    }
                                }
                            }
                        }
                    }
                    print '</tr>';

                    exit;
                }

                $arrCMS_Users_Access = array();
                $sectionXML =  $CMS_FN_MENU(); #simplexml_load_string(file_get_contents(APPPATH.'views/cms/layout/cms_sections.xml'), "SimpleXMLElement", LIBXML_NOCDATA);
                foreach ($sectionXML->children() as $tagObjects) {
                    $sectionType = (!isset($tagObjects['type'])) ? $tagObjects['type'] : 'cms';
                    $arrLink = explode('/', strval($tagObjects['link']));
                    $arrRole = explode('|', strval($tagObjects['role']));
                    $arrLinkMethodAccess = explode('|', strval($tagObjects['link_method_access']));

                    if (isset($arrLink[1])) if ($arrLink[1] == 'post') unset($arrLink[1]);
                    if (isset($arrLink[1])) if ($arrLink[1] == 'list') unset($arrLink[1]);

                    $sectionId = $arrLink[0] . (isset($arrLink[1]) ? ':' . $arrLink[1] : '');

                    if (in_array('editor', $arrRole, true)) {
                        $arrCMS_Users_Access[$sectionId]['options'] = array('view' => false, 'url' => $CONFIG['website']['path'] . $CONFIG['cms']['route_name'] . '/' . strval($tagObjects['link']), 'link_method_access' => $arrLinkMethodAccess, 'access_options' => array());

                        $arrCMS_Users_Access[$sectionId]['items'] = array();
                        $arrSubItems = array();
                        foreach ($tagObjects->children() as $tagSubObjects) {

                            if (strval($tagSubObjects->getName()) == 'sub') {
                                $arrLink = explode('/', strval($tagSubObjects['link']));
                                $sectionSubId = $arrLink[0] . (isset($arrLink[1]) ? ':' . $arrLink[1] : '');

                                $arrAccessOptions = array();
                                foreach ($tagSubObjects->children() as $cmsSubItems) {

                                    if (strval($cmsSubItems->getName()) == 'cms_access') {
                                        foreach ($cmsSubItems->children() as $cmsAccessOption) {
                                            $tId = $sectionSubId . '_'. ((isset($cmsAccessOption['id'])) ? $cmsAccessOption['id'] : cmsTools::makeSlug(strval($cmsAccessOption)));
                                            $arrAccessOptions[$tId] = array('selected' => false, 'caption' => strval($cmsAccessOption));

                                        }
                                    }

                                }

                                $arrSubItems[$sectionSubId]['options'] = array('views' => false, 'url' => $CONFIG['website']['path'] . $CONFIG['cms']['route_name'] . '/' . strval($tagSubObjects['link']), 'access_options'=>$arrAccessOptions);
                            }

                            $arrSubAccessOptions = array();
                            if (strval($tagSubObjects->getName()) == 'cms_access') {

                            }
                            $arrCMS_Users_Access[$sectionId]['options']['access_options'] = $arrSubAccessOptions;

                        }
                        $arrCMS_Users_Access[$sectionId]['items'] = $arrSubItems;
                        #print_r($arrLink);

                        $arrAccessOptions = array();
                        foreach ($tagObjects->children() as $tagSubObjects) {
                            if (strval($tagSubObjects->getName()) == 'cms_access') {
                                foreach ($tagSubObjects->children() as $cmsAccessOption) {
                                    $tId = $sectionId . '_' . ((isset($cmsAccessOption['id'])) ? $cmsAccessOption['id'] : cmsTools::makeSlug(strval($cmsAccessOption)));
                                    $arrAccessOptions[$tId] = array('selected' => false, 'caption' => strval($cmsAccessOption));
                                }
                            }
                        }
                        $arrCMS_Users_Access[$sectionId]['options']['access_options'] = $arrAccessOptions;
                    }
                }
                if (count($arrCMS_Users_Access)>0) {
                    $this->attributeById("CMS_Users_Access_Temp", "value", base64_encode(json_encode($arrCMS_Users_Access)));
                }

                if ($this->postType == 0) {
                    if ($_POST) {
                        $arrData = $this->dbClass->select(sprintf("SELECT COUNT(*) AS dCount FROM cms_users WHERE CMS_Users_Name = '%s' AND CMS_Users_Website = '{$CONFIG['website']['domain']}'", $this->dbClass->mysqli->real_escape_string(trim($this->cmsPost['primary']['CMS_Users_Name']))));
                        if ($arrData[0]["dCount"]>0) {
                            $this->alert[] = "Email is already taken";
                        }

                        if (!filter_var($this->cmsPost['primary']['CMS_Users_Name'], FILTER_VALIDATE_EMAIL)) {
                            $this->alert[] = "Invalid email address";
                        } else {
                            $this->cmsPost['primary']['CMS_Users_Email'] = $this->cmsPost['primary']['CMS_Users_Name'];
                        }

                        if ($this->cmsPost['primary']['CMS_Users_Password_Input'] != $this->cmsPost['primary']['CMS_Users_Password_Confirm']) {
                            $this->alert[] = "Password does not match the confirm password.";
                        } else {
                            if ($this->cmsPost['primary']['CMS_Users_Password_Input'] != '' && $this->cmsPost['primary']['CMS_Users_Password_Confirm'] != '') {
                                if(strlen($this->cmsPost['primary']['CMS_Users_Password_Input']) < 12) {
                                    $this->alert[] = "Password must at least 12 characters";
                                }
                                if (!preg_match("#[A-Z]+#", $this->cmsPost['primary']['CMS_Users_Password_Input'])) {
                                    $this->alert[] = "Password must include at least one capital letter";
                                }
                                if (!preg_match("#[a-z]+#", $this->cmsPost['primary']['CMS_Users_Password_Input'])) {
                                    $this->alert[] = "Password must include at least one lower case letter";
                                }
                                /*elseif(!(preg_match('#[0-9]#', $this->cmsPost['primary']['CMS_Users_Password_Input']))) {
                                    $this->alert[] = "Password must contain at least one number";
                                }*/
                            }
                        }

                        if (count($this->alert)==0) {
                            #$this->cmsPost['primary']['CMS_Users_Type'] = 1;
                            $this->cmsPost['primary']['CMS_Users_Password'] = $tCrypt->encode($this->cmsPost['primary']['CMS_Users_Password_Input']);
                            $this->cmsPost['primary']['CMS_Users_Status'] = 1;
                            $this->cmsPost['primary']['CMS_Users_Date_Created'] = date("Y-m-d");
                            $this->cmsPost['primary']['CMS_Users_Website'] = $CONFIG['website']['domain'];
                        }
                    }

                    $this->attributeById("CMS_Users_Password", "visible", "false");
                    $this->attributeById("CMS_Users_Status", "visible", "false");
                } else {
                    if ($_POST) {
                        $this->cmsPost['primary']['CMS_Users_Password'] = $tCrypt->encode($this->cmsPost['primary']['CMS_Users_Password']);
                    }

                    $this->attributeById("CMS_Users_Name", "readonly", "true");
                    $this->attributeById("CMS_Users_Password_Input", "visible", "false");
                    $this->attributeById("CMS_Users_Password_Confirm", "visible", "false");
                }

                $arrType = array();

                if (!isset($this->requestSlug[0])) {
                    $arrType[] = '<option value="">Select User Role</option>';
                }

                if (isset($this->requestSlug[1])) {
                    if ($this->requestSlug[1] == 'sso') {
                        $this->menuSubIndex(0);
                        $this->deleteTagById("CMS_Users_Password_Input");
                        $this->deleteTagById("CMS_Users_Password_Confirm");
                        $this->deleteTagById("CMS_Users_Password");
                        $this->deleteTagById("CMS_Users_Status");
                        $arrType[] = '<option value="-1">Select User Role</option>';
                        $this->insertAfter("CMS_Users_Name", '<control type="hidden" id="CMS_Users_Status_SSO"></control>');
                        $this->insertTagById("cmsUsersButtons",
                            "
                                <button type=\"custom\" caption=\"Approve\" class=\"btn-primary\" onclick=\"cmsSSOAction(0)\" />
                                <button type=\"custom\" caption=\"Reject\" class=\"btn-secondary\" onclick=\"cmsSSOAction(1)\" />
                                <button type=\"custom\" caption=\"Cancel\" class=\"btn-secondary\" onclick=\"cmsSSOAction(2)\" />
                            "
                        );
                        $this->attributeById("cmsUsersBody", "saveclose", "false");

                        $this->postSubmittedEvent['end'] = function ($pArr) {
                            $this->dbClass->update("cms_users",
                                array(
                                    'CMS_Users_Status'=>1
                                ),
                                $pArr["value"]
                            );
                        };
                    }
                } else {
                    $this->insertTagById("cmsUsersButtons",
                        "
                            <button type=\"save\" />
                            <button type=\"cancel\" />
                        "
                    );
                }

                if (isset($CONFIG['cms']['role'])) {
                    if (isset($CONFIG['cms']['role']['type'])) {
                        foreach($CONFIG['cms']['role']['type'] as $typeIndex => $typeData) {
                            $arrType[] = '<option value="'.$typeIndex.'">'.$typeData['name'].'</option>';
                        }
                    }
                }

                if (count($arrType) > 0) {
                    $this->insertTagById("CMS_Users_Type",
                        implode('', $arrType)
                    );
                }
            },
            function () {
                #POST AFTER INITIALIZE
                if ($this->postType == 1) {
                    $tCrypt = new cmsCryptonite();
                    $this->postFields[0]['CMS_Users_Password'] = $tCrypt->decode($this->postFields[0]['CMS_Users_Password']);
                }
            },
            function () {
                #POST SUBMIT COMPLETE

            }
        );
        #header("location: {$CONFIG['website']['path']}{$CONFIG['cms']['route_name']}/administrator/users/list");
    }

    function my_account() {
        $this->menuSubIndex(0);
        $this->renderPage(
            function () {
                #LIST INITIALIZE
            },
            function () {
                #POST INITIALIZE
                global $CONFIG;

                $this->postRedirect = $CONFIG['website']['path'].$CONFIG['cms']['route_name'].'/administrator/my-account/post';

                $arrData = $this->dbClass->select(sprintf("SELECT * FROM cms_users_login WHERE CMS_Users_SessionId = '%s'", $this->dbClass->mysqli->real_escape_string(CMS_Users_SessionId)));
                if (count($arrData) > 0) {
                    if ($arrData[0]["CMS_Users_Login_Type"] == 1) {
                        $this->deleteTagById("CMS_Users_Password_Input_Current");
                        $this->deleteTagById("CMS_Users_Password_Input");
                        $this->deleteTagById("CMS_Users_Password_Confirm");
                    }
                }

                if ($_POST) {
                    if (isset($this->cmsPost['primary']['CMS_Users_Password_Input_Current'])) {
                        $tCrypt = new cmsCryptonite();
                        $arrData = $this->dbClass->select("SELECT * FROM cms_users WHERE CMS_Users_Id = " . CMS_Users_Id);
                        $CMS_Users_Password = $tCrypt->decode($arrData[0]["CMS_Users_Password"]);

                        if ($CMS_Users_Password == $this->cmsPost['primary']['CMS_Users_Password_Input_Current']) {
                            if ($this->cmsPost['primary']['CMS_Users_Password_Input'] != $this->cmsPost['primary']['CMS_Users_Password_Confirm']) {
                                $this->alert[] = "Password does not match the confirm password.";
                            } else {
                                if ($this->cmsPost['primary']['CMS_Users_Password_Input'] != '' && $this->cmsPost['primary']['CMS_Users_Password_Confirm'] != '') {
                                    if (strlen($this->cmsPost['primary']['CMS_Users_Password_Input']) < 12) {
                                        $this->alert[] = "Password must at least 12 characters";
                                    }
                                    if (!preg_match("#[A-Z]+#", $this->cmsPost['primary']['CMS_Users_Password_Input'])) {
                                        $this->alert[] = "Password must include at least one capital letter";
                                    }
                                    if (!preg_match("#[a-z]+#", $this->cmsPost['primary']['CMS_Users_Password_Input'])) {
                                        $this->alert[] = "Password must include at least one lower case letter";
                                    }
                                    /*if(strlen($this->cmsPost['primary']['CMS_Users_Password_Input']) < 8) {
                                        $this->alert[] = "Password must be more than 8 characters in length";
                                    }
                                    elseif(!(preg_match('#[0-9]#', $this->cmsPost['primary']['CMS_Users_Password_Input']))) {
                                        $this->alert[] = "Password must contain at least one number";
                                    }*/
                                }
                            }
                        } else {
                            $this->alert[] = "Invalid Current Password.";
                        }

                        if (count($this->alert) == 0) {
                            $this->cmsPost['primary']['CMS_Users_Password'] = $tCrypt->encode($this->cmsPost['primary']['CMS_Users_Password_Input']);
                        }
                    }
                }

                $this->requestSlug[0] = CMS_Users_Id;
                $this->attributeById("CMS_Users_Name", "readonly", "true");

                if (CMS_Users_Type == 1) {
                    $this->attributeById("CMS_Users_FullName", "visible", "false");
                }

            },
            function () {
                #POST AFTER INITIALIZE
            },
            function () {
                #POST SUBMIT COMPLETE
            }
        );
    }

    function sso_approval() {
        $this->renderPage(
            function () {
                global $CONFIG;

                $arrData = $this->dbClass->select("SELECT * FROM cms_users WHERE CMS_Users_Website = '{$CONFIG['website']['domain']}' AND CMS_Users_Status_SSO = 1");
                if (count($arrData) == 0) {
                    header("location: {$CONFIG['website']['path']}{$CONFIG['cms']['route_name']}/administrator/users/list");
                    exit;
                }

                $this->attributeById("users", "table_select",
                    "
                        SELECT
                            CMS_Users_Id,
                            CMS_Users_Name,
                            CONCAT(CMS_Users_Name_First, ' ',CMS_Users_Name_Last) AS CMS_Users_FullName,
                            CMS_Users_Date_LastActivity,
                            CMS_Users_Date_Login,
                            CMS_Editor_IP,
                            CMS_Users_Date_Created,
                            CMS_Users_Status,
                            CMS_Users_Type
                        FROM
                           cms_users
                        WHERE
                           CMS_Users_Website = '{$CONFIG['website']['domain']}' AND
                           CMS_Users_Status_SSO = 1 
                    "
                );
            },
            function () {
                global $CONFIG;
            }
        );
    }

    function blocked_users() {
        $this->renderPage(
            function () {
                global $CONFIG;

                if (isset($_POST['cmsUserUnblock'])) {
                    $arrPost = json_decode(base64_decode($_POST['cmsUserUnblock']), true);
                    $this->dbClass->update("cms_users",
                        array(
                            'CMS_Users_Status'=>1
                        ),
                        $arrPost['id']
                    );
                    exit;
                }

                $arrData = $this->dbClass->select("SELECT * FROM cms_users WHERE CMS_Users_Website = '{$CONFIG['website']['domain']}' AND CMS_Users_Status_SSO IN (0,2) AND CMS_Users_Status = 0");
                if (count($arrData) == 0) {
                    header("location: {$CONFIG['website']['path']}{$CONFIG['cms']['route_name']}/administrator/users/list");
                    exit;
                }

                $this->attributeById("users", "table_select",
                    "
                        SELECT
                            CMS_Users_Id,
                            CMS_Users_Name,
                            CONCAT(CMS_Users_Name_First, ' ',CMS_Users_Name_Last) AS CMS_Users_FullName,
                            CMS_Users_Date_LastActivity,
                            CMS_Users_Date_Login,
                            CMS_Editor_IP,
                            CMS_Users_Date_Created,
                            CMS_Users_Status,
                            CMS_Users_Type
                        FROM
                           cms_users
                        WHERE
                           CMS_Users_Website = '{$CONFIG['website']['domain']}' AND
                           CMS_Users_Status_SSO IN (0,2) AND 
                           CMS_Users_Status = 0 
                    "
                );
                $this->dataTableFormatter("users", "CMS_Users_Status",
                    function ($d, $row) {
                        return ($d==1) ? 'Active' : 'Hold';
                    }
                );
            },
            function () {
                global $CONFIG;
            }
        );
    }
}