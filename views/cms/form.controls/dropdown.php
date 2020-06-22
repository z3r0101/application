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

class cms_dropdown
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
        $errorMsg = "";

        $tId = ($this->id === NULL) ? $this->controlObj['id'] : $this->id;
        $tName = strval($this->controlObj['id']);
        $tCaption = (isset($this->controlObj['caption'])) ? '<label for="'.$tId.'">'.$this->controlObj['caption'].'</label>' : '';
        $tPlaceHolder = (isset($this->controlObj['placeholder'])) ? $this->controlObj['placeholder'] : 'Select...';
        $tValue = ($this->data !== NULL) ? $this->data : '';
        $tGroup = (isset($this->controlObj['group'])) ? 'cms-group="'.strval($this->controlObj['group']).'"' : "";
        if ($tGroup!='') {
            if ($this->group_name!=strval($this->controlObj['group'])) {
                $tGroup .= ' style="display: none"';
            }
        }

        $listData = "";
        $controlSettings = array();
        $PK = "";
        if (isset($this->controlObj['table_name'])) {
            $table_name = strval($this->controlObj['table_name']);
            $item_value = strval($this->controlObj['item_value']);
            $item_caption = strval($this->controlObj['item_caption']);
            $data_default = strval($this->controlObj['data_default']);

            $db = new cmsDatabaseClass();

            $strWhere = "";
            if ($data_default!='') {
                $arrDataDef = json_decode($data_default, true);

                $arrWhere = array();
                foreach($arrDataDef as $Field => $Value) {
                    $arrWhere[] = "{$Field} = {$Value}";
                }

                $strWhere = " WHERE ".implode(" AND ", $arrWhere);
            }

            $arrData = $db->select("SELECT * FROM {$table_name} {$strWhere}");

            $arrDataPK = $db->select("SHOW KEYS FROM {$table_name} WHERE Key_name = 'PRIMARY'");
            if (count($arrDataPK)==0) {
                $errorMsg = "table name \"{$table_name}\" has no primary key";
            } else {
                $PK = $arrDataPK[0]['Column_name'];
            }

            for($i=0; $i<count($arrData); $i++) {
                $listData .= '
                                <li><a href="javascript:void(0)">
                                    <div style="display: table; width: 100%">
                                        <div class="dropdown-select-col-a" onclick="fn'.$tId.'_Select(this)">'.$arrData[$i][$item_caption].'</div><div class="dropdown-select-col-b"><span class="glyphicon glyphicon-edit" onclick="" style="display: none"> </span><span class="glyphicon glyphicon-remove-circle" onclick="fn'.$tId.'_Delete(this, \''.$arrData[$i][$PK].'\')"> </span></div>
                                    </div>
                                </a></li>
                                ';
            }

            $controlSettings = array(
                'id'=>$tName,
                'form_control_type' => 'dropdown',
                'table_name' => $table_name,
                'item_caption' => $item_caption,
                'item_value' => $item_value,
                'data_default' => $data_default
            );
        }

        $tClass = (!$this->isRepeaterControl) ? 'cms-form-control' : 'cms-form-control-repeater';

        $tValue = base64_encode($tValue);


        $strControlSettings = base64_encode(json_encode($controlSettings));

        if ($tValue!='') {
            $tPlaceHolder = base64_decode($tValue);
        }

        $tContainerStyle = (isset($this->controlObj['container_style'])) ? 'style="'.$this->controlObj['container_style'].'""' : '';
        if ($tGroup!='') {
            $tContainerStyle = '';
            $tGroup = str_replace('style="display: none"', 'style="display: none; '.$this->controlObj['container_style'].'"', $tGroup);
        }

        $tDisabled = (isset($this->controlObj['disabled'])) ? 'disabled="'.$this->controlObj['disabled'].'""' : '';

        return <<<HTML
            <div class="form-group" {$tGroup} {$tContainerStyle} >
                {$tCaption}
                <input type="hidden" class="form-control {$tClass}" id="{$tId}" name="{$tName}" value="{$tValue}">

                <div id="{$tId}_Dropdown" class="dropdown">
                    <button style="width: 100%; text-align: left" class="btn dropdown-toggle" type="button" data-toggle="dropdown" {$tDisabled}><div class="dropdown-selected" id="{$tId}_Val">{$tPlaceHolder}</div>
                        <span class="caret pull-right"></span>
                    </button>
                    <ul class="dropdown-menu" style="width: 100%" id="{$tId}_Sel">
                        {$listData}
                        <li class="dropdown-create-new"><a href="javascript:void(0)" jt-value="Create New" class="jt-shipping" onclick="fn{$tId}_CreateNew(this)">Create New</a></li>
                    </ul>
                </div>

            </div>
            <script>
                function fn{$tId}_CreateNew(pObj) {
                    BootstrapDialog.show(
                        {
                            title: $(document)[0].title,
                            message: '<textarea id="{$tId}_Input" class="form-control" style="width: 100%; height: 80px; margin-bottom: 10px" placeholder=""></textarea>',
                            buttons: [{
                                label: 'Apply',
                                action: function(dialog) {
                                    if ($('#{$tId}_Input').val()!='') {

                                        $(pObj).parents('.dropdown').find('.dropdown-selected').html($('#{$tId}_Input').val());
                                        $({$tId}).val($('#{$tId}_Input').val());

                                        var postData = {
                                            type: 0,
                                            data: {
                                                {$controlSettings["item_value"]}: $('#{$tId}_Input').val(),
                                            }
                                        };

                                        $.ajax(
                                            {
                                                type: 'POST',
                                                url: '',
                                                data: 'CMS_POST_REQ='+encodeURIComponent('{$strControlSettings}')+'&CMS_POST_REQ_DATA='+encodeURIComponent(base64_encode(json_encode(postData)))
                                            }
                                        ).done(
                                            function (data) {
                                                var retData = JSON.parse(data);

                                                var tData = '\
                                                            <li><a href="javascript:void(0)">\
                                                                <div style="display: table; width: 100%">\
                                                                    <div class="dropdown-select-col-a" onclick="fn{$tId}_Select(this)">'+$('#{$tId}_Input').val()+'</div><div class="dropdown-select-col-b"><span class="glyphicon glyphicon-edit" onclick="jtFnCustomerAddressEdit(this)" style="display: none"> </span><span class="glyphicon glyphicon-remove-circle" onclick="fn{$tId}_Delete(this, '+retData['id']+')"> </span></div>\
                                                                </div>\
                                                            </a></li>\
                                                ';
                                                $(pObj).parents('.dropdown').find('li.dropdown-create-new').before(tData);
                                            }
                                        );

                                        dialog.close();
                                    } else {
                                        $('#{$tId}_Input').focus();
                                    }
                                }
                            }, {
                                label: 'Cancel',
                                action: function(dialog) {
                                    dialog.close();
                                }
                            }]
                        }
                    );
                }

                function fn{$tId}_Select(pObj) {
                    $(pObj).parents('.dropdown').removeClass('open');
                    $(pObj).parents('.dropdown').find('button').attr('aria-expanded', 'false');
                    $(pObj).parents('.dropdown').find('.dropdown-selected').html($(pObj).html());
                    $({$tId}).val($(pObj).html());

                    $.event.trigger({
                        type: "CMS_EVENT",
                        'form_control_type': 'dropdown',
                        'form_control_id': '{$tId}',
                        'event': 'select'
                    });
                }

                function fn{$tId}_Delete(pObj, pId) {
                    BootstrapDialog.confirm(
                        {
                            type: BootstrapDialog.TYPE_WARNING,
                            title: $(document)[0].title,
                            message: 'Are you sure you want to delete this item below?<br /><br />'+$(pObj).parents('li').find('.dropdown-select-col-a').html(),
                            callback: function (result) {
                                if (result) {
                                    var postData = {
                                        type: 1,
                                        data: {
                                            {$PK}: pId,
                                        }
                                    }

                                    $.event.trigger({
                                        type: "CMS_EVENT",
                                        'form_control_type': 'dropdown',
                                        'form_control_id': '{$tId}',
                                        'event': 'delete'
                                    });

                                    $.ajax(
                                        {
                                            type: 'POST',
                                            url: '',
                                            data: 'CMS_POST_REQ='+encodeURIComponent('{$strControlSettings}')+'&CMS_POST_REQ_DATA='+encodeURIComponent(base64_encode(json_encode(postData)))
                                        }
                                    ).done(
                                        function (data) {
                                        }
                                    );

                                    $(pObj).parents('li').remove();
                                }
                            }
                        }
                    );
                }

                $('#{$tId}').val(base64_decode('{$tValue}'));
            </script>
HTML;

    }
}