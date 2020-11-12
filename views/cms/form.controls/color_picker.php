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

class cms_color_picker
{
    private $controlObj = NULL;
    private $data = NULL;

    public $id = NULL;
    public $isRepeaterControl = false;
    public $repeater_style = "";
    public $group_name = "";

    public $vendor_js_path = array('bootstrap-colorpicker/js/bootstrap-colorpicker.min.js');
    public $vendor_css_path = array('bootstrap-colorpicker/css/bootstrap-colorpicker.min.css');

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
        $tValue = ($this->data !== NULL) ? $this->data : ((isset($this->controlObj['value']) ? $this->controlObj['value'] : ''));
        $tGroup = (isset($this->controlObj['group'])) ? 'cms-group="'.strval($this->controlObj['group']).'"' : "";
        if ($tGroup!='') {
            if ($this->group_name!=strval($this->controlObj['group'])) {
                $tGroup .= ' style="display: none"';
            }
        }

        $tContainerStyle = (isset($this->controlObj['container-style'])) ? 'style="'.$this->controlObj['container-style'].'"' : '';
        $tContainerObjStyle = (isset($this->controlObj['container-obj-style'])) ? 'style="'.$this->controlObj['container-obj-style'].'"' : '';
        $tContainerObjClass = (isset($this->controlObj['container-obj-class'])) ? 'class="'.$this->controlObj['container-obj-class'].'"' : '';

        $tClass = (!$this->isRepeaterControl) ? 'cms-form-control' : 'cms-form-control-repeater';

        $tControlStyle = (isset($this->controlObj['control_style'])) ? $this->controlObj['control_style'] : '';
        $tReadonly = (isset($this->controlObj['readonly'])) ? 'readonly="'.$this->controlObj['readonly'].'"' : '';

        $tValue = base64_encode($tValue);

        if ($this->isRepeaterControl) {

            if (isset($this->repeater_style)) {
                if ($this->repeater_style=='table') $tCaption = '';
            }
        }

        $tInputType = (isset($this->controlObj['input-type'])) ? $this->controlObj['input-type'] : 'text';
        $tInputDateClear = (isset($this->controlObj['input-date-clear'])) ? ($this->controlObj['input-date-clear'] == 'true' ? true : false) : false;

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

        $inputControl = "<input type=\"{$tInputType}\" class=\"form-control {$tClass} {$tControlStyle}\" id=\"{$tId}\" name=\"{$tName}\" {$tPlaceHolder} {$tReadonly} {$tStyle}>";
        if ($tInputDateClear && $tInputType == 'date') {
            $inputControl = " 
                <div class=\"input-group mb-3\">
                    <div class=\"input-group-prepend\">
                        <span class=\"input-group-text\" style=\"cursor: pointer\" id=\"{$tId}_group\" onclick=\"$('#{$tId}').attr('type', 'text'); $('#{$tId}').val(''); $('#{$tId}').attr('type', 'date');\"><i class=\"far fa-calendar-times\"> </i></span>
                    </div>
                    <input type=\"{$tInputType}\" class=\"form-control {$tClass} {$tControlStyle}\" id=\"{$tId}\" name=\"{$tName}\" aria-describedby=\"{$tId}_group\" {$tPlaceHolder} {$tReadonly} {$tStyle}>
                </div>        
            ";
        }



        return <<<EOL
            <div class="form-group" {$tGroup} {$tContainerStyle}>
                {$tCaption}
                <div {$tContainerObjClass} {$tContainerObjStyle}>
                {$strHTMLOutLeft}
                {$inputControl}
                {$strHTMLOutRight}
                </div>
            </div>
            <script>
                $('#{$tId}').val(base64_decode('{$tValue}'));
                $('#{$tId}').css('background-color', base64_decode('{$tValue}'));
                $(document).ready(
                    function () {
                        $('#{$tId}').colorpicker();
                        $('#{$tId}').on('colorpickerChange', function(event) {
                            $('#{$tId}').css('background-color', event.color.toString());
                        });
                    }
                )
            </script>
EOL;

    }
}