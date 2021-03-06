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

class cms_textarea
{
    private $controlObj = NULL;
    private $data = NULL;

    public $id = NULL;
    public $isRepeaterControl = false;
    public $group_name = "";

    function __construct($controlObj)
    {
        #parent::__construct();

        $this->controlObj = $controlObj;
    }

    function value($data) {
        $this->data = $data;
    }

    function render() {
        $tId = ($this->id === NULL) ? $this->controlObj['id'] : $this->id;
        $tName = strval($this->controlObj['id']);
        $tCaption = (isset($this->controlObj['caption'])) ? '<label for="'.$tId.'">'.$this->controlObj['caption'].'</label>' : '';
        $tPlaceHolder = (isset($this->controlObj['placeholder'])) ? 'placeholder="'.$this->controlObj['placeholder'].'"' : '';
        $tValue = ($this->data !== NULL) ? $this->data : '';

        $tGroup = (isset($this->controlObj['group'])) ? 'cms-group="'.strval($this->controlObj['group']).'"' : "";
        if ($tGroup!='') {
            if ($this->group_name!=strval($this->controlObj['group'])) {
                $tGroup .= ' style="display: none"';
            }
        }

        $tClass = (!$this->isRepeaterControl) ? 'cms-form-control' : 'cms-form-control-repeater';

        $tValue = base64_encode($tValue);

        $tReadonly = (isset($this->controlObj['readonly'])) ? 'readonly="'.$this->controlObj['readonly'].'""' : '';

        $tContainerStyle = (isset($this->controlObj['container-style'])) ? 'style="'.$this->controlObj['container-style'].'"' : '';
        $tContainerObjStyle = (isset($this->controlObj['container-obj-style'])) ? 'style="'.$this->controlObj['container-obj-style'].'"' : '';

        $tStyle = (isset($this->controlObj['style'])) ? 'style="'.$this->controlObj['style'].'""' : '';

        return <<<EOL
            <div class="form-group" {$tGroup} {$tContainerStyle}>
                <div {$tContainerObjStyle}>
                    {$tCaption}
                    <textarea class="form-control {$tClass}" id="{$tId}" name="{$tName}" {$tPlaceHolder} {$tReadonly} {$tStyle}></textarea>
                </div>
            </div>
            <script>
                $('#{$tId}').val(base64_decode('{$tValue}'));
            </script>
EOL;
    }
}