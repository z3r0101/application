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

class cms_tag
{
    private $controlObj = NULL;
    private $data = NULL;

    public $id = NULL;
    public $isRepeaterControl = false;
    public $repeater_style = "";
    public $group_name = "";

    public $control_style = <<<CSS
        .cms-control-group {
            position: relative;
            height: auto;
        }

        .cms-control-group-sel {
            display: block;
            float: left;
            width: auto;
            padding: 0 0.5% 0% 0.5%;
            background-color: #c0c0c0;
            margin-left: 2px;
            margin-bottom: 2px;
            -webkit-border-radius: 2px 2px 2px 2px;
            border-radius: 2px 2px 2px 2px;
        }

        .cms-control-group-rem {
            display: inline-block;
            border-left: 1px solid #cdcdcd;
            padding-left: 3px;
        }

        .cms-control-group-sel a {
            font-size: 11px;
        }

        .cms-control-group-list {
            position: absolute;
            z-index: 1;
        }
        
        .cms-control-group-list:focus, .cms-control-group-list:focus{
            outline: none;
        }

        .cms-control-group-list-container {
            display: inline-block;
            width: 100%;
            height: auto;
            background-color: white;
            margin-top: 1%;
            border: 1px solid #cdcdcd;
        }
        
        .cms-control-group-list-container input {
            
        }
        
        .cms-control-group-list-sel {
            display: inline-block;
            width: auto;
            padding: 0 0.5% 0% 0.5%;
            background-color: #c0c0c0;
            margin-left: 2px;
            margin-bottom: 2px;
            -webkit-border-radius: 2px 2px 2px 2px;
            border-radius: 2px 2px 2px 2px;
            cursor: pointer;
        }

        .cms-control-pad {
            padding: 0.5%
        }
CSS;

    public $control_js = <<<JS
        function cmsControlTag(pId, pOption) {
            var tTop = $('#'+pId+'_control .cms-control-group.form-control').position().top;
            var tHeight = $('#'+pId+'_control .cms-control-group.form-control').height();
            var tWidth = $('#'+pId+'_control .cms-control-group.form-control').width();
            
            if ($('#'+pId+'_control .cms-control-group.form-control .cms-control-group-list')[0]) {
                $('#'+pId+'_control .cms-control-group.form-control .cms-control-group-list').remove();
            }
            
            
            var tArr = [];
            if (pOption == 0) {
                $.each(CMS_CONTROLS_OBJ[pId]['list'],
                    function (pIndex, pData) {
                        var tSubArr = CMS_CONTROLS_OBJ[pId]['selected'].filter(function (item) {return item[0] == pData[0]}); 
                        if (tSubArr.length == 0)
                            tArr[tArr.length] = '<div class="cms-control-group-list-sel" onclick="cmsControlTagSel(\''+pId+'\', this)" data-value="'+pData[0]+'">'+pData[1]+'</div>';
                    }    
                );
            } else if (pOption == 1) {
                tArr[tArr.length] = '<input type="text" class="form-control" onkeyup="cmsControlTagText(\''+pId+'\', this)">';
            }
            
            if (tArr.length > 0) {
                $('#'+pId+'_control .cms-control-group.form-control').append('\
                    <div class="cms-control-group-list" style="top: '+tHeight+'px; width: '+tWidth+'px" tabindex="0" '+((pOption==0) ? 'onblur="$(this).hide()" onkeydown="cmsControlTagSelKeyDown(\''+pId+'\', this)"' : '')+'>\
                        <div class="cms-control-group-list-container">\
                            <div class="cms-control-pad">\
                                '+tArr.join('')+'\
                            </div>\
                        </div>\
                    </div>\
                ');
                
                if (pOption == 0) {
                    $('#'+pId+'_control .cms-control-group.form-control .cms-control-group-list').focus();
                    
                } else if (pOption == 1) {
                    $('#'+pId+'_control .cms-control-group.form-control .cms-control-group-list input').focus();
                    $('#'+pId+'_control .cms-control-group.form-control .cms-control-group-list input').on('blur',
                        function () {
                            $('#'+pId+'_control .cms-control-group.form-control .cms-control-group-list').remove();
                        }
                    );
                }
            }
        }
        
        function cmsControlTagText(pId, pObj) {
            if (event.keyCode === 13) {
                event.preventDefault();
                var tIndex = CMS_CONTROLS_OBJ[pId]['selected'].length;
                CMS_CONTROLS_OBJ[pId]['selected'][tIndex] = [tIndex, $(pObj).val()];        
                $('#'+pId+'_control .cms-control-group.form-control').append('<div class="cms-control-group-sel" data-value="'+tIndex+'">'+$(pObj).val()+' <div class="cms-control-group-rem"><a href="javascript:void(0)" data-value="'+tIndex+'" onclick="cmsControlTagRemove(\''+pId+'\', this)"><i class="fas fa-times"></i></a></div></div>');
                $('#'+pId+'_control .cms-control-group.form-control .cms-control-group-list').remove();
                $('#'+pId).val(json_encode(CMS_CONTROLS_OBJ[pId]['selected']));
            } else if (event.keyCode === 27) {
                $('#'+pId+'_control .cms-control-group.form-control .cms-control-group-list').remove();
            }
        }
        
        function cmsControlTagSelKeyDown(pId, pObj) {
            if (event.keyCode === 27) {
                $('#'+pId+'_control .cms-control-group.form-control .cms-control-group-list').remove();
            }
        }
        
        function cmsControlTagSel(pId, pObj) {
            CMS_CONTROLS_OBJ[pId]['selected'][CMS_CONTROLS_OBJ[pId]['selected'].length] = [parseInt($(pObj).attr('data-value'), 10), $(pObj).html()];
            $('#'+pId+'_control .cms-control-group.form-control').append('<div class="cms-control-group-sel" data-value="'+$(pObj).attr('data-value')+'">'+$(pObj).html()+' <div class="cms-control-group-rem"><a href="javascript:void(0)" data-value="'+$(pObj).attr('data-value')+'" onclick="cmsControlTagRemove(\''+pId+'\', this)"><i class="fas fa-times"></i></a></div></div>');
            $('#'+pId+'_control .cms-control-group.form-control .cms-control-group-list').remove();
            $('#'+pId).val(json_encode(CMS_CONTROLS_OBJ[pId]['selected']));
        }
        
        function cmsControlTagRemove(pId, pObj) {
            CMS_CONTROLS_OBJ[pId]['selected'] = CMS_CONTROLS_OBJ[pId]['selected'].filter(function(item){ return item[0] != parseInt($(pObj).attr('data-value'),10) })      
            $(pObj).parents('.cms-control-group-sel').remove();
            $('#'+pId).val(json_encode(CMS_CONTROLS_OBJ[pId]['selected']));
        }
JS;



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

        $tData_text = (isset($this->controlObj['data_text'])) ? strval($this->controlObj['data_text']) : '';
        $TextOnly = $tData_text === 'true'? true: false;

        $tData_query = (isset($this->controlObj['data_query'])) ? strval($this->controlObj['data_query']) : '';
        $tData_value = (isset($this->controlObj['data_value'])) ? strval($this->controlObj['data_value']) : '';
        $tData_caption = (isset($this->controlObj['data_caption'])) ? strval($this->controlObj['data_caption']) : '';


        $tValue = base64_encode($tValue);

        if ($this->isRepeaterControl) {
            if (isset($this->repeater_style)) {
                if ($this->repeater_style=='table') $tCaption = '';
            }
        }

        $tArrList = array();
        if ($tData_query!='' && $tData_value!='' && $tData_caption!='') {
            $dbClass = new cmsDatabaseClass();
            $arrData = $dbClass->select($tData_query);
            if (count($arrData) > 0) {
                foreach($arrData as $Index => $Data) {
                    $tArrList[] = array($Data[$tData_value], $Data[$tData_caption]);
                }
            }
        }
        $tBaseEncodeList = base64_encode(json_encode($tArrList));

        $tDataOption = 0;
        if ($TextOnly) {
            $tDataOption = 1;
        }

        return <<<EOL
            <div class="form-group" {$tGroup} {$tContainerStyle}>
                {$tCaption}
                <div {$tContainerObjClass} {$tContainerObjStyle}>
                    <div id="{$tId}_control" class="input-group mb-3">
                      <div class="input-group-prepend">
                        <span class="input-group-text" id="basic-addon1"><a href="javascript:void(0)" onclick="cmsControlTag('{$tId}', {$tDataOption})"><i class="fas fa-plus"></i></a></span>
                      </div>
                      <div type="text" class="cms-control-group form-control"></div>
                    </div>                
                </div>
                <input type="hidden" class="form-control {$tClass}" id="{$tId}" name="{$tName}">
            </div>
            <script>
                CMS_CONTROLS_OBJ["{$tId}"] = {list: [], selected: []};
                CMS_CONTROLS_OBJ["{$tId}"]["list"] = json_decode(base64_decode('{$tBaseEncodeList}'));
                
                var arrData{$tId} = (base64_decode('{$tValue}')!='') ? JSON.parse(base64_decode('{$tValue}')) : '';
                
                if (arrData{$tId}.length > 0) {
                    CMS_CONTROLS_OBJ["{$tId}"]['selected'] = arrData{$tId};
                 
                    $.each(CMS_CONTROLS_OBJ["{$tId}"]['selected'],
                        function (pIndex, pData) {
                            $('#{$tId}_control .cms-control-group.form-control').append('<div class="cms-control-group-sel" data-value="'+pData[0]+'">'+pData[1]+' <div class="cms-control-group-rem"><a href="javascript:void(0)" data-value="'+pData[0]+'" onclick="cmsControlTagRemove(\'{$tId}\', this)"><i class="fas fa-times"></i></a></div></div>');    
                        }
                    );
                    
                }
                
                $('#{$tId}').val(base64_decode('{$tValue}'));
            </script>
EOL;

    }
}