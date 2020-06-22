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

class cms_date
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
        $tValue = ($this->data !== NULL) ? $this->data : '';
        $tGroup = (isset($this->controlObj['group'])) ? 'cms-group="'.strval($this->controlObj['group']).'"' : "";
        if ($tGroup!='') {
            if ($this->group_name!=strval($this->controlObj['group'])) {
                $tGroup .= ' style="display: none"';
            }
        }

        $tClass = (!$this->isRepeaterControl) ? 'cms-form-control' : 'cms-form-control-repeater';

        $tContainerStyle = (isset($this->controlObj['container-style'])) ? 'style="'.$this->controlObj['container-style'].'"' : '';
        $tContainerObjStyle = (isset($this->controlObj['container-obj-style'])) ? 'style="'.$this->controlObj['container-obj-style'].'"' : '';

        return <<<EOL
            <div class="form-group" {$tGroup} {$tContainerStyle}>
                <div {$tContainerObjStyle}>
                    {$tCaption}
                    <div class="input-group date" id="{$tId}_DatePicker">
                        <span class="input-group-addon">
                            <span class="glyphicon glyphicon-calendar"></span>
                        </span>
                        <input type="text" class="form-control {$tClass}" id="{$tId}" name="{$tName}" placeholder="">
                    </div>
                </div>
            </div>
            <script>
                $('#{$tId}').val('{$tValue}');

                $('#{$tId}_DatePicker').datetimepicker(
                    {
                        format: 'YYYY-MM-DD'
                    }
                );

                $('#{$tId}').on('click',
                    function () {

                        $('#{$tId}_DatePicker').data("DateTimePicker").show();
                    }
                );
            </script>
EOL;
    }
}