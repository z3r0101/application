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

class Model extends BaseController {
    #region -- GLOBALS --
    public $dbClass = null;
    #endregion

    function __construct() {
        $this->dbClass = new cmsDatabaseClass();
    }
}
