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

class cms_select
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
        $tCaption = (isset($this->controlObj['caption'])) ? '<label for="'.$tId.'">'.$this->controlObj['caption'].'</label>' : '';
        $tPlaceHolder = (isset($this->controlObj['placeholder'])) ? 'placeholder="'.$this->controlObj['placeholder'].'"' : '';
        $tValue = ($this->data !== NULL) ? $this->data : '';
        $tGroup = (isset($this->controlObj['group'])) ? 'cms-group="'.strval($this->controlObj['group']).'"' : "";
        if ($tGroup!='') {
            if ($this->group_name!=strval($this->controlObj['group'])) {
                $tGroup .= ' style="display: none"';
            }
        }

        $tArrOptions = array();
        foreach($this->controlObj->option as $Option) {

            $vars = get_object_vars ( $Option );
            $arrAttributes = array();
            foreach($vars['@attributes'] as $Key => $Val) {
                $arrAttributes[] = strval($Key).'="'.strval($Val).'"';
            }

            $tArrOptions[] = '<option '.implode(' ', $arrAttributes).'>'.strval($Option).'</option>';
        }
        $tOptions = implode('', $tArrOptions);

        $tClass = (!$this->isRepeaterControl) ? 'cms-form-control' : 'cms-form-control-repeater';
        $tContainerStyle = (isset($this->controlObj['container-style'])) ? 'style="'.$this->controlObj['container-style'].'"' : '';
        $tContainerObjStyle = (isset($this->controlObj['container-obj-style'])) ? 'style="'.$this->controlObj['container-obj-style'].'"' : '';
        $tContainerObjClass = (isset($this->controlObj['container-obj-class'])) ? 'class="'.$this->controlObj['container-obj-class'].'"' : '';

        $strHTMLOutLeft = "";
        $strHTMLOutRight = "";
        $inputGroupAlign = "right";
        foreach($this->controlObj->children() as $tagObj) {
            if (strval($tagObj->getName())=='input-group') {

                $inputGroupAlign = (isset($tagObj["align"])) ? ($tagObj["align"]!='' ? $tagObj["align"] : "right") : "right";
                if ($inputGroupAlign == "left") {
                    $strHTMLOutLeft .= strval($tagObj->children()->asXML());
                } else {
                    $strHTMLOutRight .= strval($tagObj->children()->asXML());
                }
            }
        }

        $tStyle = (isset($this->controlObj['style'])) ? 'style="'.$this->controlObj['style'].'""' : '';

        return <<<EOL
            <div class="form-group" {$tGroup} {$tContainerStyle}>
                {$tCaption}
                <div {$tContainerObjClass} {$tContainerObjStyle}>
                {$strHTMLOutLeft}
                <select class="form-control {$tClass}" id="{$tId}" name="{$tName}" {$tPlaceHolder} {$tStyle}>{$tOptions}</select>
                {$strHTMLOutRight}
                </div>
            </div>
            <script>
                $('#{$tId}').val('{$tValue}');
            </script>
EOL;

    }
}