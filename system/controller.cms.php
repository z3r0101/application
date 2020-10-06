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

class BaseControllerCMS extends BaseController {
    public $cmsSelectedMethod = '';
    public $cmsFormType = '';
    public $cmsActivePath = '';
    public $cmsActiveParentPath = '';

    #region -- PROPERTIES --
    public $dbClass = null;

    public $cmsRole = array('admin', 'editor');

    public $primaryId = array('name'=>'', 'value'=>0);
    public $cmsPost = NULL;

    public $requestSlug = array();

    public $selectedClass = "";
    public $selectedMethod = "";
    public $selectedUrlClass = "";
    public $selectedUrlMethod = "";
    public $selectedUrlPath = "";
    public $selectedMethodParent = "";
    public $selectedFormType = "";
    public $selectedPath = "";
    public $selectedParentPath = "";
    public $formLayoutFile = "";

    public $menuSubIndex = NULL;

    public $alert = array();

    public $formLayoutData = NULL;

    public $postType = NULL;
    public $postRedirect = "";
    public $postFields = array();
    public $postRepeaterFields = array();
    public $postSubmittedEvent = array('start'=>null, 'end'=>null);

    public $table_name = null;

    public $CMS_Users_Access = null;

    public $initFormLayout = array('list'=>'', 'post'=>'');
    #endregion

    public $dataTableSelected = "";
    public $dataTableSelectFormatter = array();

    private $dataTable = array();
    private $dataTableFormatter = array();
    private $dataTableDeleteCallback = array();
    private $dataTableAction = array();
    private $dataTableButtonPost = array();

    public $batchUploadEvent = array('start'=>null, 'end'=>null);

    private $error = array();

    function __construct() {
        global $CONFIG, $routes, $CMS_FN_MENU;

        $pageCMSRoutePath = $CONFIG['cms']['route_name'] . '/';
        $_pageArrPath = explode('/', substr(PATH_INFO,1));
        $pageUrlClass = implode('/',$_pageArrPath);
        $pageUrlClass = explode('/', $pageUrlClass);
        array_shift($pageUrlClass);
        $pageUrl = $pageUrlClass;
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
        $pageUrlSelected = array();
        foreach($pageUrlClass as $Index => $Name) {
            $pageUrlSelected[$Name] = $pageUrl[$Index];
        }
        $this->selectedUrlClass = $pageUrlSelected[get_class($this)];

        parent::__construct();

        $this->dbClass = new cmsDatabaseClass();

        #AUTHENTICATION
        if (isset($_GET['cms-token'])) {
            $_SESSION[$CONFIG['cookie']['prefix']."_cms_session"] = $_GET['cms-token'];
        }

        if (isset($_SESSION[$CONFIG['cookie']['prefix']."_cms_session"])) {

            $cmsSessionError = false;
            $cmsSessionTimeout = false;
            $cmsSession = $_SESSION[$CONFIG['cookie']['prefix']."_cms_session"];
            if ($cmsSession!="") {
                $tQuery = sprintf(
                    "
                      SELECT 
                          cms_users.CMS_Users_Id, 
                          cms_users.CMS_Users_Name_First, 
                          cms_users.CMS_Users_Name_Last, 
                          cms_users.CMS_Users_Name, 
                          cms_users.CMS_Users_Type, 
                          IFNULL(cms_users.CMS_Users_Date_LastLogin, '') AS CMS_Users_Date_LastLogin,
                          cms_users_login.CMS_Users_Login_Type,
                          cms_users_login.CMS_Users_Login_SSO_Id
                      FROM cms_users 
                      INNER JOIN cms_users_login ON cms_users_login.CMS_Users_Id = cms_users.CMS_Users_Id
                      WHERE 
                          cms_users.CMS_Users_Status = 1 and 
                          cms_users_login.CMS_Users_SessionId='%s' AND 
                          cms_users.CMS_Users_Website = '%s'",
                    mysqli_real_escape_string($this->dbClass->mysqli, $cmsSession),
                    mysqli_real_escape_string($this->dbClass->mysqli, $CONFIG['website']['domain'])
                );
                $arrData = $this->dbClass->select($tQuery);
                if (count($arrData) > 0) {
                    $crypt = new cmsCryptonite();
                    #$cmsArrSession = json_decode(base64_decode($cmsSession));
                    $cmsArrSession = json_decode($crypt->decode($cmsSession));

                    if (is_array($cmsArrSession)) {
                        if (isset($cmsArrSession[0])) {
                            $dateActivity = date("Y-m-d H:i:s");

                            $arrData = $this->dbClass->select(
                                sprintf(
                                "
                                SELECT 
                                    cms_users.CMS_Users_Id, 
                                    cms_users.CMS_Users_Name_First, 
                                    cms_users.CMS_Users_Name_Last, 
                                    cms_users.CMS_Users_Name, 
                                    cms_users.CMS_Users_Type, 
                                    cms_users.CMS_Users_Super_Admin,
                                    IFNULL(cms_users.CMS_Users_Date_LastLogin, '') AS CMS_Users_Date_LastLogin,
                                    cms_users.CMS_Users_Access,
                                    IFNULL(CMS_Users_Date_Login, '') AS CMS_Users_Date_Login,
                                    cms_users_login.CMS_Users_Login_Type,
                                    cms_users_login.CMS_Users_Login_SSO_Id
                                FROM cms_users 
                                    INNER JOIN cms_users_login ON cms_users_login.CMS_Users_Id = cms_users.CMS_Users_Id
                                WHERE 
                                    cms_users.CMS_Users_Status = 1 and 
                                    cms_users_login.CMS_Users_SessionId='%s' AND 
                                    cms_users.CMS_Users_Website = '%s' AND 
                                    cms_users.CMS_Users_Id = %d",
                                    mysqli_real_escape_string($this->dbClass->mysqli, $cmsSession),
                                mysqli_real_escape_string($this->dbClass->mysqli, $CONFIG['website']['domain']),
                                mysqli_real_escape_string($this->dbClass->mysqli, $cmsArrSession[0])
                                )
                            );
                            /*$arrData = $this->dbClass->select(
                                sprintf("SELECT * FROM cms_users WHERE CMS_Users_Status = 1 and CMS_Users_SessionId='%s' AND CMS_Users_Website = '%s' AND CMS_Users_Id = %d",
                                    mysqli_real_escape_string($this->dbClass->mysqli, $cmsSession),
                                    mysqli_real_escape_string($this->dbClass->mysqli, $CONFIG['website']['domain']),
                                    mysqli_real_escape_string($this->dbClass->mysqli, $crypt->decode($cmsArrSession[0]))
                                )
                            );*/
                            if (count($arrData) == 0) {
                                $cmsSessionError = true;
                            } else {
                                if ($arrData[0]["CMS_Users_Date_Login"] != '' && isset($CONFIG['cms']['login_timeout'])) {
                                    $tmLastLogin = strtotime($arrData[0]["CMS_Users_Date_Login"]);
                                    if (time() - $tmLastLogin > $CONFIG['cms']['login_timeout']) {
                                        $_SESSION[$CONFIG['cookie']['prefix']."_cms_session"] = "";
                                        unset($_SESSION[$CONFIG['cookie']['prefix']."_cms_session"]);
                                        $cmsSessionError = true;
                                        $cmsSessionTimeout = true;
                                    }
                                }

                                if (!$cmsSessionError) {

                                    $retUpdate = $this->dbClass->update(
                                        "cms_users",
                                        array(
                                            'CMS_Users_Date_LastActivity' => $dateActivity
                                        ),
                                        $arrData[0]['CMS_Users_Id']
                                    );
                                    if (!defined("CMS_Users_Id")) define('CMS_Users_Id', $arrData[0]['CMS_Users_Id']);
                                    if (!defined("CMS_Users_FullName")) define('CMS_Users_FullName', $arrData[0]['CMS_Users_Name_First'] . " " . $arrData[0]['CMS_Users_Name_Last']);
                                    if (!defined("CMS_Users_FullNameL")) define('CMS_Users_FullNameL', $arrData[0]['CMS_Users_Name_Last'] . ", " . $arrData[0]['CMS_Users_Name_First']);
                                    if (!defined("CMS_Users_Super_Admin")) define('CMS_Users_Super_Admin', $arrData[0]['CMS_Users_Super_Admin']);
                                    if (!defined("CMS_Users_Type")) define('CMS_Users_Type', $arrData[0]['CMS_Users_Type']);
                                    if (!defined("CMS_Users_Name")) define('CMS_Users_Name', $arrData[0]['CMS_Users_Name']);
                                    if (!defined("CMS_Users_SessionId")) define('CMS_Users_SessionId', $cmsSession);
                                    #if (!defined("CMS_Users_Access")) define('CMS_Users_Access', ($arrData[0]['CMS_Users_Access']!='') ? json_decode(base64_decode($arrData[0]['CMS_Users_Access']), true) : array());
                                    $this->CMS_Users_Access = ($arrData[0]['CMS_Users_Access'] != '') ? json_decode(base64_decode($arrData[0]['CMS_Users_Access']), true) : null;

                                    if (is_array($this->CMS_Users_Access)) {
                                        foreach ($this->CMS_Users_Access as $Index => $Data) {
                                            if (!$Data['options']['view']) {
                                                foreach ($Data['items'] as $itemIndex => $itemData) {
                                                    #var_dump($itemData['options']['view']);;
                                                    #$itemData['options']['view'] = false;

                                                    $this->CMS_Users_Access[$Index]['items'][$itemIndex]['options']['view'] = false;
                                                }
                                            }
                                        }
                                    }

                                    $sectionXML = $CMS_FN_MENU();
                                    #$sectionXML =  simplexml_load_string(file_get_contents(APPPATH.'views/cms/layout/cms_sections.xml'), "SimpleXMLElement", LIBXML_NOCDATA);
                                    if (defined("pageSelectedClass")) {

                                        $pageSelectedUrlClass = pageSelectedUrlClass;
                                        $pageSelectedUrlMethod = pageSelectedUrlMethod;

                                        #print '<pre>';
                                        #print_r($this->CMS_Users_Access);

                                        if (isset($this->CMS_Users_Access[pageSelectedUrlClass]['options']['link_access'])) {
                                            if ($this->CMS_Users_Access[pageSelectedUrlClass]['options']['link_access'] != '') {
                                                $arrLinkAccess = explode('/', $this->CMS_Users_Access[pageSelectedUrlClass]['options']['link_access']);
                                                $pageSelectedUrlClass = $arrLinkAccess[0];
                                                $pageSelectedUrlMethod = (isset($arrLinkAccess[1])) ? $arrLinkAccess[1] : pageSelectedUrlMethod;
                                            }
                                        }

                                        #region -- CHECK ACCESS --
                                        if (CMS_Users_Type == 1) {
                                            if (isset($this->CMS_Users_Access[$pageSelectedUrlClass])) {
                                                #print $pageSelectedUrlClass;
                                                #print '<pre>';
                                                #rint_r($this->CMS_Users_Access);
                                                if (!$this->CMS_Users_Access[$pageSelectedUrlClass]['options']['view']) {
                                                    print pageError("Access denied", "You are not authorized to access this page.");
                                                    exit;
                                                }
                                            }
                                        }
                                        #endregion

                                        if ($pageSelectedUrlMethod == "index") {
                                            if (isset($sectionXML->xpath('//*[@link="' . $pageSelectedUrlClass . '"]')[0])) {

                                                $noIndex = isset($sectionXML->xpath('//*[@link="' . $pageSelectedUrlClass . '"]')[0]["no-index"]) ? filter_var($sectionXML->xpath('//*[@link="' . pageSelectedUrlClass . '"]')[0]["no-index"], FILTER_VALIDATE_BOOLEAN) : false;

                                                if (!$noIndex) {
                                                    if (CMS_Users_Type == 0) {
                                                        $tLink = strval($sectionXML->xpath('//*[@link="' . $pageSelectedUrlClass . '"]')[0]->sub[0]['link']);
                                                        if ($tLink != '') {
                                                            header("location: " . $CONFIG['website']['path'] . $CONFIG['cms']['route_name'] . "/{$tLink}");
                                                            exit;
                                                        }
                                                    } else {
                                                        $menuCounter = 0;
                                                        foreach ($this->CMS_Users_Access[$pageSelectedUrlClass]['items'] as $Index => $Data) {
                                                            if ($Data['options']['view']) {
                                                                if ($Data['options']['view']) {
                                                                    $tLink = strval($sectionXML->xpath('//*[@link="' . $pageSelectedUrlClass . '"]')[0]->sub[$menuCounter]['link']);
                                                                    header("location: " . $CONFIG['website']['path'] . $CONFIG['cms']['route_name'] . "/{$tLink}");
                                                                    exit;
                                                                }
                                                            }
                                                            $menuCounter++;
                                                        }
                                                        print pageError("Access denied", "You are not authorized to access this page.");
                                                        exit;
                                                    }
                                                }

                                            }
                                        }
                                    }
                                }
                            }

                            $this->dbClass->update("cms_users_login",
                                array(
                                    'CMS_Users_Login_Activity_DateTime'=>$dateActivity
                                ),
                                array(
                                    'CMS_Users_SessionId'=>$cmsSession
                                )
                            );
                        } else {
                            $cmsSessionError = true;
                        }
                    } else {
                        $cmsSessionError = true;
                    }
                } else {
                    $cmsSessionError = true;
                }

            } else {
                $cmsSessionError = true;
            }

            if ($cmsSessionError) {
                $urlRedirect = $CONFIG['website']['path'].$CONFIG['cms']['route_name']."/login";
                if ($cmsSessionTimeout) {
                    $arrData = $this->dbClass->select(sprintf("SELECT * FROM cms_users_login WHERE CMS_Users_SessionId = '%s'", $this->dbClass->mysqli->real_escape_string($cmsSession)));
                    if (count($arrData) > 0) {
                        $CMS_Users_Login_Type = $arrData[0]["CMS_Users_Login_Type"];
                        $CMS_Users_Login_SSO_Id = $arrData[0]["CMS_Users_Login_SSO_Id"];
                        if ($CMS_Users_Login_Type == 1) {
                            if (isset($CONFIG['cms']['sso'][$CMS_Users_Login_SSO_Id]['oauth']) && isset($CONFIG['cms']['sso'][$CMS_Users_Login_SSO_Id]['oauth']['logout_url'])) {
                                $urlRedirect = $CONFIG['cms']['sso'][$CMS_Users_Login_SSO_Id]['oauth']['logout_url'];
                            }
                        }
                    }
                }

                header("location: {$urlRedirect}");
                exit;
            }
        } else {
            header("location: ".$CONFIG['website']['path'].$CONFIG['cms']['route_name']."/login");
            exit;
        }
    }

    function layoutConfigCode($pXML) {
        global $CONFIG;

        $strRet = str_replace('[CONFIG_WEBSITE_PATH]', $CONFIG['website']['path'], $pXML);
        $strRet = str_replace('[CONFIG_CMS_DIRECTORY_NAME]', $CONFIG['cms']['route_name'], $strRet);
        $strRet = str_replace('[RES_URL]', RES_URL, $strRet);

        $strRet = preg_replace_callback('/\[\?(.*)\]/', function ($matches) { return (isset($_GET[$matches[1]]) ? $_GET[$matches[1]] : ''); }, $strRet);

        return $strRet;
    }

    function setFormLayoutData($xmlFile = "") {
        if ($xmlFile=="") {
            if ($this->formLayoutFile == "") {
                if (file_exists(SITEROOTPATH.'www/views/cms/'.$this->selectedClass.'.'.$this->selectedMethod.'.'.$this->selectedFormType.'.xml')) {
                    $this->formLayoutData = $this->loadForm(SITEROOTPATH . 'www/views/cms/' . $this->selectedClass . '.' . $this->selectedMethod . '.' . $this->selectedFormType . '.xml');
                } else {
                    if (!file_exists(APPPATH . 'views/cms/layout/forms/' . $this->selectedClass . '.' . $this->selectedMethod . '.' . $this->selectedFormType . '.xml')) {
                        if (!file_exists(APPPATH . 'views/cms/layout/forms/' . $this->selectedClass . '_' . $this->selectedMethod . '_' . $this->selectedFormType . '.xml')) {
                            print pageError("CMS Form Layout Not Found", "The form layout you requested (" . APPPATH . 'views/cms/layout/forms/' . $this->selectedClass . '.' . $this->selectedMethod . '.' . $this->selectedFormType . '.xml' . ") was not found.");
                            exit;
                        } else {
                            $this->formLayoutData = $this->loadForm(APPPATH . 'views/cms/layout/forms/' . $this->selectedClass . '_' . $this->selectedMethod . '_' . $this->selectedFormType . '.xml');
                        }
                    } else {
                        $this->formLayoutData = $this->loadForm(APPPATH . 'views/cms/layout/forms/' . $this->selectedClass . '.' . $this->selectedMethod . '.' . $this->selectedFormType . '.xml');
                    }
                }
            } else {
                if (file_exists(SITEROOTPATH.'www/views/cms/'.$this->formLayoutFile)) {
                    $this->formLayoutData = $this->loadForm(SITEROOTPATH . 'www/views/cms/' . $this->formLayoutFile);
                } else {
                    if (!file_exists(APPPATH . 'views/cms/layout/forms/' . $this->formLayoutFile)) {
                        print pageError("CMS Form Layout Not Found", "The form layout you requested (" . APPPATH . 'views/cms/layout/forms/' . $this->formLayoutFile . ") was not found.");
                        exit;
                    } else {
                        $this->formLayoutData = $this->loadForm(APPPATH . 'views/cms/layout/forms/' . $this->formLayoutFile);
                    }
                }
            }
        } else {
            if (file_exists($xmlFile)) {
                $this->formLayoutFile = $xmlFile;
                $this->formLayoutData = $this->loadForm($this->formLayoutFile);
            } else {
                print pageError("CMS Form Layout Not Found", "The form layout you requested (".$xmlFile.") was not found.");
                exit;
            }
        }

    }

    function attributeById($tagId, $tagAttributeName, $tagValue) {
        if ($this->formLayoutData)
            $this->formLayoutData->xpath('//*[@id="'.$tagId.'"]')[0][$tagAttributeName] = $tagValue;
    }

    function insertTagById($tagId, $content) {
        $this->simpleXMLImportXML($this->formLayoutData->xpath('//*[@id="'.$tagId.'"]')[0], $content);
    }

    function replaceTagById($tagId, $content) {
        $content = (string) $content;

        foreach($this->formLayoutData->xpath('//*[@id="'.$tagId.'"]') as $child) {
            $dom = dom_import_simplexml($child);
            $dom->nodeValue = "";
        }

        $domToChange = dom_import_simplexml($this->formLayoutData->xpath('//*[@id="'.$tagId.'"]')[0]);

        $fragment = $domToChange->ownerDocument->createDocumentFragment();
        $fragment->appendXML($content);

        $domToChange->appendChild($fragment);
    }

    function deleteTagById($tagId) {
        foreach($this->formLayoutData->xpath('//*[@id="'.$tagId.'"]') as $child) {
            unset($child[0]);
        }
    }

    function insertAfter($tagId, $content) {
        $layoutData = $this->formLayoutData->xpath('//*[@id="'.$tagId.'"]')[0];

        $target_dom = dom_import_simplexml($layoutData);
        $insert_dom = $target_dom->ownerDocument->importNode(dom_import_simplexml(new SimpleXMLElement($content)), true);
        if ($target_dom->nextSibling) {
            $target_dom->parentNode->insertBefore($insert_dom, $target_dom->nextSibling);
        } else {
            $target_dom->parentNode->appendChild($insert_dom);
        }

    }


    private function simpleXMLImportXML(SimpleXMLElement $parent, $xml, $before = false)
    {
        #http://stackoverflow.com/questions/767327/in-simplexml-how-can-i-add-an-existing-simplexmlelement-as-a-child-element

        $xml = (string)$xml;

        // check if there is something to add
        if ($nodata = !strlen($xml) or $parent[0] == NULL) {
            return $nodata;
        }

        // add the XML
        $node     = dom_import_simplexml($parent);
        $fragment = $node->ownerDocument->createDocumentFragment();

        $xml=preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $xml);

        $fragment->appendXML($xml);

        if ($before) {
            return (bool)$node->parentNode->insertBefore($fragment, $node);
        }

        return (bool)$node->appendChild($fragment);
    }

    private function simpleXMLImportXML2(SimpleXMLElement $parent, $xml, $option = 0)
    {
        #http://stackoverflow.com/questions/767327/in-simplexml-how-can-i-add-an-existing-simplexmlelement-as-a-child-element
        $xml = (string)$xml;

        // check if there is something to add
        if ($nodata = !strlen($xml) or $parent[0] == NULL) {
            return $nodata;
        }

        // add the XML
        $node     = dom_import_simplexml($parent);
        $fragment = $node->ownerDocument->createDocumentFragment();
        $fragment->appendXML($xml);

        if ($option == 0) {
            return (bool) $node->appendChild($fragment);
        } else if ($option == 1) {
            return (bool) $node->parentNode->insertBefore($fragment, $node);
        } else if ($option == 2) {
            return (bool) $node->parentNode->replaceChild($fragment, $node);
        }
    }

    function dataTable($tableId, $propertyName, $propertyValue) {
        $this->dataTable[$tableId][$propertyName] = $propertyValue;
    }
    function dataTableWhere($tableId, $sqlWhere) {
        $this->dataTable[$tableId]['table_where'] = $sqlWhere;
    }
    function dataTableFormatter($tableId, $columnName, $fn) {
        $this->dataTableFormatter[$tableId][$columnName] = $fn;
    }
    function dataTableDeleteCallback($tableId, $fn) {
        $this->dataTableDeleteCallback[$tableId] = $fn;
    }

    function menuSubIndex($index) {
        $this->menuSubIndex = $index;
    }

    function renderPage($fnList, $fnPostInitialize = null, $fnPostAfterInitialize = null, $fnPostComplete = null) {
        global $CONFIG, $CMS_FN_MENU;

        if (isset($this->requestSlug[0])) {
            if ($this->requestSlug[0] == 'post') {
                if (!is_callable($fnPostInitialize)) {
                    unset($this->requestSlug[0]);
                }
            }
        }

        $arrCMS_Users_Access = $this->CMS_Users_Access;

        if (CMS_Users_Type == 1) {
            $noAccess = true;
            if (($this->selectedClass != 'cms_administrator' && $this->selectedMethod != 'my_account')) {
                #print $this->selectedUrlClass.(($this->selectedUrlMethod!='index') ? ':'.$this->selectedUrlMethod : '').'<hr>';

                $pageSelectedUrlClass = $this->selectedUrlClass;
                $pageSelectedUrlMethod = $this->selectedUrlMethod;
                if (isset($this->CMS_Users_Access[$this->selectedUrlClass]['options']['link_access'])) {
                    if ($this->CMS_Users_Access[$this->selectedUrlClass]['options']['link_access']!='') {
                        $arrLinkAccess = explode('/', $this->CMS_Users_Access[$this->selectedUrlClass]['options']['link_access']);
                        $pageSelectedUrlClass = $arrLinkAccess[0];
                        $pageSelectedUrlMethod = (isset($arrLinkAccess[1])) ? $arrLinkAccess[1] : $this->selectedUrlMethod;
                    }
                }

                if (isset($arrCMS_Users_Access[$pageSelectedUrlClass.(($pageSelectedUrlMethod!='index') ? ':'.$pageSelectedUrlMethod : '')])) {
                    if (isset($arrCMS_Users_Access[$pageSelectedUrlClass.(($pageSelectedUrlMethod!='index') ? ':'.$pageSelectedUrlMethod : '')]['options'])) {
                        if (isset($arrCMS_Users_Access[$pageSelectedUrlClass.(($pageSelectedUrlMethod!='index') ? ':'.$pageSelectedUrlMethod : '')]['options']['view'])) {
                            if ($arrCMS_Users_Access[$pageSelectedUrlClass.(($pageSelectedUrlMethod!='index') ? ':'.$pageSelectedUrlMethod : '')]['options']['view']) {
                                $noAccess = false;
                            } else {
                                if (isset($arrCMS_Users_Access[$pageSelectedUrlClass.(($pageSelectedUrlMethod!='index') ? ':'.$pageSelectedUrlMethod : '')]['options']['link_method_access'])) {
                                    $linkMethodAccess = $arrCMS_Users_Access[$pageSelectedUrlClass.(($pageSelectedUrlMethod!='index') ? ':'.$pageSelectedUrlMethod : '')]['options']['link_method_access'];
                                    #print '<pre>';
                                    #print_r($linkMethodAccess);
                                    #print $pageSelectedUrlClass;
                                    foreach($linkMethodAccess as $Index => $Method) {
                                        #print $pageSelectedUrlClass.(($Method!='index') ? ':'.$Method : '');
                                        if (isset($arrCMS_Users_Access[$pageSelectedUrlClass.(($Method!='index') ? ':'.$Method : '')]['options']['view'])) {
                                            if ($arrCMS_Users_Access[$pageSelectedUrlClass . (($Method != 'index') ? ':' . $Method : '')]['options']['view']) {
                                                $noAccess = false;
                                                break;
                                            }
                                        }
                                        if (isset($arrCMS_Users_Access[$pageSelectedUrlClass]['items'][$pageSelectedUrlClass.':'.$Method]['options']['view'])) {
                                            if ($arrCMS_Users_Access[$pageSelectedUrlClass]['items'][$pageSelectedUrlClass.':'.$Method]['options']['view']) {
                                                $noAccess = false;
                                                break;
                                            }
                                        }
                                    }
                                    #print '<pre>';
                                    #print_r($arrCMS_Users_Access);
                                }
                            }
                        }
                    }
                } else {
                    #IF SUB ITEMS
                    if (isset($arrCMS_Users_Access[$pageSelectedUrlClass])) {
                        if (isset($arrCMS_Users_Access[$pageSelectedUrlClass]['items'][$pageSelectedUrlClass.':'.$pageSelectedUrlMethod]['options']['view'])) {
                            if ($arrCMS_Users_Access[$pageSelectedUrlClass]['items'][$pageSelectedUrlClass.':'.$pageSelectedUrlMethod]['options']['view']) {
                                $noAccess = false;

                            } else {
                                #link_method_access
                            }

                        } else {
                            #print $pageSelectedUrlClass.'::'.$pageSelectedUrlMethod;
                            #print '<pre>';
                            #print_r($arrCMS_Users_Access);
                        }
                    } else {
                        #print '<pre>';
                        #print_r($arrCMS_Users_Access);
                    }
                }
            } else if (($this->selectedClass == 'cms_administrator' && $this->selectedMethod == 'my_account')) {
                $noAccess = false;
            }

            if ($noAccess) {
                print pageError("Access denied", "You are not authorized to access this page.");
                exit;
            }
        }

        $data = $this->requestSlug;
        if (isset($this->requestSlug[0])) {
            if ($this->requestSlug[0] == 'list') {
                #region -- RENDER LIST --
                $this->selectedFormType = "list";

                array_shift($this->requestSlug);


                $this->setFormLayoutData($this->initFormLayout['list']);


                #region -- USERS ACCESS --
                if (CMS_Users_Type == 1) {
                    $tAccess = (isset($this->CMS_Users_Access[$this->selectedUrlClass]['items'][$this->selectedUrlClass.':'.$this->selectedUrlMethod])) ?
                        $this->CMS_Users_Access[$this->selectedUrlClass]['items'][$this->selectedUrlClass.':'.$this->selectedUrlMethod] :
                        (isset($this->CMS_Users_Access[$this->selectedUrlClass])) ? $this->CMS_Users_Access[$this->selectedUrlClass] : false;

                    if ($tAccess) {
                        $tAccessOptions = (isset($this->CMS_Users_Access[$this->selectedUrlClass]['items'][$this->selectedUrlClass.':'.$this->selectedUrlMethod])) ?
                            $this->selectedUrlClass.':'.$this->selectedUrlMethod :
                            $this->selectedUrlClass;

                        if (isset($tAccess['options']['access_options'][$tAccessOptions.'_edit'])) {
                            if (!$tAccess['options']['access_options'][$tAccessOptions.'_edit']['selected']) {
                                $xmlSearchTag = $this->formLayoutData->xpath('//column[@type="action"]');
                                foreach ($xmlSearchTag as $Index => $Data) {
                                    if (count($Data->button) == 1) {
                                        if ($Data->button['type'] == 'edit') {
                                            $Data['visible'] = 'false';
                                            $Data->button['visible'] = 'false';
                                        }
                                    } else {
                                        foreach ($Data->xpath('//button[@type="edit"]') as $sIndex => $sData) {
                                            $sData['visible'] = 'false';
                                        }
                                    }
                                }
                            }
                        }

                        if (isset($tAccess['options']['access_options'][$tAccessOptions.'_add'])) {
                            if (!$tAccess['options']['access_options'][$tAccessOptions.'_add']['selected']) {
                                $xmlSearchTag = $this->formLayoutData->xpath('//buttons/button[@type="add"]');
                                foreach ($xmlSearchTag as $Index => $Data) {
                                    $Data['visible'] = 'false';
                                }
                            }
                        }

                        if (isset($tAccess['options']['access_options'][$tAccessOptions.'_delete'])) {
                            if (!$tAccess['options']['access_options'][$tAccessOptions.'_delete']['selected']) {
                                $xmlSearchTag = $this->formLayoutData->xpath('//buttons/button[@type="delete"]');
                                foreach ($xmlSearchTag as $Index => $Data) {
                                    $Data['visible'] = 'false';
                                }
                                $xmlSearchTag = $this->formLayoutData->xpath('//column[@type="select"]');
                                foreach ($xmlSearchTag as $Index => $Data) {
                                    $Data['visible'] = 'false';
                                }
                            }
                        }
                    }
                }
                #endregion

                $fnList();

                #region -- GENERATE RESERVE CMS COLUMN --
                $defaultCMSField = (isset($this->formLayoutData->body['default_cms_field'])) ? filter_var($this->formLayoutData->body['default_cms_field'], FILTER_VALIDATE_BOOLEAN) : true;
                if ($defaultCMSField) {
                    if (isset($this->formLayoutData->body)) {
                        foreach($this->formLayoutData->body->datatable as $child) {
                            if (count($child->children())) {
                                $tableId = strval($child->table['id']);
                                $table = (isset($this->dataTable[$tableId]['table_name'])) ? strval($this->dataTable[$tableId]['table_name']) : strval($child->table['table_name']);

                                $arrCMSColumns = array(
                                    'cms_CreatedById'=>"INT NOT NULL DEFAULT '0'",
                                    'cms_CreatedByName'=>"VARCHAR(255) NOT NULL DEFAULT ''",
                                    'cms_CreatedDate'=>"DATETIME NULL DEFAULT NULL",
                                    'cms_ModifiedById'=>"INT NOT NULL DEFAULT '0'",
                                    'cms_ModifiedByName'=>"VARCHAR(255) NOT NULL DEFAULT ''",
                                    'cms_ModifiedDate'=>"DATETIME NULL DEFAULT NULL",
                                    'cms_Details'=>"TEXT NULL DEFAULT NULL"
                                );
                                foreach($arrCMSColumns as $ColName => $ColProp) {
                                    $arrCol = $this->dbClass->select(
                                        "
                                                    SELECT *
                                                    FROM information_schema.COLUMNS
                                                    WHERE
                                                        TABLE_SCHEMA = '{$this->dbClass->database}' AND
                                                        TABLE_NAME = '{$table}' AND
                                                        COLUMN_NAME = '{$ColName}'
                                                "
                                    );
                                    if (count($arrCol) == 0) {
                                        $this->dbClass->execute("ALTER TABLE `{$table}` ADD `{$ColName}` {$ColProp}");
                                    }
                                }

                                if (strval($child->table['batch_upload_field'])!='') $this->batchUpload($tableId);
                            }
                        }
                    }
                }
                #endregion

                if ($_POST) { 
                    if (isset($_POST['cmsListPostVal'])) {
                        $cmsListPostVal = json_decode($_POST['cmsListPostVal'], true);
                        if (isset($this->dataTableAction[$cmsListPostVal['table_id']][$cmsListPostVal['button_id']])) {
                            if (is_callable($this->dataTableAction[$cmsListPostVal['table_id']][$cmsListPostVal['button_id']]))
                                $this->dataTableAction[$cmsListPostVal['table_id']][$cmsListPostVal['button_id']]($cmsListPostVal['value']);
                        }

                        exit;
                    }

                    if (isset($_POST['cmsListDeleteVal'])) {
                        $arrData = json_decode($_POST['cmsListDeleteVal'], true);

                        foreach($this->formLayoutData->body->datatable as $child) {
                            if (count($child->children())) {
                                if (strval($child->table['id']) == $arrData['table_id']) {
                                    $tableId = strval($child->table['id']);
                                    $table = (isset($this->dataTable[$tableId]['table_name'])) ? strval($this->dataTable[$tableId]['table_name']) : strval($child->table['table_name']);
                                    $db = new cmsDatabaseClass();

                                    $arrDelData = array();
                                    $arrDataPK = $db->select("SHOW KEYS FROM {$table} WHERE Key_name = 'PRIMARY'");
                                    if (count($arrDataPK)==0) {
                                        $this->error[] = "table name \"{$table}\" has no primary key";
                                    } else {
                                        $this->primaryId['name'] = $arrDataPK[0]['Column_name'];
                                        $arrDelData = $db->select("SELECT * FROM {$table} WHERE ".$arrDataPK[0]['Column_name']." = ".$arrData['value']);
                                        if (count($arrDelData)>0) {
                                            $arrDelData = $arrDelData[0];
                                        }
                                    }

                                    $db->delete($table, intval($arrData['value']));

                                    #$this->dataTableDeleteCallback[$arrData['table_id']](intval($arrData['value']), $arrDelData);

                                    if (isset($this->dataTableDeleteCallback[$tableId])) {
                                        if (is_callable($this->dataTableDeleteCallback[$tableId])) $this->dataTableDeleteCallback[$tableId]($arrData['value'], $arrDelData);
                                    }
                                }
                            }
                        }

                        exit;
                    }

                    if (isset($_POST['cmsButtonPostVal'])) {
                        $cmsButtonPostVal = json_decode($_POST['cmsButtonPostVal'], true);
                        if (isset($this->dataTableButtonPost[$cmsButtonPostVal['table_id']][$cmsButtonPostVal['button_id']])) {
                            if (is_callable($this->dataTableButtonPost[$cmsButtonPostVal['table_id']][$cmsButtonPostVal['button_id']]))
                                $this->dataTableButtonPost[$cmsButtonPostVal['table_id']][$cmsButtonPostVal['button_id']]($cmsButtonPostVal['value']);
                        }

                        exit;
                    }

                    if (isset($_POST['cmsButtonDeleteVal'])) {
                        $arrData = json_decode($_POST['cmsButtonDeleteVal'], true);

                        foreach($this->formLayoutData->body->datatable as $child) {
                            if (count($child->children())) {
                                if (strval($child->table['id']) == $arrData['table_id']) {
                                    $tableId = strval($child->table['id']);
                                    $table = (isset($this->dataTable[$tableId]['table_name'])) ? strval($this->dataTable[$tableId]['table_name']) : strval($child->table['table_name']);
                                    $db = new cmsDatabaseClass();
                                    foreach($arrData['value'] as $Index => $Value) {

                                        $arrDelData = array();
                                        $arrDataPK = $db->select("SHOW KEYS FROM {$table} WHERE Key_name = 'PRIMARY'");
                                        if (count($arrDataPK)==0) {
                                            $this->error[] = "table name \"{$table}\" has no primary key";
                                        } else {
                                            $this->primaryId['name'] = $arrDataPK[0]['Column_name'];
                                            $arrDelData = $db->select("SELECT * FROM {$table} WHERE ".$arrDataPK[0]['Column_name']." = ".$Value);
                                            if (count($arrDelData)>0) {
                                                $arrDelData = $arrDelData[0];
                                            }
                                        }

                                        $db->delete($table, intval($Value));
                                        if (isset($this->dataTableDeleteCallback[$tableId])) {
                                            if (is_callable($this->dataTableDeleteCallback[$tableId])) $this->dataTableDeleteCallback[$tableId]($Value, $arrDelData);
                                        }
                                    }
                                }
                            }
                        }

                        exit;
                    }

                    if (isset($_POST['cmsListOrderVal'])) {
                        $arrData = json_decode(base64_decode($_POST['cmsListOrderVal']), true);

                        foreach($this->formLayoutData->body->datatable as $child) {
                            if (count($child->children())) {
                                if (strval($child->table['id']) == $arrData['table_id']) {
                                    $tableId = strval($child->table['id']);
                                    $table = (isset($this->dataTable[$tableId]['table_name'])) ? strval($this->dataTable[$tableId]['table_name']) : strval($child->table['table_name']);
                                    $table_order_field = (isset($this->dataTable[$tableId]['table_order_field'])) ? strval($this->dataTable[$tableId]['table_order_field']) : strval($child->table['table_order_field']);

                                    $db = new cmsDatabaseClass();
                                    foreach($arrData['value'] as $Index => $Value) {
                                        #print 'Table:'.strval($table).' '.$table_order_field.'='.$Index.' '.$arrDataPK[0]['Column_name'].'='.intval($Value)."\n\n";
                                        $db->update(strval($table), array($table_order_field => $Index), intval($Value));
                                    }
                                }
                            }
                        }

                        exit;
                    }

                }

                $this->requestSlug = array_slice($this->requestSlug, 0);
                $this->dataTablePopulate($this->requestSlug);
                #$this->cmsLoadView("cms/cms_list");

                #include VENDORSPATH.'BladeOne/BladeOne.php';
                #$blade=new \eftec\bladeone\BladeOne(APPPATH.'views', null, \eftec\bladeone\BladeOne::MODE_DEBUG);
                #echo $blade->run("cms/cms_list.php", ['self'=>$this, 'CONFIG'=>$CONFIG, 'CMS_FN_MENU'=>$CMS_FN_MENU]);

                $this->loadView("cms/cms_list", 1, ['self'=>$this, 'CONFIG'=>$CONFIG, 'CMS_FN_MENU'=>$CMS_FN_MENU]);
                exit;
                #endregion
            } else if ($this->requestSlug[0] == 'post') {
                #region -- RENDER POST --
                if (isset($_POST['cmsPost'])) {
                    $this->cmsPost = json_decode(base64_decode($_POST['cmsPost']), true);
                }

                array_shift($this->requestSlug);

                if (isset($this->requestSlug[0])) {
                    if (is_numeric($this->requestSlug[0])) {
                        $this->postType = 1;
                    }
                } else {
                    $this->postType = 0;
                }

                $db = new cmsDatabaseClass();

                $this->selectedFormType = "post";
                $this->setFormLayoutData($this->initFormLayout['post']);

                #region -- USERS ACCESS --
                if (CMS_Users_Type == 1) {
                    if ($this->selectedUrlClass != 'administrator' && $this->selectedUrlMethod != 'my-account') {

                        $pageSelectedUrlClass = $this->selectedUrlClass;
                        $pageSelectedUrlMethod = $this->selectedUrlMethod;
                        if (isset($this->CMS_Users_Access[$this->selectedUrlClass]['options']['link_access'])) {
                            if ($this->CMS_Users_Access[$this->selectedUrlClass]['options']['link_access']!='') {
                                $arrLinkAccess = explode('/', $this->CMS_Users_Access[$this->selectedUrlClass]['options']['link_access']);
                                $pageSelectedUrlClass = $arrLinkAccess[0];
                                $pageSelectedUrlMethod = (isset($arrLinkAccess[1])) ? $arrLinkAccess[1] : $this->selectedUrlMethod;
                            }
                        }


                        $tAccess = (isset($this->CMS_Users_Access[$pageSelectedUrlClass]['items'][$pageSelectedUrlClass.':'.$pageSelectedUrlMethod])) ?
                            $this->CMS_Users_Access[$pageSelectedUrlClass]['items'][$pageSelectedUrlClass.':'.$pageSelectedUrlMethod] :
                            (isset($this->CMS_Users_Access[$pageSelectedUrlClass])) ? $this->CMS_Users_Access[$pageSelectedUrlClass] : false;

                        #print '<pre>';
                        #print_r($tAccess);
                        #print '</pre>';
                        #exit;

                        if ($tAccess) {
                            $tAccessOptions = (isset($this->CMS_Users_Access[$pageSelectedUrlClass]['items'][$pageSelectedUrlClass.':'.$pageSelectedUrlMethod])) ?
                                $pageSelectedUrlClass.':'.$pageSelectedUrlMethod :
                                $pageSelectedUrlClass;

                            /*print '<pre>';
                            print_r($tAccess);
                            print '</pre>';
                            print '<pre>';
                            print_r($tAccessOptions);
                            print '</pre>';
                            exit;*/

                            #if (isset($tAccess['options']['access_options'][$tAccessOptions])) {
                            if (count($tAccess['options']['access_options'])>0) {
                                if (isset($tAccess['options']['access_options'][$tAccessOptions.'_edit'])) {
                                    if ($this->postType == 1 && !$tAccess['options']['access_options'][$tAccessOptions.'_edit']['selected']) {
                                        print pageError("Access denied", "You are not authorized to access this page.");
                                        exit;
                                    }
                                }

                                if (isset($tAccess['options']['access_options'][$tAccessOptions.'_add'])) {
                                    if (!$tAccess['options']['access_options'][$tAccessOptions.'_add']['selected']) {
                                        print pageError("Access denied", "You are not authorized to access this page.");
                                        exit;
                                    }
                                }
                            } else {
                                if (isset($tAccess['items'][$tAccessOptions]['options']['access_options'][$tAccessOptions.'_edit'])) {
                                    if ($this->postType == 1 && !$tAccess['items'][$tAccessOptions]['options']['access_options'][$tAccessOptions.'_edit']['selected']) {
                                        print pageError("Access denied", "You are not authorized to access this page.");
                                        exit;
                                    }
                                }

                                if (isset($tAccess['items'][$tAccessOptions]['options']['access_options'][$tAccessOptions.'_add'])) {
                                    if (!$tAccess['items'][$tAccessOptions]['options']['access_options'][$tAccessOptions.'_add']['selected']) {
                                        print pageError("Access denied", "You are not authorized to access this page.");
                                        exit;
                                    }
                                }
                            }

                        }
                    }
                }
                #endregion

                $table_name = "";
                if (!isset($this->formLayoutData->body['table_name'])) {
                    #$this->error[] = "table_name not found in form > body";
                } else {
                    $table_name = strval($this->formLayoutData->body['table_name']);
                }

                $this->table_name = $table_name;

                $saveClose = (isset($this->formLayoutData->body['saveclose'])) ? strval($this->formLayoutData->body['saveclose']) : 'true';

                #print_r($this->getPrimaryControls()); exit;

                if ($table_name!='') {
                    $arrDataPK = $db->select("SHOW KEYS FROM {$table_name} WHERE Key_name = 'PRIMARY'");
                    if (count($arrDataPK)==0) {
                        $this->error[] = "table name \"{$table_name}\" has no primary key";
                    } else {
                        $this->primaryId['name'] = $arrDataPK[0]['Column_name'];
                    }
                }

                if ($_POST) {
                    $primaryId = isset($_POST['id']) ? intval($_POST['id']) : 0;
                    $this->primaryId['value'] = $primaryId;
                }

                $fnPostInitialize();

                include_once("form.control.post.req.php");

                if ($_POST) {
                    $postError = "";

                    if (isset($_POST['cmsPost'])) {
                        $cmsPost = $this->cmsPost;

                        $primaryId = isset($_POST['id']) ? intval($_POST['id']) : 0;
                        $this->primaryId['value'] = $primaryId;

                        $arrPrimaryControls = $this->getPrimaryControls();
                        #$arrAlert = array();
                        foreach($this->cmsPost['primary'] as $Key => $Value) {
                            if (isset($arrPrimaryControls[$Key]['required'])) {
                                if ($arrPrimaryControls[$Key]['required'] == 'true' && $Value == '') {
                                    $this->alert[] = $arrPrimaryControls[$Key]['caption'].' is required';
                                }
                            }
                        }

                        if (!is_null($this->postSubmittedEvent['start'])) {
                            if (is_callable($this->postSubmittedEvent['start'])) {
                                $this->postSubmittedEvent['start']($this->primaryId);
                                $cmsPost = $this->cmsPost;
                            }
                        }

                        $retArrRepeater = array();

                        $retEvent = null;

                        if (count($this->alert) == 0) {

                            //VERIFY FIELD IF EXIST
                            foreach ($cmsPost['primary'] as $ColName => $ColVal) {
                                $arrCol = $this->dbClass->select(
                                    "
                                        SELECT *
                                        FROM information_schema.COLUMNS
                                        WHERE
                                            TABLE_SCHEMA = '{$this->dbClass->database}' AND
                                            TABLE_NAME = '{$table_name}' AND
                                            COLUMN_NAME = '{$ColName}'
                                    "
                                );
                                if (count($arrCol) == 0) {
                                    //REMOVE IF NOT FOUND
                                    unset($cmsPost['primary'][$ColName]);
                                }
                            }

                            #print '-------';
                            #print_r($cmsPost['primary']);

                            if ($primaryId == 0) {
                                #NEW
                                $this->postType = 0;
                                $retArr = $db->insert($table_name, $cmsPost['primary']);

                                if (isset($retArr['value'])) {
                                    $primaryId = $retArr['value'];
                                    $this->primaryId['value'] = $primaryId;
                                } else {
                                    $postError = $retArr['error'];
                                }

                            } else {
                                #UPDATE
                                $this->postType = 1;
                                $retArr = $db->update($table_name, $cmsPost['primary'], $primaryId);
                                if (isset($retArr['error'])) {
                                    $postError = $retArr['error'];
                                }
                            }

                            if ($primaryId > 0) {
                                #FILE UPLOADED
                                $repeaterObj = $this->simpleXMLElementObjXPath($this->formLayoutData, 'body/panel/control[@type="upload"]');
                                foreach($repeaterObj as $controlObj) {
                                    $fileId = strval($controlObj['id']);

                                    if (isset($cmsPost['primary'][$fileId])) {
                                        if ($cmsPost['primary'][$fileId]!='') {
                                            $tArr = explode('/', trim($cmsPost['primary'][$fileId]));
                                            if ($tArr[0] == 'temp') {
                                                $tempFile = trim($cmsPost['primary'][$fileId]); #print $tempFile."\n";

                                                $arrUploadDir = array();
                                                if (strval($controlObj['upload_parent_dir'])!='') $arrUploadDir[] = strval($controlObj['upload_parent_dir']);
                                                $arrUploadDir[] = $primaryId;
                                                if (strval($controlObj['upload_container_dir'])!='') $arrUploadDir[] = strval($controlObj['upload_container_dir']);

                                                $uploadContainerDir = implode('/',$arrUploadDir);

                                                if (!is_dir(UPLOADSPATH . $uploadContainerDir)) {
                                                    mkdir(UPLOADSPATH . $uploadContainerDir, 0777, true);
                                                } else {

                                                }

                                                #print UPLOADSPATH . $uploadContainerDir."\n";
                                                #print UPLOADSPATH.$tempFile."\n";

                                                if (file_exists(UPLOADSPATH.$tempFile)) {
                                                    copy(UPLOADSPATH.$tempFile, UPLOADSPATH.$uploadContainerDir.'/'.basename($tempFile));
                                                    unlink(UPLOADSPATH.$tempFile);
                                                    cmsTools::rmDir(dirname(UPLOADSPATH.$tempFile));
                                                    $db->update($table_name, array($fileId=>$uploadContainerDir.'/'.basename($tempFile)), $primaryId);
                                                }
                                            }
                                        }
                                    }

                                    /*if (isset($cmsPost['primary'][$fileId])) {
                                        if ($cmsPost['primary'][$fileId]!='') {
                                            $cmsPost['primary'][$fileId] = $CONFIG['website']['path'].$cmsPost['primary'][$fileId];

                                            $tArr = explode('/', trim($cmsPost['primary'][$fileId]));

                                            $tArrWebPath = explode('/', $CONFIG['website']['path']);
                                            $tPathIndex = count($tArrWebPath);
                                            $tArrWebPath = array_filter($tArrWebPath, function ($item) { return ($item!=''); });
                                            $tArrWebPath = array_values($tArrWebPath);

                                            if (isset($tArr[$tPathIndex]) && $tArr[$tPathIndex] == 'temp') {
                                                $tArrPath = $tArr;
                                                foreach ($tArr as $subPathIndex => $subPathValue) {
                                                    if ($subPathIndex == 0 && $subPathValue == '') {
                                                        unset($tArrPath[0]);
                                                    }

                                                    foreach($tArrWebPath as $pathIndex => $pathValue) {
                                                        if ($pathValue == $subPathValue) {
                                                            unset($tArrPath[$subPathIndex]);
                                                        }
                                                    }

                                                    if ($subPathValue == $CONFIG['cms']['directory_upload_name']) {
                                                        unset($tArrPath[$subPathIndex]);
                                                    }
                                                    if ($subPathValue == 'temp') {
                                                        unset($tArrPath[$subPathIndex]);
                                                    }
                                                }
                                                $tArrPath = array_values($tArrPath);

                                                $uploadDir = SITEROOTPATH.$CONFIG['cms']['directory_upload_name'];
                                                $uploadParentDir = strval($controlObj['upload_parent_dir']);
                                                $uploadContainerDir = (strval($controlObj['upload_container_dir'])!='') ? '/'.strval($controlObj['upload_container_dir']) : '';
                                                $uploadSavePath = $uploadDir.'/'.$uploadParentDir.'/'.$primaryId.$uploadContainerDir;


                                                if (!is_dir($uploadSavePath)) {
                                                    mkdir($uploadSavePath, 0777, true);
                                                }

                                                $uploadTempFile = $uploadDir.'/temp/'.implode('/', $tArrPath);
                                                $uploadTempDir = $uploadDir.'/temp/'.$tArrPath[0];
                                                $uploadSaveFile = $uploadSavePath.'/'.basename($cmsPost['primary'][$fileId]);
                                                $uploadSaveUrl = $CONFIG['cms']['directory_upload_name'].'/'.$uploadParentDir.'/'.$primaryId.$uploadContainerDir.'/'.basename($cmsPost['primary'][$fileId]); #$CONFIG['website']['path'].$CONFIG['cms']['directory_upload_name'].'/'.$uploadParentDir.'/'.$primaryId.$uploadContainerDir.'/'.basename($cmsPost['primary'][$fileId]);

                                                $dbug = '';
                                                $dbug .= print_r(explode('/', trim($cmsPost['primary'][$fileId])), true);
                                                #$dbug .= 'uploadSavePath: '.$uploadSavePath."\n";
                                                #$dbug .= $uploadDir.'/temp/'.$tArrPath[0]."\n";
                                                #$dbug .= $uploadDir.'/temp/'.implode('/', $tArrPath)."\n";
                                                #$dbug .= $cmsPost['primary'][$fileId]."\n";
                                                #$dbug .= $uploadSaveFile."\n";
                                                #$dbug .= $uploadSaveUrl."\n";

                                                #if ($CONFIG['environment'] == 'development') {
                                                    file_put_contents(SITEROOTPATH . "uploads/temp/debug-upload.txt", date("Y-m-d H:i:s") . " - {$dbug}\n" . PHP_EOL, FILE_APPEND);
                                                #}
                                                #exit;

                                                if (file_exists($uploadTempFile)) {
                                                    copy($uploadTempFile, $uploadSaveFile);
                                                    unlink($uploadTempFile);
                                                    cmsTools::rmDir($uploadTempDir);
                                                    $db->update($table_name, array($fileId=>$uploadSaveUrl), $primaryId);
                                                }
                                            }
                                        }
                                    }*/
                                }

                                #REPEATER
                                /*foreach($cmsPost['repeater'] as $repeaterIndex => $repeaterObj) {

                                    $repeaterProperties = json_decode(base64_decode($repeaterObj['details']['properties']), true);

                                    #print_r($repeaterProperties);

                                    $retArrRepeater[$repeaterProperties['table_name']] = array();

                                    foreach($repeaterObj['data'] as $repeaterControlIndex => $repeaterControlObj) {
                                        $repeaterPostFields = array();
                                        foreach($repeaterControlObj as $controlName => $controlVal) {
                                            $repeaterPostFields[$controlName] = $controlVal;
                                        }

                                        $repeater_row_id = 0;

                                        if (intval($repeaterPostFields[$repeaterProperties['table_primary_field']])==0) {
                                            #NEW
                                            unset($repeaterPostFields[$repeaterProperties['table_primary_field']]);
                                            $repeaterPostFields[$repeaterProperties['table_link_field']] = $primaryId;
                                            if (isset($repeaterProperties['table_order_field'])) {
                                                if ($repeaterProperties['table_order_field']!='') {
                                                    $repeaterPostFields[$repeaterProperties['table_order_field']] = $repeaterControlIndex;
                                                }
                                            }
                                            $retArr = $db->insert($repeaterProperties['table_name'], $repeaterPostFields);
                                            $repeater_row_id = $retArr['value'];
                                        } else {
                                            #UPDATE
                                            $repeaterRowId = $repeaterPostFields[$repeaterProperties['table_primary_field']];
                                            unset($repeaterPostFields[$repeaterProperties['table_primary_field']]);
                                            if (isset($repeaterProperties['table_order_field'])) {
                                                if ($repeaterProperties['table_order_field']!='') {
                                                    $repeaterPostFields[$repeaterProperties['table_order_field']] = $repeaterControlIndex;
                                                    #print_r($repeaterPostFields);
                                                    #echo "\n";
                                                }
                                            }
                                            $retArr = $db->update($repeaterProperties['table_name'], $repeaterPostFields, $repeaterRowId);
                                            $repeater_row_id = $repeaterRowId;
                                        }

                                        $retArrRepeater[$repeaterProperties['table_name']][$repeaterControlIndex] = $repeater_row_id;

                                        if ($repeater_row_id > 0) {
                                            #FILE UPLOADED
                                            $getRepeaterObj = $this->simpleXMLElementObjXPath($this->formLayoutData, 'body/panel/repeater[@id="'.$repeaterProperties['id'].'"]');

                                            #print_r($getRepeaterObj);

                                            foreach($getRepeaterObj as $repeaterItem) {
                                                $repeater_table_name = strval($repeaterItem['table_name']);
                                                $repeater_id = strval($repeaterItem['id']);

                                                $repeaterControlFileObj = $this->simpleXMLElementObjXPath($repeaterItem, 'control[@type="file"]');
                                                if (count($repeaterControlFileObj)>0) {
                                                    $uploadType = (isset($controlObj['upload_type'])) ? intval($controlObj['upload_type']) : 0;

                                                    foreach($repeaterControlFileObj as $controlObj) {
                                                        $fileId = strval($controlObj['id']);

                                                        if (isset($cmsPost['repeater'][$repeater_id]['data'][$repeaterControlIndex][$fileId])) {
                                                            $jsonFile = json_decode($cmsPost['repeater'][$repeater_id]['data'][$repeaterControlIndex][$fileId], true);
                                                            $dirPath = $jsonFile['path'];
                                                            $filePath = $jsonFile['path'].'/'.$jsonFile['name'];

                                                            if (is_file(SITEROOTPATH.$filePath) && isset($jsonFile['upload_temp'])) {
                                                                $newImagePath = cmsTools::makeSlug($repeater_id).'/'.$repeater_row_id.'/'.cmsTools::makeSlug($fileId);

                                                                $uploadDir = $CONFIG['cms']['directory_upload_name'].'/'.$controlObj['dir'].'/'.$primaryId.'/'.$newImagePath;
                                                                if ($uploadType == 0) {
                                                                    if (!is_dir(SITEROOTPATH.$uploadDir)) {
                                                                        mkdir(SITEROOTPATH.$uploadDir, 0777, true);
                                                                    } else {
                                                                        cmsTools::rmDir(SITEROOTPATH.$uploadDir);
                                                                        mkdir(SITEROOTPATH.$uploadDir, 0777, true);
                                                                    }
                                                                    cmsTools::xcopy(SITEROOTPATH.$dirPath, SITEROOTPATH.$uploadDir, 0777);
                                                                } else if ($uploadType == 1) {
                                                                    $tPathInfo = pathinfo($jsonFile['name']);
                                                                    $uploadDir = $CONFIG['cms']['directory_upload_name'].'/'.$controlObj['dir'].'/'.$primaryId.'-'.$repeater_row_id.'.'.$tPathInfo['extension'];
                                                                    $jsonFile['name'] = $primaryId.'-'.$repeater_row_id.'.'.$tPathInfo['extension'];
                                                                    cmsTools::xcopy(SITEROOTPATH.$filePath, SITEROOTPATH.$uploadDir, 0777);
                                                                }

                                                                #DELETE TEMP DIR & FILES
                                                                $crypt = new cmsCryptonite();
                                                                $uploadTemp = SITEROOTPATH.$CONFIG['cms']['directory_upload_name'].'/temp/'.$crypt->decode($jsonFile['upload_temp']); #cmsTools::rmDir(SITEROOTPATH.$CONFIG['cms']['directory_upload_name'].'/temp/'.$crypt->decode($jsonFile['upload_temp']));

                                                                #UPDATE DIR
                                                                if ($uploadType == 0) {
                                                                    $jsonFile['path'] =  $controlObj['dir'].'/'.$primaryId.'/'.$newImagePath;
                                                                    $jsonFile['base_path'] =  $controlObj['dir'].'/'.$primaryId.'/'.cmsTools::makeSlug($repeater_id);
                                                                } else if ($uploadType == 1) {
                                                                    $jsonFile['path'] = strval($controlObj['dir']);
                                                                    $jsonFile['base_path'] = strval($controlObj['dir']);
                                                                }
                                                                if (isset($jsonFile['upload_temp'])) unset($jsonFile['upload_temp']);
                                                                $db->update($repeater_table_name, array($fileId=>json_encode($jsonFile)), $repeater_row_id);
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    #DELETE UPLOADED TEMP DIR/FILES
                                    #if ($uploadTemp!='') {
                                    #    cmsTools::rmDir($uploadTemp);
                                    #}

                                    #DELETE REPEATER DATA IF MARKED AS 'deleted'
                                    if (isset($repeaterObj['deleted'])) {
                                        foreach($repeaterObj['deleted'] as $repeaterIndex => $repeaterDataId) {
                                            $repeaterItem = $this->simpleXMLElementObjXPath($this->formLayoutData, 'body/panel/repeater[@id="' . $repeaterProperties["id"] . '"]');
                                            if (count($repeaterItem) > 0) {
                                                $arrData = $db->select("SELECT * FROM ".$repeaterProperties["table_name"]." WHERE ".$repeaterProperties["table_primary_field"]."=".$repeaterDataId);
                                                if (count($arrData) > 0) {
                                                    #$jsonFile = json_decode($arrData[0]['Content_Block_Image'], true);
                                                    #$dirBasePath = $jsonFile['base_path'];
                                                    #$dirBasePath = SITEROOTPATH.$CONFIG['cms']['directory_upload_name']."/".$dirBasePath."/".$repeaterDataId;
                                                    #if (is_dir($dirBasePath)) {
                                                    #    #print $dirBasePath;
                                                    #    cmsTools::rmDir($dirBasePath);
                                                    #}
                                                }
                                            }
                                            #print $repeaterProperties['table_name'].' = '.$repeaterDataId."\n";

                                            foreach($retArrRepeater[$repeaterProperties['table_name']] as $retRepeaterIndex => $retRepeaterValue) {
                                                if ($retRepeaterValue == $repeaterDataId) {
                                                    unset($retArrRepeater[$repeaterProperties['table_name']][$retRepeaterIndex]);
                                                }
                                            }

                                            $db->delete($repeaterProperties['table_name'], intval($repeaterDataId));
                                        }
                                    }
                                }*/

                                #DATATABLE
                                foreach($cmsPost['datatable'] as $datatableIndex => $datatableObj) {
                                    $arrDataId = array();

                                    #print $datatableIndex."\n\n";

                                    $datatableItem = $this->simpleXMLElementObjXPath($this->formLayoutData, 'body/panel/control[@id="' . $datatableIndex . '"]');

                                    #print_r(strval($datatableItem[0]['table_name']));

                                    $tTableName = strval($datatableItem[0]['table_name']);
                                    $tTableNameParentPk = strval($datatableItem[0]['table_parent_pk']);
                                    #print $tTableName;
                                    #print "\n\n";

                                    $tTableNamePK = "";
                                    $arrDataPK = $db->select("SHOW KEYS FROM {$tTableName} WHERE Key_name = 'PRIMARY'");
                                    if (count($arrDataPK) > 0) {
                                        $tTableNamePK = $arrDataPK[0]['Column_name'];
                                    }

                                    if ($tTableNamePK != '') {
                                        foreach ($datatableObj as $Index => $Data) {

                                            if (isset($Data[$tTableNamePK])) {
                                                $arrPK = array(
                                                    $tTableNamePK => $Data[$tTableNamePK]
                                                );

                                                foreach ($Data as $ColName => $ColVal) {
                                                    $arrCol = $this->dbClass->select(
                                                    "
                                                        SELECT *
                                                        FROM information_schema.COLUMNS
                                                        WHERE
                                                            TABLE_SCHEMA = '{$this->dbClass->database}' AND
                                                            TABLE_NAME = '{$tTableName}' AND
                                                            COLUMN_NAME = '{$ColName}'
                                                    "
                                                    );
                                                    if (count($arrCol) == 0) {
                                                        unset($Data[$ColName]);
                                                    }
                                                }

                                                if (intval($Data[$tTableNamePK]) > 0) {
                                                    $arrDataId[] = $Data[$tTableNamePK];
                                                    unset($Data[$tTableNamePK]);

                                                    $Data[$this->primaryId['name']] = $this->primaryId['value'];

                                                    $this->dbClass->update($tTableName,
                                                        $Data,
                                                        $arrPK
                                                    );
                                                } else {
                                                    unset($Data[$tTableNamePK]);

                                                    if (isset($Data[$this->primaryId['name']])) {
                                                        $Data[$this->primaryId['name']] = $this->primaryId['value'];
                                                    }

                                                    $arrData = $this->dbClass->insert($tTableName,
                                                        $Data
                                                    );
                                                    if (isset($arrData['value'])) {
                                                        $arrDataId[] = $arrData['value'];
                                                    } else {
                                                        #$this->alert[] = $arrData['error'];
                                                        $this->alert[] = $arrData['error'].'<hr><pre>'.print_r($Data, true).'</pre>';
                                                    }
                                                }

                                                #print "\n\n";
                                                #print_r($Data);
                                                #print "\n\n";
                                                #print_r($arrPK);
                                                #print "\n\n";
                                            }
                                        }
                                    }

                                    #print_r($arrDataId);

                                    if (count($arrDataId) > 0) {
                                        $this->dbClass->execute("DELETE FROM {$tTableName} WHERE {$this->primaryId['name']} = {$this->primaryId['value']} AND {$tTableNamePK} NOT IN (" . implode(",", $arrDataId) . ")");
                                        //print_r("SELECT * FROM {$tTableName} WHERE {$this->primaryId['name']} = {$this->primaryId['value']} AND {$tTableNamePK} NOT IN (" . implode(",", $arrDataId) . ")");
                                    } else {
                                        #$arrData = $this->dbClass->select("SELECT * FROM {$tTableName} WHERE {$this->primaryId['name']} = {$this->primaryId['value']}");
                                        #if (count($arrData) == 0)
                                        $this->dbClass->execute("DELETE FROM {$tTableName} WHERE {$this->primaryId['name']} = {$this->primaryId['value']}");
                                    }

                                }

                                $cmsDataDetailsHasCol = true;
                                $cmsDataDetailsCol = array('cms_CreatedById', 'cms_CreatedByName', 'cms_CreatedDate', 'cms_ModifiedById', 'cms_ModifiedByName', 'cms_ModifiedDate', 'cms_Details');
                                foreach($cmsDataDetailsCol as $Index => $DataColName) {
                                    $arrCol = $this->dbClass->select(
                                        "
                                                        SELECT *
                                                        FROM information_schema.COLUMNS
                                                        WHERE
                                                            TABLE_SCHEMA = '{$this->dbClass->database}' AND
                                                            TABLE_NAME = '{$table_name}' AND
                                                            COLUMN_NAME = '{$DataColName}'
                                                    "
                                    );
                                    if (count($arrCol)==0) {
                                        $cmsDataDetailsHasCol = false;
                                    }
                                }
                                if ($cmsDataDetailsHasCol) {
                                    if ($this->postType == 0) {
                                        $cmsDate = date("Y-m-d H:i:s");
                                        $cmsDetails = array(
                                            'data_history'=>array(
                                                array(
                                                    'cms_CreatedById'=>CMS_Users_Id,
                                                    'cms_CreatedByName'=>CMS_Users_FullName,
                                                    'cms_CreatedDate'=>$cmsDate,
                                                    'cms_ModifiedById'=>CMS_Users_Id,
                                                    'cms_ModifiedByName'=>CMS_Users_FullName,
                                                    'cms_ModifiedDate'=>$cmsDate
                                                )
                                            )
                                        );

                                        $this->dbClass->update($table_name,
                                            array(
                                                'cms_CreatedById'=>CMS_Users_Id,
                                                'cms_CreatedByName'=>CMS_Users_FullName,
                                                'cms_CreatedDate'=>$cmsDate,
                                                'cms_ModifiedById'=>CMS_Users_Id,
                                                'cms_ModifiedByName'=>CMS_Users_FullName,
                                                'cms_ModifiedDate'=>$cmsDate,
                                                'cms_Details'=>json_encode($cmsDetails)
                                            ),
                                            $this->primaryId['value']
                                        );
                                    } else {
                                        $cmsDate = date("Y-m-d H:i:s");
                                        $arrData = $this->dbClass->select("SELECT cms_Details FROM {$table_name} WHERE ".$this->primaryId['name']."=".$this->primaryId['value']);
                                        if (count($arrData)>0) {
                                            $cmsDetails = json_decode($arrData[0]["cms_Details"], true);
                                            if (isset($cmsDetails['data_history'])) {
                                                $cmsDetails['data_history'][] =  array(
                                                    'cms_CreatedById'=>0,
                                                    'cms_CreatedByName'=>'',
                                                    'cms_CreatedDate'=>'',
                                                    'cms_ModifiedById'=>CMS_Users_Id,
                                                    'cms_ModifiedByName'=>CMS_Users_FullName,
                                                    'cms_ModifiedDate'=>$cmsDate
                                                );
                                            }
                                            $this->dbClass->update($table_name,
                                                array(
                                                    'cms_ModifiedById'=>CMS_Users_Id,
                                                    'cms_ModifiedByName'=>CMS_Users_FullName,
                                                    'cms_ModifiedDate'=>$cmsDate,
                                                    'cms_Details'=>json_encode($cmsDetails)
                                                ),
                                                $this->primaryId['value']
                                            );
                                        }
                                    }
                                }

                                if (!is_null($this->postSubmittedEvent['end'])) {
                                    if (is_callable($this->postSubmittedEvent['end'])) {
                                        $retEvent = $this->postSubmittedEvent['end']($this->primaryId);
                                    }
                                }

                                if (is_callable($fnPostComplete)) {
                                    $fnPostComplete();
                                }
                            }
                        }

                        #$this->alert = array_reverse($this->alert);

                        print json_encode(
                            array(
                                'primaryId'=>array('name'=>$this->primaryId['name'], 'value'=>$primaryId),
                                'dataRepeater'=>$retArrRepeater,
                                'alert'=>$this->alert,
                                'saveclose'=>$saveClose,
                                'selectedUrlPath'=>$this->selectedUrlPath,
                                'redirect'=>$this->postRedirect,
                                'submitted_results'=>$retEvent,
                                'error'=>$postError
                            )
                        );

                        exit;
                    }
                }


                if (isset($this->requestSlug[0])) {
                    #print $this->requestSlug[0].' '.$this->requestSlug[0]; exit;

                    if (is_numeric($this->requestSlug[0])) {
                        $this->postType = 1;

                        $this->primaryId['value'] = intval($this->requestSlug[0]);

                        if ($table_name!="") {
                            #print_r($this->getPrimaryControls()); exit;
                            #print_r(array_keys($this->getPrimaryControls())); exit;

                            $tArrFields = array_keys($this->getPrimaryControls());

                            foreach($tArrFields as $Index => $ColName) {
                                $arrCol = $this->dbClass->select(
                                    "
                                                        SELECT *
                                                        FROM information_schema.COLUMNS
                                                        WHERE
                                                            TABLE_SCHEMA = '{$this->dbClass->database}' AND
                                                            TABLE_NAME = '{$table_name}' AND
                                                            COLUMN_NAME = '{$ColName}'
                                    "
                                );
                                if (count($arrCol) == 0) {
                                    //REMOVE IF NOT FOUND
                                    unset($tArrFields[$Index]);
                                }
                            }

                            $this->postFields = $db->safe_select($table_name, $tArrFields, intval($this->requestSlug[0]));
                        }

                        #print '<pre>'; print_r($this->postFields);
                        #print_r($this->getPrimaryControls()); exit;

                        #print '<pre>';
                        #print_r($this->simpleXMLElementObjXPath($this->formLayoutData, 'body/panel/repeater'));
                        #print '</pre><hr>';

                        /*$repeaterObj = $this->simpleXMLElementObjXPath($this->formLayoutData, 'body/panel/control[@type="file"]');
                        foreach($repeaterObj as $controlObj) {
                            print $controlObj['type'].'<br>';
                        }
                        print '<hr><pre>';
                        print_r($repeaterObj);
                        print '</pre>';
                        exit;*/

                        if (is_object($this->formLayoutData)) {
                            #GET ALL PARENT REPEATER;
                            $repeaterObj = $this->simpleXMLElementObjXPath($this->formLayoutData, 'body/panel/repeater');
                            foreach($repeaterObj as $repeaterItem) {

                                $table_name = strval($repeaterItem['table_name']);
                                $table_where = strval($repeaterItem['table_where']);
                                $table_link_field = strval($repeaterItem['table_link_field']);
                                $table_order_by = strval($repeaterItem['table_order_by']);
                                $table_order_field = strval($repeaterItem['table_order_field']);

                                #print '<pre>';
                                #print_r(array_keys($this->getControlFields($repeaterItem)));
                                #print '</pre><hr>';

                                $repeaterSelectFields = array_keys($this->getControlFields($repeaterItem));

                                $dataPM = $db->select("SHOW KEYS FROM {$table_name} WHERE Key_name = 'PRIMARY'");
                                $repeaterSelectFields[] = $dataPM[0]['Column_name'];

                                $repeaterOrder = array();
                                if ($table_order_field!='') $repeaterOrder[] = $table_order_field;
                                if ($table_order_by!='') $repeaterOrder[] = $table_order_by;
                                $repeaterOrder[] = $dataPM[0]['Column_name'];

                                foreach($repeaterItem as $controlKey => $controlItem) {
                                    if ($controlKey == 'button') {
                                        if (isset($controlItem['id'])) {
                                            $repeaterSelectFields[] = strval($controlItem['id']);
                                        }
                                        break;
                                    }
                                }

                                $tArr = explode(' AND ', $table_where);
                                $tArrFilter = array($table_link_field=>intval($this->requestSlug[0]));
                                foreach($tArr as $subIndex => $subData) {
                                    $tArrSub = explode('=', $subData);
                                    if (count($tArrSub) == 2) {
                                        $tArrFilter[$tArrSub[0]] = $tArrSub[1];
                                    }
                                }

                                $repeaterData = $db->safe_select($table_name, $repeaterSelectFields, $tArrFilter, $repeaterOrder);
                                #$repeaterData = $db->safe_select($table_name, $repeaterSelectFields, array($table_link_field=>intval($this->requestSlug[0])), $repeaterOrder);
                                $this->postRepeaterFields[strval($repeaterItem['id'])] = $repeaterData;
                            }

                            #print '<pre>';
                            #print_r($this->postRepeaterFields);
                            #print '</pre>';
                            #exit;
                        }
                    }
                } else {
                    $this->postType = 0;
                }

                if (is_callable($fnPostAfterInitialize)) {
                    $fnPostAfterInitialize();
                }

                $this->cmsLoadView("cms/cms_post", 1, ['self'=>$this, 'CONFIG'=>$CONFIG, 'CMS_FN_MENU'=>$CMS_FN_MENU]);
                exit;
                #endregion
            } else {
                $tArr = explode('/', $this->selectedUrlPath);
                if (count($tArr) > 0) {
                    if ($tArr[count($tArr)-1] == 'index') {
                        unset($tArr[count($tArr)-1]);
                    }
                }
                header("location: ".implode('/', $tArr)."/list");
            }
        } else {
            $tArr = explode('/', $this->selectedUrlPath);

            if (count($tArr) > 0) {
                if ($tArr[count($tArr)-1] == 'index') {
                    unset($tArr[count($tArr)-1]);
                }
            }
            header("location: ".implode('/', $tArr)."/list");
        }
    }
    function callBackListDelete() {

    }
    function callBackPostInsert() {

    }
    function callBackPostUpdate() {

    }
    function dataTableAction($tableId, $buttonId, $fn) {
        $this->dataTableAction[$tableId][$buttonId] = $fn;
    }
    function dataTableButtonPost($tableId, $buttonId, $fn) {
        $this->dataTableButtonPost[$tableId][$buttonId] = $fn;
    }

    /*private function cmsLoadView($viewName) {
        include_once(APPPATH.'system/globals.php');
        include_once(APPPATH.'views/'.$viewName.'.php');
    }*/
    private function cmsLoadView($viewName, $type = 0 /* 0: include, 1: blade */, $variant = null) {
        include_once(APPPATH.'system/globals.php');
        if ($type == 0) {
            include_once(APPPATH.'views/'.$viewName.'.php');
        } else if ($type == 1) {
            include VENDORSPATH.'BladeOne/BladeOne.php';
            $blade=new \eftec\bladeone\BladeOne(APPPATH.'views', SITEROOTPATH.'compiles', \eftec\bladeone\BladeOne::MODE_DEBUG);
            echo $blade->run($viewName.".php", $variant);
        }
    }

    private function loadForm($formLayoutFile) {
        $xml =  simplexml_load_string(file_get_contents($formLayoutFile), "SimpleXMLElement", LIBXML_NOCDATA); #,
        return $xml;
    }
    private function dataTablePopulate($data) {
        global $CONFIG;

        $CMS_DEBUG = 'x0x0x0';

        if (isset($_GET['datatable'])) {
            $columns = array();

            // DB table to use
            $table = '';
            $table_view = '';
            $table_where = '';
            $arrQueries = array();

            // Table's primary key
            $primaryKey = '';

            $table_db_index = 0;

            foreach($this->formLayoutData->body->datatable as $child) {
                if (count($child->children())) {

                    if (strval($child->table['id']) == $_GET['datatable']) {
                        $this->dataTableSelected = strval($child->table['id']);

                        $table_db_index = (isset($child->table['table_db_index'])) ? intval($child->table['table_db_index']) : 0;
                        $db = new cmsDatabaseClass($table_db_index);

                        $tableId = strval($child->table['id']);
                        $table = (isset($this->dataTable[$tableId]['table_name'])) ? strval($this->dataTable[$tableId]['table_name']) : strval($child->table['table_name']);
                        $viewName = (isset($this->dataTable[$tableId]['view_name'])) ? strval($this->dataTable[$tableId]['view_name']) : strval($child->table['view_name']);
                        $primaryKey = (isset($this->dataTable[$tableId]['primary_key'])) ? strval($this->dataTable[$tableId]['primary_key']) : strval($child->table['primary_key']);

                        $arrData = $db->select("SHOW TABLES LIKE '{$table}';");
                        if (count($arrData) == 0) {
                            print "Table named {$table} not found. [{$table_db_index}]";
                            exit;
                        }

                        $table_where = (isset($this->dataTable[$tableId]['table_where'])) ? strval($this->dataTable[$tableId]['table_where']) : strval($child->table['table_where']);
                        $table_order_field = (isset($this->dataTable[$tableId]['table_order_field'])) ? strval($this->dataTable[$tableId]['table_order_field']) : strval($child->table['table_order_field']);
                        $table_order_by = (isset($this->dataTable[$tableId]['table_order_by'])) ? strval($this->dataTable[$tableId]['table_order_by']) : strval($child->table['table_order_by']);

                        $arrListColumn = array();
                        if ($table_order_field!='') $arrListColumn[$table_order_field] = 'asc';
                        if ($table_order_by!='') {
                            $tArr = explode(',', $table_order_by);
                            foreach($tArr as $Index => $Value) {
                                $tSArr = explode(' ', trim($Value));
                                $arrListColumn[$tSArr[0]] = (isset($tSArr[1])) ? (($tSArr[1]!='') ? strtolower($tSArr[1]) : 'asc') : 'asc';
                            }
                        }

                        #print $table_order_by;

                        $table_name_temp = '';
                        if (isset($this->dataTable[$tableId]['table_select'])) {
                            $table_name_temp = "{$this->dataTable[$tableId]['table_select']}";
                        } else {
                            if (isset($child->table['table_select'])) {
                                $table_name_temp = "{$child->table['table_select']}";
                            }
                        }

                        $arrQueries['table_count_query'] = (isset($this->dataTable[$tableId]['table_count_query'])) ? strval($this->dataTable[$tableId]['table_count_query']) : strval($child->table['table_count_query']);

                        /*if (isset($this->dataTable[$tableId]['table_select'])) {
                            $table_name_temp = "({$this->dataTable[$tableId]['table_select']}) AS {$table}";
                        } else {
                            if (isset($child->table['table_select'])) {
                                $table_name_temp = "({$child->table['table_select']}) AS {$table}";
                            }
                        }*/

                        $arrData = $db->select("SHOW KEYS FROM {$table} WHERE Key_name = 'PRIMARY'");
                        if (isset($arrData[0])) {
                            $primaryKey = $arrData[0]['Column_name'];
                        }


                        if ($table_where!='') {
                            preg_match_all('/\s*\[[^]]*\]/', $table_where, $match);

                            foreach($match[0] as $Index => $Value) {
                                $varField = str_replace('[', '', $Value);
                                $varField = str_replace(']', '', $varField);

                                if ($varField!='') {
                                    $tArr = explode('=', $varField);

                                    $getField = (isset($_GET[$tArr[0]])) ? $_GET[$tArr[0]] : $tArr[1];

                                    $table_where = str_replace($Value, $getField, $table_where);
                                }
                            }

                            $table_where = $table_where;
                        }

                        /*$table_view = '
                                (
                                    SELECT
                                        '.$table.'.*,
                                        '.$primaryKey.' AS cms_datatable_select,
                                        '.$primaryKey.' AS cms_datatable_action
                                    FROM
                                        '. (($table_name_temp=='') ? $table : $table_name_temp).'
                                ) AS '.$table;*/

                        $table_view = '';

                        #print_r($_GET['columns']); exit;

                        if ($table_name_temp=='') {
                            $table_view = '
                                    SELECT
                                        '.$table.'.*,
                                        '.$primaryKey.' AS cms_datatable_select,
                                        '.$primaryKey.' AS cms_datatable_action,
                                        '.$primaryKey.' AS cms_datatable_drag
                                    FROM
                                        '.$table.'
                            ';
                        } else {
                            $table_view = preg_replace('/SELECT/', '
                            SELECT
                            '.(($viewName=='') ? $table : $viewName).'.'.$primaryKey.' AS cms_datatable_select,
                            '.(($viewName=='') ? $table : $viewName).'.'.$primaryKey.' AS cms_datatable_action,
                            '.(($viewName=='') ? $table : $viewName).'.'.$primaryKey.' AS cms_datatable_drag,
                            ', $table_name_temp, 1);
                        }

                        $columns[] = array(
                            'db' => $primaryKey,
                            'dt' => 'DT_RowId',
                            'formatter' => function( $d, $row ) {
                                // Technically a DOM id cannot start with an integer, so we prefix
                                // a string. This can also be useful if you have multiple tables
                                // to ensure that the id is unique with a different prefix
                                return 'row_'.$d;
                            }
                        );

                        $dtColIndex = 0;
                        foreach($child->children() as $subChild) {
                            if (count($subChild->body->column) > 0) {
                                foreach($subChild->body->column as $subColumn) {

                                    if ($subColumn['type']=='data') {

                                        /*if (isset($_GET['columns'][$dtColIndex])) {
                                            if (isset($subColumn['table_owner'])) {
                                                $_GET['columns'][$dtColIndex]['table'] = strval($subColumn['table_owner']);
                                            }
                                        }*/

                                        $tVisible = (isset($subColumn['visible'])) ? filter_var(strval($subColumn['visible']), FILTER_VALIDATE_BOOLEAN) : true;

                                        if ($tVisible) {
                                            $columns[] = array(
                                                'db'=>strval($subColumn['fieldname']),
                                                'dt'=>strval($subColumn['fieldname']),
                                                'dt_owner'=>(isset($subColumn['table_owner']) ? strval($subColumn['table_owner']) : '')
                                            );

                                            if (isset($this->dataTableFormatter[strval($tableId)][strval($subColumn['fieldname'])])) {
                                                $columns[count($columns)-1]['formatter'] = $this->dataTableFormatter[strval($tableId)][strval($subColumn['fieldname'])];
                                            }
                                        }

                                    } else if ($subColumn['type']=='action') {

                                        $buttonsCount = (isset($subColumn->button)) ? count($subColumn->button) : 0;

                                        $arrActionButton = array();
                                        if ($buttonsCount > 0) {
                                            foreach($subColumn->button as $actionButton) {

                                                $tVisible = (isset($actionButton['visible'])) ? filter_var(strval($actionButton['visible']), FILTER_VALIDATE_BOOLEAN) : true;

                                                if ($tVisible) {
                                                    if ($actionButton['type'] == 'edit') {
                                                        $tHref = $this->selectedUrlPath.'/post';
                                                        $cmsUrl = '';
                                                        if (isset($actionButton['cms-url'])) {
                                                            $cmsUrl = strval($actionButton['cms-url']);
                                                            $cmsUrl = str_replace('[id]', '{$d}', $cmsUrl);
                                                            $cmsUrl = $this->layoutConfigCode($cmsUrl);
                                                            $tHref = $cmsUrl;
                                                        } else {
                                                            $tHref = $tHref.'/{$d}';
                                                        }
                                                        $className = (isset($actionButton['class'])) ? $actionButton['class'] : "fas fa-edit";
                                                        $arrActionButton[] = '<a href=\\"'.$tHref.'\\"><i class=\\"cms-action-btn '.$className.'\\" aria-hidden=\\"true\\"></i></a>';
                                                    } else if ($actionButton['type'] == 'delete') {
                                                        $arrActionButton[] = '<a href=\\"javascript:void(0)\\" data-table=\\"'.$tableId.'\\" data-id=\\"[id]\\" onclick=\\"cmsFnListActionDelete(this)\\"><i class=\\"cms-action-btn far fa-trash-alt\\" aria-hidden=\\"true\\"></i></a>';
                                                    } else if ($actionButton['type'] == 'post') {
                                                        $buttonId = (isset($actionButton['id'])) ? $actionButton['id'] : "";
                                                        $className = (isset($actionButton['class'])) ? $actionButton['class'] : "";
                                                        $arrActionButton[] = '<a href=\\"javascript:void(0)\\" id=\\"'.$buttonId.'_[id]\\" data-table=\\"'.$tableId.'\\" data-button=\\"'.$buttonId.'\\" data-id=\\"[id]\\" onclick=\\"cmsFnListActionPost(this)\\"><i class=\\"cms-action-btn '.$className.'\\" aria-hidden=\\"true\\"></i></a>';
                                                    } else if ($actionButton['type'] == 'custom') {
                                                        $link = (isset($actionButton['link'])) ? $actionButton['link'] : "";
                                                        $link = $this->layoutConfigCode($link);
                                                        $target = (isset($actionButton['target'])) ? ' target=\\"'.$actionButton['target'].'\\"' : "";
                                                        $className = (isset($actionButton['class'])) ? $actionButton['class'] : "";
                                                        $title = (isset($actionButton['title'])) ? $actionButton['title'] : "";
                                                        $buttonOnClick = (isset($actionButton['onclick'])) ? 'onclick=\\"'.$actionButton['onclick'].'\\"' : '';
                                                        $caption = strval($actionButton['caption']);
                                                        if (strpos($className, 'fa-') !== false) {
                                                            $arrActionButton[] = '<a href=\\"'.$link.'\\"'.$target.' title=\\"'.$title.'\\" '.$buttonOnClick.'><i class=\\"cms-action-btn '.$className.'\\" aria-hidden=\\"true\\"></i></a>';
                                                        } else {
                                                            if (!isset($actionButton['class_icon']))
                                                                $arrActionButton[] = '<a href=\\"'.$link.'\\"'.$target.' title=\\"'.$title.'\\" '.$buttonOnClick.' class=\\"'.$className.'\\">'.$caption.'</a>';
                                                            else
                                                                $arrActionButton[] = '<a href=\\"'.$link.'\\"'.$target.' title=\\"'.$title.'\\" '.$buttonOnClick.' class=\\"'.$className.'\\"><i class=\\"'.strval($actionButton['class_icon']).'\\" aria-hidden=\\"true\\"></i>'.(($caption!='') ? ' '.$caption : '').'</a>';
                                                        }
                                                    }
                                                }

                                            }
                                        }

                                        $actionButton = implode("", $arrActionButton);

                                        $buttonActionEval = <<<EOL
                                \$buttonActionFn = function(\$d, \$row) {
                                    \$retBtn = str_replace('[id]', \$d, "{$actionButton}");

                                    foreach(\$row as \$Index => \$Val) {
                                        \$retBtn = str_replace('['.\$Index.']', \$Val, \$retBtn);
                                    }

                                    \$retBtn = str_replace('[CMS_DATA_ROW]', base64_encode(json_encode(\$row)), \$retBtn);

                                    return \$retBtn;
                                };
EOL;
                                        eval($buttonActionEval);

                                        $columns[] = array(
                                            'db'        => 'cms_datatable_action',
                                            'dt'        => 'cms_datatable_action',
                                            'formatter' => $buttonActionFn
                                        );

                                    } else if ($subColumn['type']=='select') {
                                        $columns[] =     array(
                                            'db'        => 'cms_datatable_select',
                                            'dt'        => 'cms_datatable_select',
                                            'formatter' => function($d, $row) {
                                                $strRowData = base64_encode(json_encode($row));
                                                if (!empty($this->dataTableSelectFormatter)) {
                                                    if (isset($this->dataTableSelectFormatter[$this->dataTableSelected])) {
                                                        return $this->dataTableSelectFormatter[$this->dataTableSelected]($d, $row);
                                                    } else {
                                                        return "<input type=\"checkbox\" value=\"{$d}\" onclick=\"cmsFnDataTableSelect(this)\" cms-row-data=\"{$strRowData}\">";
                                                    }
                                                } else {
                                                    return "<input type=\"checkbox\" value=\"{$d}\" onclick=\"cmsFnDataTableSelect(this)\" cms-row-data=\"{$strRowData}\">";
                                                }

                                            }
                                        );
                                    } else if ($subColumn['type']=='drag') {
                                        $columns[] =     array(
                                            'db'        => 'cms_datatable_drag',
                                            'dt'        => 'cms_datatable_drag',
                                            'formatter' => function($d, $row) {
                                                $strRowData = base64_encode(json_encode($row));
                                                return "<img class=\"cmsDrag\" src=\"".RES_CMS_URL."images/icon-drag.png\" width=\"20\">";
                                            }
                                        );
                                    }

                                    if (isset($arrListColumn[strval($subColumn['fieldname'])])) {
                                        unset($arrListColumn[strval($subColumn['fieldname'])]);
                                    }

                                    $dtColIndex++;
                                }
                            }
                        }

                        foreach($arrListColumn as $Index => $Value) {
                            $columns[] =     array(
                                'db'        => $Index,
                                'dt'        => $Index
                            );
                        }

                    }
                }
            }

            // SQL server connection information
            $sql_details = array(
                'user' => $CONFIG['database'][$table_db_index]['username'],
                'pass' => $CONFIG['database'][$table_db_index]['password'],
                'db'   => $CONFIG['database'][$table_db_index]['name'],
                'host' => $CONFIG['database'][$table_db_index]['host'],
            );
            if (isset($CONFIG['database'][0]['ssl']['cert'])) {
                $sql_details['ssl'] = $CONFIG['database'][0]['ssl']['cert'];
            }

            /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
             * If you just want to use the basic configuration for DataTables with PHP
             * server-side, there is no need to edit below this line.
             */

            require_once(VENDORSPATH.'PHP-SQL-Parser/src/PHPSQLParser.php');
            require( VENDORSPATH.'DataTables/server/'.'ssp.class.php' );

            echo json_encode(
                SSP::simple( $_GET, $sql_details, $table, $primaryKey, $columns, $table_where, $table_view, APPPATH, $arrQueries, $viewName)
            );

            exit;
        }
    }

    private function recursiveFind(array $array, $needle) {
        $iterator = new RecursiveArrayIterator($array);
        $recursive = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
        $return = array();
        foreach ($recursive as $key => $value) {
            if ($key === $needle) {
                #$return[] = $value;

                $new_valueA = array();
                foreach($return as $key => $Item) {
                    if ($key == '@attributes') {
                        if (isset($Item['@attributes'])) {
                            $new_valueA[] = $Item['@attributes'];
                        } else {
                            $new_valueA[] = $Item;
                        }
                    } else {
                        $new_valueA[] = $Item;
                    }
                }

                $new_valueB = array();
                foreach($value as $key => $Item) {
                    if ($key == '@attributes') {
                        #$new_valueB[] = array('@attributes'=>$Item);
                        if (isset($Item['@attributes'])) {
                            $new_valueB[] = array('@attributes'=>$Item['@attributes']);
                        } else {
                            $new_valueB[] = array('@attributes'=>$Item);
                        }
                    } else {
                        $new_valueB[] = $Item;
                    }
                }

                $return = array_merge($new_valueA, $new_valueB);
                #$return = array_merge($return, $value);
            }
        }
        return $return;
    }

    private function recursiveUnset(&$array, $unwanted_key) {
        unset($array[$unwanted_key]);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->recursiveUnset($value, $unwanted_key);
            }
        }
    }

    private function getPrimaryControls() {
        $arrReturn = array();

        if (isset($this->formLayoutData->body)) {
            $tArr = json_decode(json_encode($this->formLayoutData->body), true);
            $this->recursiveUnset($tArr, 'repeater');
            $tArr = $this->recursiveFind($tArr, 'control');

            //print_r($tArr); exit;

            foreach($tArr as $arrObj) {
                if (isset($arrObj['@attributes'])) {
                    if ($arrObj['@attributes']['type']!='datatable')
                        $arrReturn[$arrObj['@attributes']['id']] = $arrObj['@attributes'];
                } else {
                    if (isset($arrObj['type'])) {
                        if ($arrObj['type']!='datatable')
                            $arrReturn[$arrObj['id']] = $arrObj;
                    }
                }
            }
        }

        return $arrReturn;
    }

    private function getControlFields($simpleXMLElementArr) {
        $tArr = json_decode(json_encode($simpleXMLElementArr), true);
        $this->recursiveUnset($tArr, 'repeater');
        $tArr = $this->recursiveFind($tArr, 'control');

        $arrReturn = array();
        foreach($tArr as $arrObj) {
            if (isset($arrObj['@attributes'])) $arrReturn[$arrObj['@attributes']['id']] = $arrObj['@attributes'];
        }

        return $arrReturn;
    }

    private function simpleXMLElementObjXPath($pArray = array(), $pXPath) {
        $doc = new DOMDocument();
        $doc->formatOutput = TRUE;
        $doc->loadXML($pArray->asXML());
        $xml = $doc->saveXML();

        $sXml = new SimpleXMLElement($xml);
        $result = $sXml->xpath($pXPath);

        return $result;
    }

    function batchUpload($pDataTableId) {
        global $CONFIG;

        if (isset($_POST['cmsBatchUpload'])) {
            #print_r($_FILES);


            foreach($this->formLayoutData->body->datatable as $child) {
                if (count($child->children())) {

                    if (strval($child->table['id']) == $pDataTableId) {
                        $table_db_index = (isset($child->table['table_db_index'])) ? intval($child->table['table_db_index']) : 0;
                        $db = new cmsDatabaseClass($table_db_index);

                        $tableId = strval($child->table['id']);
                        $table = (isset($this->dataTable[$tableId]['table_name'])) ? strval($this->dataTable[$tableId]['table_name']) : strval($child->table['table_name']);
                        $viewName = (isset($this->dataTable[$tableId]['view_name'])) ? strval($this->dataTable[$tableId]['view_name']) : strval($child->table['view_name']);
                        $primaryKey = (isset($this->dataTable[$tableId]['primary_key'])) ? strval($this->dataTable[$tableId]['primary_key']) : strval($child->table['primary_key']);
                        $batchUploadField = strval($child->table['batch_upload_field']);
                        $batchUploadDir = strval($child->table['batch_upload_dir']);

                        $batchUploadDataField = strval($child->table['batch_upload_data_field']); $arrBatchUploadDataField = ($batchUploadDataField != '') ? explode(',', $batchUploadDataField) : array();
                        $batchUploadDataValue = strval($child->table['batch_upload_data_value']); $arrBatchUploadDataValue = ($batchUploadDataValue != '') ? explode(',', $batchUploadDataValue) : array();

                        $arrData = $db->select("SHOW TABLES LIKE '{$table}';");
                        if (count($arrData) == 0) {
                            print "Table named {$table} not found. [{$table_db_index}]";
                            exit;
                        }

                        $table_where = (isset($this->dataTable[$tableId]['table_where'])) ? strval($this->dataTable[$tableId]['table_where']) : strval($child->table['table_where']);
                        $table_order_field = (isset($this->dataTable[$tableId]['table_order_field'])) ? strval($this->dataTable[$tableId]['table_order_field']) : strval($child->table['table_order_field']);
                        $table_order_by = (isset($this->dataTable[$tableId]['table_order_by'])) ? strval($this->dataTable[$tableId]['table_order_by']) : strval($child->table['table_order_by']);

                        $table_name_temp = '';
                        if (isset($this->dataTable[$tableId]['table_select'])) {
                            $table_name_temp = "{$this->dataTable[$tableId]['table_select']}";
                        } else {
                            if (isset($child->table['table_select'])) {
                                $table_name_temp = "{$child->table['table_select']}";
                            }
                        }

                        $primaryKey = '';
                        $arrData = $db->select("SHOW KEYS FROM {$table} WHERE Key_name = 'PRIMARY'");
                        if (isset($arrData[0])) {
                            $primaryKey = $arrData[0]['Column_name'];
                        }


                        if ($table_where!='') {
                            preg_match_all('/\s*\[[^]]*\]/', $table_where, $match);

                            foreach($match[0] as $Index => $Value) {
                                $varField = str_replace('[', '', $Value);
                                $varField = str_replace(']', '', $varField);

                                if ($varField!='') {
                                    $tArr = explode('=', $varField);

                                    $getField = (isset($_GET[$tArr[0]])) ? $_GET[$tArr[0]] : $tArr[1];

                                    $table_where = str_replace($Value, $getField, $table_where);
                                }
                            }

                            $table_where = $table_where;
                        }

                        $table_view = '';


                        if ($table_name_temp=='') {
                            $table_view = '
                                    SELECT
                                        '.$table.'.*
                                    FROM
                                        '.$table.'
                            ';
                        } else {
                            $table_view = preg_replace('/SELECT/', '
                            SELECT
                            '.(($viewName=='') ? $table : $viewName).'.'.$primaryKey.' AS cms_datatable_select,
                            '.(($viewName=='') ? $table : $viewName).'.'.$primaryKey.' AS cms_datatable_action,
                            ', $table_name_temp, 1);
                        }

                        $table_view .= ($table_where!='') ? " WHERE {$table_where}" : '';

                        $table_view .= ($table_order_by!='') ? " ORDER BY {$table_order_by}" : '';

                        $arrData = $db->select("{$table_view}");

                        if (!is_null($this->batchUploadEvent['start'])) {
                            if (is_callable($this->batchUploadEvent['start'])) {
                                $this->batchUploadEvent['start']($arrBatchUploadDataField, $arrBatchUploadDataValue);
                            }
                        }

                        $orderCounter = 0;
                        if ($table_order_field!='') {
                            $Index = 0;
                            foreach($arrData as $Index => $Data) {
                                $db->update($table,
                                    array(
                                        $table_order_field=>$Index
                                    ),
                                    $Data[$primaryKey]
                                );
                            }
                            $orderCounter = $Index;
                        }

                        #print_r("{$table_view}");
                        #print_r($arrData);
                        #exit;

                        #print $batchUploadField;

                        #print_r($_FILES['cmsFile']);

                        $arrOrder = json_decode($_POST['cmsBatchUploadOrder'], true);

                        #print_r($arrOrder);

                        $orderCounter++;

                        $out = '';
                        $arrNewId = array();

                        foreach($arrOrder as $orderIndex => $orderValue) {
                            $Index = intval($orderValue);

                            $tPathInfo = pathinfo($_FILES['cmsFile']['name']);
                            $tFile = cmsTools::makeSlug($tPathInfo['filename']).".".$tPathInfo['extension'];

                            $tArr = array(
                                $batchUploadField=>''
                            );

                            if ($table_order_field!='') {
                                $tArr[$table_order_field] = $orderCounter;
                            }

                            if (count($arrBatchUploadDataField) > 0) {
                                foreach($arrBatchUploadDataField as $subIndex => $subData) {
                                    $tArr[$subData] = $arrBatchUploadDataValue[$subIndex];
                                }
                            }

                            $tArrNew = $db->insert($table,
                                $tArr
                            );

                            $uploadedDir = $batchUploadDir . '/' . $tArrNew['value'] . '/' . cmsTools::makeSlug($batchUploadField);

                            $tArrPathInfo = pathinfo($_FILES['cmsFile']['name']);

                            $db->update($table,
                                array(
                                    $batchUploadField=>json_encode(
                                        array(
                                            'name'=>$tFile, /*cmsTools::makeSlug($_FILES['cmsFile']['name'][$Index]),*/
                                            'path'=>$uploadedDir,
                                            'base_path'=>'',
                                            'file_type'=>$tArrPathInfo['extension']
                                        )
                                    )
                                ),
                                $tArrNew['value']
                            );

                            $uploadedDir = SITEROOTPATH . $CONFIG['cms']['directory_upload_name'] . '/' . $uploadedDir;

                            if (!is_dir($uploadedDir)) {
                                mkdir($uploadedDir, 0777, true);
                            }

                            move_uploaded_file($_FILES['cmsFile']['tmp_name'], "{$uploadedDir}/{$tFile}");

                            #print "{$uploadedDir}/{$tFile}\n";

                            $arrNewId[] = $tArrNew['value'];

                            ob_start();
                            print $tArrNew['value'];
                            $out = ob_get_contents();
                            $orderCounter++;
                        }

                        if (!is_null($this->batchUploadEvent['end'])) {
                            if (is_callable($this->batchUploadEvent['end'])) {
                                $this->batchUploadEvent['end']($arrBatchUploadDataField, $arrBatchUploadDataValue, $arrNewId);
                            }
                        }

                        ob_end_clean();
                        print $out;
                    }
                }

            }

            exit;
        }

        if (isset($_POST['cmsBatchUploadOrder'])) {
            $arrOrder = json_decode($_POST['cmsBatchUploadOrder']);

            foreach($this->formLayoutData->body->datatable as $child) {
                if (count($child->children())) {

                    if (strval($child->table['id']) == $pDataTableId) {
                        $table_db_index = (isset($child->table['table_db_index'])) ? intval($child->table['table_db_index']) : 0;
                        $db = new cmsDatabaseClass($table_db_index);

                        $tableId = strval($child->table['id']);
                        $table = (isset($this->dataTable[$tableId]['table_name'])) ? strval($this->dataTable[$tableId]['table_name']) : strval($child->table['table_name']);
                        $viewName = (isset($this->dataTable[$tableId]['view_name'])) ? strval($this->dataTable[$tableId]['view_name']) : strval($child->table['view_name']);
                        $primaryKey = (isset($this->dataTable[$tableId]['primary_key'])) ? strval($this->dataTable[$tableId]['primary_key']) : strval($child->table['primary_key']);
                        $batchUploadField = strval($child->table['batch_upload_field']);
                        $batchUploadDir = strval($child->table['batch_upload_dir']);

                        $batchUploadDataField = strval($child->table['batch_upload_data_field']); $arrBatchUploadDataField = ($batchUploadDataField != '') ? explode(',', $batchUploadDataField) : array();
                        $batchUploadDataValue = strval($child->table['batch_upload_data_value']); $arrBatchUploadDataValue = ($batchUploadDataValue != '') ? explode(',', $batchUploadDataValue) : array();

                        $arrData = $db->select("SHOW TABLES LIKE '{$table}';");
                        if (count($arrData) == 0) {
                            print "Table named {$table} not found. [{$table_db_index}]";
                            exit;
                        }

                        $table_where = (isset($this->dataTable[$tableId]['table_where'])) ? strval($this->dataTable[$tableId]['table_where']) : strval($child->table['table_where']);
                        $table_order_field = (isset($this->dataTable[$tableId]['table_order_field'])) ? strval($this->dataTable[$tableId]['table_order_field']) : strval($child->table['table_order_field']);
                        $table_order_by = (isset($this->dataTable[$tableId]['table_order_by'])) ? strval($this->dataTable[$tableId]['table_order_by']) : strval($child->table['table_order_by']);

                        $table_name_temp = '';
                        if (isset($this->dataTable[$tableId]['table_select'])) {
                            $table_name_temp = "{$this->dataTable[$tableId]['table_select']}";
                        } else {
                            if (isset($child->table['table_select'])) {
                                $table_name_temp = "{$child->table['table_select']}";
                            }
                        }

                        $primaryKey = '';
                        $arrData = $db->select("SHOW KEYS FROM {$table} WHERE Key_name = 'PRIMARY'");
                        if (isset($arrData[0])) {
                            $primaryKey = $arrData[0]['Column_name'];
                        }


                        if ($table_where!='') {
                            preg_match_all('/\s*\[[^]]*\]/', $table_where, $match);

                            foreach($match[0] as $Index => $Value) {
                                $varField = str_replace('[', '', $Value);
                                $varField = str_replace(']', '', $varField);

                                if ($varField!='') {
                                    $tArr = explode('=', $varField);

                                    $getField = (isset($_GET[$tArr[0]])) ? $_GET[$tArr[0]] : $tArr[1];

                                    $table_where = str_replace($Value, $getField, $table_where);
                                }
                            }

                            $table_where = $table_where;
                        }

                        $table_view = '';


                        if ($table_name_temp=='') {
                            $table_view = '
                                    SELECT
                                        '.$table.'.*
                                    FROM
                                        '.$table.'
                            ';
                        } else {
                            $table_view = preg_replace('/SELECT/', '
                            SELECT
                            '.(($viewName=='') ? $table : $viewName).'.'.$primaryKey.' AS cms_datatable_select,
                            '.(($viewName=='') ? $table : $viewName).'.'.$primaryKey.' AS cms_datatable_action,
                            ', $table_name_temp, 1);
                        }

                        $table_view .= ($table_where!='') ? " WHERE {$table_where}" : '';

                        $table_view .= ($table_order_by!='') ? " ORDER BY {$table_order_by}" : '';

                        $arrData = $db->select("{$table_view}");


                        $orderCounter = 0;
                        if ($table_order_field!='') {
                            $Index = 0;
                            foreach($arrData as $Index => $Data) {
                                $db->update($table,
                                    array(
                                        $table_order_field=>$Index
                                    ),
                                    $Data[$primaryKey]
                                );
                            }
                            $orderCounter = $Index;
                        }
                        $orderCounter++;


                        foreach($arrOrder as $orderIndex => $orderValue) {
                            $tArr = array();

                            if ($table_order_field!='') {
                                $tArr[$table_order_field] = $orderCounter;

                                $tArrNew = $db->update($table,
                                    $tArr,
                                    $orderValue[1]
                                );
                            }

                            $orderCounter++;
                        }


                    }
                }

            }

            print_r($arrOrder);

            exit;
        }

        if (isset($_GET['cms-batch-upload'])) {
            $this->loadView("cms/cms_batch_uploads");
            exit;
        }
    }

}
