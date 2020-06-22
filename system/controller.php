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

class BaseController {
    public $router = array();

    #region -- GLOBALS --
    public $CONFIG = array();
    public $requestSlug = array();

    public $selectedClass = "";
    public $selectedMethod = "";

    #endregion


    function __construct() {
        global $CONFIG;
        $this->CONFIG = $CONFIG;
        $this->dbClass = new cmsDatabaseClass();
    }

    function router($methodName, $alterName) {
        $this->router[$alterName] = $methodName;
    }

    function activeURL() {
        global $routes;
        $ret = '';

        $arr = array_filter($routes,
            function ($item) {
                return ($item == $this->selectedMethod);
            }
        );
        if (count($arr) > 0) {
            $arrKey = array_keys($arr);
            if (count($arrKey) > 0) {
                $ret = $arrKey[0] . ((count($this->requestSlug) > 0) ? '/' . implode('/', $this->requestSlug) : '');
            }
        } else {
            $ret = $this->selectedMethod . ((count($this->requestSlug) > 0) ? '/' . implode('/', $this->requestSlug) : '');
        }

        if ($ret == 'index') $ret = '';

        return $ret;
    }

    function loadView($viewName, $type = 0 /* 0: include, 1: blade */, $variant = null) {
        include_once(APPPATH.'system/globals.php');
        if ($type == 0) {
            if (file_exists(WWWPATH . 'www/views/' . $viewName . '.php')) {
                include_once(WWWPATH . 'www/views/' . $viewName . '.php');
            } else {
                include_once(APPPATH . 'views/' . $viewName . '.php');
            }
        } else if ($type == 1) {
            include VENDORSPATH.'BladeOne/BladeOne.php';

            if (is_array($viewName)) {
                if (!isset($this->requestSlug[0])) {
                    if (!(strpos($viewName[''], '/') !== false)) {
                        $viewName[''] = '/'.$viewName[''];
                    }

                    $variant['view_name'] = $viewName[''];

                    if (file_exists(WWWPATH . 'www/views/'.$viewName[''].".php")) {
                        $blade = new \eftec\bladeone\BladeOne(WWWPATH . 'www/views', null, \eftec\bladeone\BladeOne::MODE_DEBUG);
                    } else {
                        $blade = new \eftec\bladeone\BladeOne(APPPATH . 'views', null, \eftec\bladeone\BladeOne::MODE_DEBUG);
                    }

                    echo $blade->run($viewName[''].".php", $variant);
                } else {
                    if (isset($viewName[$this->requestSlug[0]])) {
                        if (!(strpos($viewName[$this->requestSlug[0]], '/') !== false)) {
                            $viewName[$this->requestSlug[0]] = '/' . $viewName[$this->requestSlug[0]];
                        }

                        $variant['view_name'] = $viewName[$this->requestSlug[0]];

                        if (file_exists(WWWPATH . 'www/views/'.$viewName[$this->requestSlug[0]] . ".php")) {
                            $blade = new \eftec\bladeone\BladeOne(WWWPATH . 'www/views', null, \eftec\bladeone\BladeOne::MODE_DEBUG);
                        } else {
                            $blade = new \eftec\bladeone\BladeOne(APPPATH . 'views', null, \eftec\bladeone\BladeOne::MODE_DEBUG);
                        }

                        echo $blade->run($viewName[$this->requestSlug[0]] . ".php", $variant);
                    }
                }
            } else {
                if (!(strpos($viewName, '/') !== false)) {
                    $viewName = '/'.$viewName;
                }

                $variant['view_name'] = $viewName;

                if (file_exists(WWWPATH . 'www/views/'.$viewName.".php")) {
                    $blade = new \eftec\bladeone\BladeOne(WWWPATH . 'www/views', null, \eftec\bladeone\BladeOne::MODE_DEBUG);
                } else {
                    $blade = new \eftec\bladeone\BladeOne(APPPATH . 'views', null, \eftec\bladeone\BladeOne::MODE_DEBUG);
                }

                echo $blade->run($viewName.".php", $variant);
            }

            exit;
        }
    }

    function loadHelper($helperName) {
        global $CONFIG;
        if (file_exists(APPPATH.'helpers/'.$helperName.'.php')) {
            include_once(APPPATH . 'helpers/' . $helperName . '.php');
        } else if (file_exists(WWWPATH.'www/helpers/'.$helperName.'.php')) {
            include_once(WWWPATH . 'www/helpers/' . $helperName . '.php');
        } else {
            print pageError("PHP script not found", "Check if the PHP script located in helpers directory.");
            exit;
        }
    }

    function loadModel($modelName) {
        include_once(APPPATH.'models/'.$modelName.'.php');
        $tArr = explode('/', $modelName);
        return new $tArr[count($tArr)-1]();
    }

    function renderContentBlock($pContent, $fn)
    {
        $dom = new DomDocument();
        $internalErrors = libxml_use_internal_errors(true);
        if ($pContent != '') {
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $pContent);
            $XPath = new DomXPath($dom);
            $divMCENonEditable = $XPath->query('//*[@class="mceNonEditable"]');
            if (!is_null($divMCENonEditable)) {


                for ($i=0; $i<$divMCENonEditable->length; $i++) {

                    $cmsBlockTitle = $XPath->query('//a', $divMCENonEditable->item($i));

                    $cmsBlockData = $divMCENonEditable->item($i)->getElementsByTagName('z3r0101'); #$XPath->query('//div[@class="cms-block-data"]', $divMCENonEditable->item($i));
                    $arrHTMLBlock = array();

                    for ($ii=0; $ii<$cmsBlockData->length; $ii++) {
                        $arrHTMLControl = array();
                        $cmsBlockDataControls = $cmsBlockData->item($ii)->getElementsByTagName('div');
                        for ($iii=0; $iii<$cmsBlockDataControls->length; $iii++) {
                            $tFn = array();
                            $tFn['id'] = $cmsBlockDataControls->item($iii)->getAttribute('cms-block-control');
                            $tFn['value'] = $cmsBlockDataControls->item($iii)->nodeValue;
                            $tFn['type'] = $cmsBlockDataControls->item($iii)->getAttribute('cms-block-type');
                            $tFn['render_type'] = 2;

                            if ($cmsBlockDataControls->item($iii)->getAttribute('cms-block-type') == 'image') {
                                $arrHTMLControl[] = (!is_callable($fn)) ? '<div><img src="'.$cmsBlockDataControls->item($iii)->nodeValue.'"></div>' : ($fn($tFn) == '' ? '<div><img src="'.$cmsBlockDataControls->item($iii)->nodeValue.'"></div>' : $fn($tFn));
                            } else {
                                $arrHTMLControl[] = (!is_callable($fn)) ? '<div>'.$cmsBlockDataControls->item($iii)->nodeValue.'</div>' : ($fn($tFn) == '' ? '<div>'.$cmsBlockDataControls->item($iii)->nodeValue.'</div>' : $fn($tFn));
                            }
                        }

                        $tFn = array();
                        $tFn['value'] = implode("\n", $arrHTMLControl);
                        $tFn['render_type'] = 1;
                        $arrHTMLBlock[] = (!is_callable($fn)) ? '<div>'.implode("\n", $arrHTMLControl).'</div>' : ($fn($tFn) == '' ? '<div>'.implode("\n", $arrHTMLControl).'</div>' : $fn($tFn));
                    }
                    if (count($arrHTMLBlock)>0) {
                        $helper = new DOMDocument();

                        $tFn = array();
                        $tFn['value'] = implode("\n", $arrHTMLBlock);
                        $tFn['render_type'] = 0;
                        $tHTML = (!is_callable($fn)) ? '<div>'.implode("\n", $arrHTMLBlock).'</div>' : ($fn($tFn) == '' ? '<div>'.implode("\n", $arrHTMLBlock).'</div>' : $fn($tFn));

                        $strBlockTitle = '<p><strong>'.$cmsBlockTitle->item(0)->textContent.'</strong></p>';
                        if ($cmsBlockTitle->item(0)->getAttribute('class') == 'block-no-title') {
                            $strBlockTitle = '';
                        }
                        $helper->loadHTML('<?xml encoding="utf-8" ?>'.$strBlockTitle.$tHTML."\n\n");

                        $divElement = $divMCENonEditable->item($i);
                        $divElement->parentNode->replaceChild($dom->importNode($helper->documentElement, true), $divElement);
                    }
                }
                $pContent = cmsTools::DOMInnerHTML($dom->getElementsByTagName("body")->item(0));
                libxml_use_internal_errors($internalErrors);
            }
        }

        return $pContent;
    }
}
