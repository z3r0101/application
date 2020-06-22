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

class cms_repeater
{
    private $controlObj = NULL;
    public $postRepeaterFields = NULL;

    function __construct($controlObj)
    {
        #parent::__construct();

        $this->controlObj = $controlObj;
    }

    private function simpleXMLElementObjXPath($pArray = array(), $pXPath) {
        $doc = new DOMDocument();
        $doc->formatOutput = TRUE;
        $doc->loadXML($pArray->asXML());
        $xml = $doc->saveXML();

        $sXml = new SimpleXMLElement($xml);
        $result = $sXml->xpath($pXPath);

        return $result;
    }

    function render() {
        $tId = strval($this->controlObj['id']);
        $table_name = strval($this->controlObj['table_name']);
        $table_link_field = strval($this->controlObj['table_link_field']);
        $table_order_field = strval($this->controlObj['table_order_field']);
        $table_order_by = strval($this->controlObj['table_order_by']);
        $tCaption = (isset($this->controlObj['caption'])) ? ((strval($this->controlObj['caption'])!='') ? '<label for="'.$tId.'" style="margin-bottom: -@0px; paddinbg-bottom: -20px">'.$this->controlObj['caption'].'</label>' : '') : ''; #$tCaption = (isset($this->controlObj['caption'])) ? '<label for="'.$tId.'">'.strval($this->controlObj['caption']).'</label>' : '';
        $tRowCaption = (isset($this->controlObj['row_caption'])) ? strval($this->controlObj['row_caption']) : '';
        $tRowCaptionFormat = (isset($this->controlObj['row_caption_format'])) ? strval($this->controlObj['row_caption_format']) : '[COUNTER]. [CAPTION]';

        $tAddButtonCaption = (isset($this->controlObj['add_button_caption'])) ? strval($this->controlObj['add_button_caption']) : '';

        $repeater_style = (isset($this->controlObj['repeater_style'])) ? strval($this->controlObj['repeater_style']) : 'list';

        $tPrimaryKey = '';
        $error = array();

        if ($table_name=="") {
            $error[] = "Repeater property name \"table_name\" is required.";
        }

        if ($table_link_field == "") {
            $error[] = "Repeater property name \"table_link_field\" is required.";
        }

        #GET PRIMARY KEY
        if (count($error) == 0) {
            $db = new cmsDatabaseClass();
            $tArrData = $db->select("SHOW KEYS FROM {$table_name} WHERE Key_name = 'PRIMARY'");

            if (isset($tArrData['error'])) {
                $error[] = $tArrData['error'];
            } else {
                $tPrimaryKey = $tArrData[0]['Column_name'];
            }
        }

        $arrButton = null;

        if (count($error) == 0) {
            $arrHTMLOut = array();
            foreach($this->controlObj as $controlKey => $controlItem) {

                if ($controlKey == 'control') {
                    $formControlsPath = APPPATH.'views/cms/form.controls/'.$controlItem['type'].'.php';
                    if (file_exists($formControlsPath)) {
                        include_once($formControlsPath);
                        $classControlName = "cms_{$controlItem['type']}";
                        if (class_exists($classControlName)) {
                            $controlObj = new $classControlName($controlItem);
                            $controlObj->isRepeaterControl = true;
                            $controlObj->repeaterId = $tId;
                            $controlObj->repeaterRowIndex = "[REPEATER_ROW_INDEX]";
                            $controlObj->repeater_style = $repeater_style;
                            $controlObj->id = "[REPEATER_CONTROL_ID]".$controlItem['id']."";
                            $arrHTMLOut[] = $controlObj->render();
                        }
                    }
                } else if ($controlKey == 'button') {
                    $arrButton = $controlItem;
                }

            }

            if (isset($arrButton['id'])) {
                $formControlsPath = APPPATH.'views/cms/form.controls/hidden.php';
                if (file_exists($formControlsPath)) {
                    include_once($formControlsPath);
                    $classControlName = "cms_hidden";
                    if (class_exists($classControlName)) {
                        $controlObj = new $classControlName($arrButton);
                        $controlObj->isRepeaterControl = true;
                        $controlObj->id = "[REPEATER_CONTROL_ID]".$arrButton['id']."";
                        $arrHTMLOut[] = $controlObj->render();
                    }
                }
            }

            $strHTMLOut = base64_encode(implode('', $arrHTMLOut));

            $repeaterProperties = array(
                'id'=>$tId,
                'table_name'=>$table_name,
                'table_link_field'=>$table_link_field,
                'table_order_field'=>$table_order_field,
                'table_order_by'=>$table_order_by,
                'table_primary_field'=>$tPrimaryKey,
                'repeater_style'=>$repeater_style
            );

            $strRepeaterDetails = base64_encode(json_encode($repeaterProperties));

            $arrRepeaterHTML = array();
            $counter = 0;

            if (isset($this->postRepeaterFields[$tId])) {
                foreach($this->postRepeaterFields[$tId] as $repeaterRow => $repeaterData) {
                    $counter = $repeaterRow+1;
                    $collapse = ($repeaterRow == 0) ? ' in' : '';

                    $arrHTMLOut = array();
                    foreach($this->controlObj as $controlKey => $controlItem) {
                        if ($controlKey == 'control') {
                            $formControlsPath = APPPATH.'views/cms/form.controls/'.$controlItem['type'].'.php';
                            if (file_exists($formControlsPath)) {
                                include_once($formControlsPath);
                                $classControlName = "cms_{$controlItem['type']}";
                                if (class_exists($classControlName)) {
                                    $controlObj = new $classControlName($controlItem);
                                    $controlObj->isRepeaterControl = true;
                                    $controlObj->repeaterId = $tId;
                                    $controlObj->repeaterRowIndex = $repeaterRow;
                                    $controlObj->repeater_style = $repeater_style;
                                    $controlObj->id = "{$tId}_{$repeaterRow}_{$controlItem['id']}";
                                    $controlObj->value($repeaterData[strval($controlItem['id'])]);

                                    if (isset($arrButton['id'])) {
                                        $controlObj->group_name = $repeaterData[strval($arrButton['id'])];
                                    }

                                    $arrHTMLOut[] = $controlObj->render();
                                }
                            }
                        }
                    }
                    $strRepeaterHTMLControls = implode('', $arrHTMLOut);

                    $tPrimaryKeyVal = $repeaterData[$tPrimaryKey];

                    $tRowCaptionDisplay = $tRowCaptionFormat;

                    if (isset($arrButton['id'])) {
                        if (!isset($this->controlObj['row_caption_format'])) {
                            $tRowCaptionDisplay = '[COUNTER]. [GROUP]';
                        }
                        foreach($arrButton as $repeaterGroupName) {
                            if (strval($repeaterGroupName['value']) == strval($repeaterData[strval($arrButton['id'])])) {
                                $tRowCaptionDisplay = str_replace('[GROUP]', strval($repeaterGroupName), $tRowCaptionDisplay);
                                break;
                            }
                        }

                        if (strpos($tRowCaptionDisplay, '[GROUP]')!==false) {
                            $tRowCaptionDisplay = str_replace('[GROUP]', '', $tRowCaptionDisplay);
                        }
                    }

                    $tRowCaptionDisplay = str_replace('[COUNTER]', $counter, $tRowCaptionDisplay);
                    $tRowCaptionDisplay = str_replace('[CAPTION]', $tRowCaption, $tRowCaptionDisplay);


                    $tHeaderCaptionFiller = '';
                    if(preg_match_all('/{+(.*?)}/', $tRowCaptionDisplay, $matches)) {
                        foreach($matches[1] as $captionIndex => $captionText) {

                            $getRepeaterObj = $this->simpleXMLElementObjXPath($this->controlObj, 'control[@id="'.$captionText.'"]');

                            $tempCaption = $repeaterData[$captionText];
                            if (strval($getRepeaterObj[0]['type']) == 'file') {
                                $tArr = json_decode($repeaterData[$captionText], true);
                                $tempCaption = $tArr['name'];
                            }

                            $tRowCaptionDisplay = str_replace('{'.$captionText.'}', "<span>{$tempCaption}</span>", $tRowCaptionDisplay);
                            $tHeaderCaptionFiller .= '
                                $(\'#'.$tId.' .repeater-row:eq('.$repeaterRow.') input[name="'.$captionText.'"]\').on(\'keyup\',
                                    function () {
                                        cmsFnRepeaterTabCapFiller(this);
                                    }
                                );
                            ';
                        }
                    }

                    if ($repeater_style=='group' || $repeater_style=='list') {
                        $arrRepeaterHTML[] = <<<HTML
                            <div class="panel panel-default repeater-row data-{$repeaterRow}" data-row="{$repeaterRow}">
                                <div class="panel-heading" role="tab" id="{$tId}_heading_{$repeaterRow}">
                                    <h4 class="panel-title">
                                        <a role="button" data-toggle="collapse" data-parent="#{$tId}" href="#{$tId}_collapse_{$repeaterRow}" aria-expanded="true" aria-controls="{$tId}_collapse_{$repeaterRow}">
                                            {$tRowCaptionDisplay}
                                        </a>
                                        <a href="javascript:void(0)" class="pull-right repeater-delete" cms-repeater-id="{$tId}" onclick="cmsFnRepeater{$tId}(this)"><i class="fa fa-times" aria-hidden="true"></i></a>
                                    </h4>
                                </div>
                                <div id="{$tId}_collapse_{$repeaterRow}" class="panel-collapse collapse{$collapse}" role="tabpanel" aria-labelledby="{$tId}_heading_{$repeaterRow}">
                                    <div class="panel-body">
                                        <input type="hidden" class="cms-form-control-repeater cms-form-primary-id" id="{$tId}_{$repeaterRow}_{$tPrimaryKey}" name="{$tPrimaryKey}" value="{$tPrimaryKeyVal}">
                                        {$strRepeaterHTMLControls}
                                    </div>
                                </div><script>{$tHeaderCaptionFiller}</script>
                            </div>
HTML;
                    } else if ($repeater_style == 'table') {
                        $arrRepeaterHTML[] = <<<HTML
                            <div class="repeater-row data-{$repeaterRow} repeater-row-style-table" data-row="{$repeaterRow}">
                                <input type="hidden" class="cms-form-control-repeater cms-form-primary-id" id="{$tId}_{$repeaterRow}_{$tPrimaryKey}" name="{$tPrimaryKey}" value="{$tPrimaryKeyVal}">
                                {$strRepeaterHTMLControls}
                                <div class="form-group" style="display: block; float: left; width: 1%" container-obj-style="padding-right: 0px">
                                    <a href="javascript:void(0)" class="pull-right repeater-delete" cms-repeater-id="{$tId}" onclick="cmsFnRepeater{$tId}(this)"><i class="fa fa-times" aria-hidden="true"></i></a>
                                </div>
                            </div>
HTML;
                    }
                }
            }

            $strRepeaterHTML = implode('', $arrRepeaterHTML);

            $repeaterOrderJS = '';
            if ($table_order_field!='') {
                $repeaterOrderJS = <<<HTML
                    var fnRepeaterHeadingMouseDown = function () {
                        $( "#{$tId}" ).find('.panel-heading').on('mousedown',
                            function (pEvent) {

                                if ($(pEvent.target).attr('role')) {
                                    if ($(pEvent.target).attr('role')=='button') {

                                        return false;
                                    }
                                }

                                var collapseId = $(this).attr('id').replace('{$tId}_heading_', '{$tId}_collapse_');
                                //$( "#{$tId}" ).find('.panel-collapse.in').collapse('hide');
                                if ($('#'+collapseId).hasClass('in')) {
                                    //$( "#{$tId}" ).collapse().sortable( "option", "delay", 1000 );
                                }

                            }
                        );
                    }

                    $(function(){
                        fnRepeaterHeadingMouseDown();
                        setTimeout(
                            function () {
                                $( "#{$tId}" ).collapse().sortable(
                                    {
                                        axis: 'y',
                                        /*delay: 400,*/
                                        handle: '.panel-heading',
                                        /*placeholder: 'ui-state-highlight',*/
                                        stop: function( event, ui ) {

                                            $(ui.item).find('.cms-form-control-repeater.html').each(
                                                function (pIndex, pObj) {
                                                    //console.log($(pObj).attr('id'));

                                                    tinymce.EditorManager.execCommand('mceRemoveEditor', false, $(pObj).attr('id'));

                                                    var tinyMCESettings = JSON.parse(base64_decode($(pObj).attr('cms-tinymce-settings')));

                                                    new tinymce.Editor($(pObj).attr('id'), tinyMCESettings, tinymce.EditorManager).render();
                                                }
                                            );
                                            $( "#{$tId}" ).collapse().sortable( "option", "delay", 0 );

                                        }
                                    }
                                );
                            }, 0
                        );
                    });
HTML;
            }

            if ($arrButton === null) {
                $strButton = <<<HTML
                    <a class="btn btn-default cms-button repeater-add" href="javascript:void(0)" role="button" cms-repeater-id="{$tId}">{$tAddButtonCaption}</a>
HTML;
            } else {
                $buttonCaption = (isset($arrButton['caption'])) ? strval($arrButton['caption']) : 'Add';

                $arrButtonGroup = array();
                foreach($arrButton as $groupItem) {
                    $arrButtonGroup[] = '<li><a href="javascript:void(0)" class="repeater-add" cms-repeater-group-val="'.$groupItem['value'].'" cms-repeater-id="'.$tId.'" cms-repeater-group-control="'.$arrButton['id'].'">'.$groupItem.'</a></li>';
                }

                $strButtonGroup = implode('', $arrButtonGroup);

                if (count($strButtonGroup) > 0) {
                    $strButton = <<<HTML
                        <div class="btn-group">
                          <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" cms-repeater-id="{$tId}" cms-repeater-group-control="{$arrButton['id']}">
                            {$buttonCaption} <span class="caret"></span>
                          </button>
                          <ul class="dropdown-menu">
                            {$strButtonGroup}
                          </ul>
                        </div>
HTML;
                } else {
                    $strButton = <<<HTML
                        <a class="btn btn-default cms-button repeater-add" href="javascript:void(0)" role="button" cms-repeater-id="{$tId}">{$buttonCaption}</a>
HTML;
                }

            }

            if (isset($arrButton['id'])) {
                if (!isset($this->controlObj['row_caption_format'])) {
                    $tRowCaptionFormat = '[COUNTER]. [GROUP]';
                }
            }

            $fnRepeaterHeadingMouseDown = "";
            if ($table_order_field!='') {
                $fnRepeaterHeadingMouseDown = "fnRepeaterHeadingMouseDown();";
            }



            return <<<HTML
            <div class="form-group">
                {$tCaption}
                <div class="repeater-container" cms-repeater-id="{$tId}">
                    <div class="panel-group" id="{$tId}" role="tablist" aria-multiselectable="true"
                        cms-repeater-properties="{$strRepeaterDetails}"
                    >{$strRepeaterHTML}</div>
                </div>
                <div class="repeater-controls" style="display: none" cms-repeater-id="{$tId}">{$strHTMLOut}</div>
                {$strButton}
            </div>
            <script>
                $('.repeater-add[cms-repeater-id="{$tId}"]').on('click',
                    function () {
                        var repeaterLength = $('#{$tId} .repeater-row').length;

                        var repeaterControls = base64_decode($('.repeater-controls[cms-repeater-id="{$tId}"]').html());
                        repeaterControls = repeaterControls.replace(/\[REPEATER_CONTROL_ID\]/g, '{$tId}_'+repeaterLength+'_');
                        repeaterControls = repeaterControls.replace(/\[REPEATER_ROW_INDEX\]/g, repeaterLength);
                        //console.log(repeaterControls);

                        var cmsRepeaterProperties = JSON.parse(base64_decode($('#{$tId}').attr('cms-repeater-properties')));

                        var repeater_style = (cmsRepeaterProperties['repeater_style']) ? cmsRepeaterProperties['repeater_style'] : 'list';

                        $('#{$tId} .panel-collapse.collapse').removeClass('in');

                        var tRowCaptionFormat = "{$tRowCaptionFormat}";

                        if ($(this).attr('cms-repeater-group-control')) {

                        }

                        var tRowCaptionDisplay = tRowCaptionFormat.replace(/\[COUNTER\]/g, repeaterLength+1);
                        tRowCaptionDisplay = tRowCaptionDisplay.replace(/\[CAPTION\]/g, '{$tRowCaption}');
                        tRowCaptionDisplay = tRowCaptionDisplay.replace(/\[GROUP\]/g, $(this).html());
                        tRowCaptionDisplayTemp = tRowCaptionDisplay;
                        $.each(tRowCaptionDisplay.match(/{([^}]*)}/g),
                            function (pIndex, pValue) {

                            }
                        );

                        tRowCaptionDisplay = tRowCaptionDisplay.replace(/{([^}]*)}/g, '');


                        if (repeater_style == 'list') {
                            $('#{$tId}').append('\
                                <div class="panel panel-default repeater-row data-'+repeaterLength+'" data-row="'+repeaterLength+'">\
                                    <div class="panel-heading" role="tab" id="{$tId}_heading_'+repeaterLength+'">\
                                        <h4 class="panel-title">\
                                            <a role="button" data-toggle="collapse" data-parent="#{$tId}" href="#{$tId}_collapse_'+repeaterLength+'" aria-expanded="true" aria-controls="{$tId}_collapse_'+repeaterLength+'">\
                                                '+(tRowCaptionDisplay)+' <span></span>\
                                            </a>\
                                            <a href="javascript:void(0)" class="pull-right repeater-delete" cms-repeater-id="{$tId}" onclick="cmsFnRepeater{$tId}(this)"><i class="fa fa-times" aria-hidden="true"></i></a>\
                                        </h4>\
                                    </div>\
                                    <div id="{$tId}_collapse_'+repeaterLength+'" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="{$tId}_heading_'+repeaterLength+'">\
                                        <div class="panel-body">\
                                            <input type="hidden" class="cms-form-control-repeater cms-form-primary-id" id="{$tId}_'+repeaterLength+'_{$tPrimaryKey}" name="{$tPrimaryKey}" value="0">\
                                            '+repeaterControls+'\
                                        </div>\
                                    </div>\
                                </div>\
                            ');
                        } else {
                            $('#{$tId}').append('\
                                <div class="repeater-row data-'+repeaterLength+' repeater-row-style-table" data-row="'+repeaterLength+'">\
                                    <input type="hidden" class="cms-form-control-repeater cms-form-primary-id" id="{$tId}_'+repeaterLength+'_{$tPrimaryKey}" name="{$tPrimaryKey}" value="0">\
                                    '+repeaterControls+'\
                                    <div class="form-group" style="display: block; float: left; width: 1%" container-obj-style="padding-right: 0px">\
                                        <a href="javascript:void(0)" class="pull-right repeater-delete" cms-repeater-id="{$tId}" onclick="cmsFnRepeater{$tId}(this)"><i class="fa fa-times" aria-hidden="true"></i></a>\
                                    </div>\
                                </div>\
                            ');
                        }


                        if ($(this).attr('cms-repeater-group-control')) {
                            $('div.repeater-row.data-'+repeaterLength+' input[name="'+$(this).attr('cms-repeater-group-control')+'"]').val($(this).attr('cms-repeater-group-val'));
                            $('div.repeater-row.data-'+repeaterLength+' div[cms-group]').hide();
                            $('div.repeater-row.data-'+repeaterLength+' div[cms-group="'+$(this).attr('cms-repeater-group-val')+'"]').show();
                        }

                        $.each(tRowCaptionDisplayTemp.match(/{([^}]*)}/g),
                            function (pIndex, pValue) {
                                pValue = pValue.replace('{', '').replace('}', '');
                                $('#{$tId} .repeater-row:eq('+repeaterLength+') input[name="'+pValue+'"]').unbind();
                                $('#{$tId} .repeater-row:eq('+repeaterLength+') input[name="'+pValue+'"]').on('keyup',
                                    function () {
                                        cmsFnRepeaterTabCapFiller(this);
                                        //$(this).parents('.repeater-row').find('.panel-title a:eq(0) span').html($(this).val());
                                    }
                                );

                            }
                        );

                        $.event.trigger({
                            type: "CMS_REPEATER_ADDED",
                            data: null,
                            repeater_index_added: repeaterLength
                        });

                        {$fnRepeaterHeadingMouseDown}
                    }
                );

                function cmsFnRepeaterTabCapFiller(pObj) {
                    $(pObj).parents('.repeater-row').find('.panel-title a:eq(0) span').html($(pObj).val());
                }

                function cmsFnRepeater{$tId}(pObj) {
                    $(pObj).parents('.repeater-row').find('textarea.html').each(
                        function (pIndex, pTinyMceObj) {
                            tinymce.remove("#"+$(pTinyMceObj).attr('id'));
                        }
                    );

                    if (!CMS_REPEATER_DELETE['{$tId}']) {
                        CMS_REPEATER_DELETE['{$tId}'] = [];
                    }

                    CMS_REPEATER_DELETE['{$tId}'][CMS_REPEATER_DELETE['{$tId}'].length] = $(pObj).parents('.repeater-row').find('.cms-form-primary-id').val();

                    //Delete temp uploaded file
                    $(pObj).parents('.repeater-row').find('input:file').each(
                        function (pIndex, pObj) {
                            var cmsControlSettings = JSON.parse(base64_decode($('#'+$(pObj).attr('cms_target_id')).attr('cms-control-settings')));
                            console.log(cmsControlSettings);
                            $.ajax(
                                {
                                    cache: false,
                                    type: 'POST',
                                    data: 'control-settings='+$('#'+$(pObj).attr('cms_target_id')).attr('cms-control-settings')+'&cms-file-upload='+$('#cmsFormToken').val()+'&cms-file-upload-opt=2'
                                }
                            ).done(
                                function (data) {

                                }
                            );

                        }
                    );

                    $(pObj).parents('.repeater-row').remove();
                }

                {$repeaterOrderJS}
            </script>
HTML;
        } else {
            $strError = implode("\n", $error);
            return <<<HTML
<pre>
Repeater Control Error:
{$strError}</pre>
HTML;

        }

    }
}