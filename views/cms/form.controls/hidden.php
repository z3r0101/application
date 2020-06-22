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

class cms_hidden
{
    private $controlObj = NULL;
    private $data = NULL;

    public $id = NULL;
    public $isRepeaterControl = false;

    function __construct($controlObj)
    {
        #parent::__construct();

        $this->controlObj = $controlObj;

        if (isset($this->controlObj['value'])) {
            $this->data = strval($this->controlObj['value']);
        }
    }

    function value($data) {
        $this->data = $data;
    }

    function render() {

        $tId = ($this->id === NULL) ? $this->controlObj['id'] : $this->id;
        $tName = strval($this->controlObj['id']);
        $tValue = ($this->data !== NULL) ? $this->data : '';

        $tClass = (!$this->isRepeaterControl) ? 'cms-form-control' : 'cms-form-control-repeater';

        $tNoDb = (isset($this->controlObj['nodb'])) ? 'nodb="'.$this->controlObj['nodb'].'"' : '';

        return <<<EOL
            <input type="hidden" class="form-control {$tClass}" id="{$tId}" name="{$tName}" value="{$tValue}" {$tNoDb}>
            <script>
                $('#{$tId}').val('{$tValue}');
            </script>
EOL;

    }
}