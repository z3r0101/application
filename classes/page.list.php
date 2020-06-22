<?php
/*
 *  @author:        ryanzkizen@gmail.com
 *  @version:       1.0
 */

class page_list {
    public $router = array();

    #region -- GLOBALS --
    public $CONFIG = array();
    private $testProp = array();
    #endregion


    function __construct() {
        global $CONFIG;
        $this->CONFIG = $CONFIG;
    }

    function setDataTable($tableId, $templateName = "") {
        $this->testProp[$tableId]['template_name'] = $templateName;
    }



    function getDataTable($tableId) {
        return $this->testProp[$tableId];
    }


}
