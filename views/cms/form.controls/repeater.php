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
 *
 * PROPERTIES
 * control tag:
 *  id                  Control Id (string)
 *  caption             Control Caption Display
 *  button-add-hide     Bool - true | false - Def: false
 *  button-sort-hide    Bool - true | false - Def: false
 *  button-remove-hide  Bool - true | false - Def: false
 *  data-base64         Save data as base64 if true. Bool - true | false - Def: true
 *  data-empty-insert   Insert data if empty - Bool - true | false - Def: false
 *
 * table tag:
 * data-insert-max      Maximum rows inserted (integer)
 * table structure in json format
 *  id                  Column Id (string)
 *  type                Control Type: text, textarea, select, html, asset, table
 *  caption             Control Caption Display
 *  default             Show value in sort caption display
 *  placeholder         Input placeholder. Applicable only in text, textarea control
 *  options             Select control type option value. In array format []
 *  onchange            Onchange event of select control
 *                      onchange: function (pCtrlId, pObj) {
 *                              //pCtrlId   - return control name
 *                              //pObj      - return select obj
 *                      }
 *  value               set value
 *
 *  FOR CUSTOM CONTROL
 *  control_block       HTML content
 *  control_initialize  Initialize custom control - function (pCtrlId, pData, pIndex)
 *  control_sort_render Sort caption display
 *  control_sort_update Trigger this event when sort order - function (pObj, pIndex) {
 *
 * SAMPLE CONTROL:
 *
               <control type="repeater" id="carousel_data" caption="Carousel Images">
                <table data-insert-max="10">
                    {
                        id: 'image',
                        type: 'asset',
                        caption: 'Image',
                        accept: '.jpg,.jpeg,.png,.gif',
                        img_aspect_ratio: '',
                        asset_default_dir: 'home'
                    },
                    {
                        id: 'title',
                        type: 'text',
                        caption: 'Title',
                        default: true
                    },
                    {
                        id: 'description',
                        type: 'textarea',
                        caption: 'Description'
                    },
                    {
                        id: 'short_profile',
                        type: 'text',
                        caption: 'Short Profile'
                    }
                </table>
                <buttons>
                    <button type="add">Add Image</button>
                    <button type="sort">Sort Images</button>
                </buttons>
            </control>
 *
 *
 *
 */

class cms_repeater
{
    private $controlObj = NULL;
    private $data = NULL;

    public $id = NULL;
    public $isRepeaterControl = false;
    public $repeater_style = "";
    public $group_name = "";

    public $vendor_js_path = array("tinymce/4.4.3/js/tinymce/tinymce.min.js", "tinymce/4.4.3/js/tinymce/jquery.tinymce.min.js", 'jquery.cropper/cropper.js', 'jquery.cropper/jquery-cropper.js', '[RES_CMS_URL]js/cms.ctrl.repeater.js', '[RES_CMS_URL]js/cms.ctrl.asset.js');
    public $vendor_css_path = array('jquery.cropper/cropper.min.css', '[RES_CMS_URL]css/cms.ctrl.repeater.css', '[RES_CMS_URL]css/cms.ctrl.asset.css');

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
        $tContainerStyle = (isset($this->controlObj['container-style'])) ? 'style="'.$this->controlObj['container-style'].'"' : '';
        $tValue = base64_encode($tValue);

        $strTableData = '';
        $dataCtrlRowMax = 0;

        if (is_object($this->controlObj->table)) {
            $strTableData = base64_encode(strval($this->controlObj->table));
            $dataCtrlRowMax = intval($this->controlObj->table['data-insert-max']);
        }


        $arrButtons = array(
            'add' => array(
                'caption' => 'Add Item'
            ),
            'sort' => array(
                'caption' => 'Sort Item'
            )
        );
        if (is_object($this->controlObj->buttons->button)) {
            foreach ($this->controlObj->buttons->button as $Index => $Obj) {
                if (isset($arrButtons[strval($Obj['type'])])) {
                    $arrButtons[strval($Obj['type'])]['caption'] = strval($Obj);
                }
            }
        }

        $ctrParamDataBase64 = (isset($this->controlObj['data-base64'])) ? ((strval($this->controlObj['data-base64'])!='true') ? 'false' : strval($this->controlObj['data-base64'])) : 'true';
        $ctrParamDataEmptyInsert = (isset($this->controlObj['data-empty-insert'])) ? ((strval($this->controlObj['data-empty-insert'])!='true') ? 'false' : strval($this->controlObj['data-empty-insert'])) : 'false';
        $ctrlParamButtonAddHide = (isset($this->controlObj['button-add-hide'])) ? ((strval($this->controlObj['button-add-hide'])!='true') ? 'false' : strval($this->controlObj['button-add-hide'])) : 'false';
        $ctrlParamButtonSortHide = (isset($this->controlObj['button-sort-hide'])) ? ((strval($this->controlObj['button-sort-hide'])!='true') ? 'false' : strval($this->controlObj['button-sort-hide'])) : 'false';
        $ctrlParamButtonRemoveHide = (isset($this->controlObj['button-remove-hide'])) ? ((strval($this->controlObj['button-remove-hide'])!='true') ? 'false' : strval($this->controlObj['button-remove-hide'])) : 'false';

        $strAssetsURL = ASSETS_URL;

        $iif = function ($condition, $true, $false) { return $condition ? $true : $false; };

        return <<<EOL
   
                <div id="{$tId}_Ctrl" class="mb-3" {$tContainerStyle} data-base64="{$ctrParamDataBase64}" data-empty-insert="{$ctrParamDataEmptyInsert}" data-asset-url="{$strAssetsURL}" data-ctrl-target="{$tId}" data-ctrl-row-max="{$dataCtrlRowMax}">
                    <label><strong>{$tCaption}</strong></label>
                    <div class="repeater-container position-relative w-100" data-button-remove-hide="{$ctrlParamButtonRemoveHide}">
                    </div>
                    <input type="hidden" class="repeater-table" value="{$strTableData}">
                    <input type="hidden" class="form-control cms-form-control" id="{$tId}" name="{$tName}">
                    {$iif(($ctrlParamButtonAddHide == 'true'), "", "<button class=\"btn btn-secondary repeater-add-item d-none mr-2\" onclick=\"cmsFnCtrlRepeater_Add($(this).parents('div[data-ctrl-target]').attr('data-ctrl-target'))\"><i class=\"fas fa-plus\"> </i> {$arrButtons['add']['caption']}</button>")}
                    {$iif(($ctrlParamButtonSortHide == 'true'), "", "<button class=\"btn btn-secondary repeater-sort-items\" onclick=\"cmsFnCtrlRepeater_Sort($(this).parents('div[data-ctrl-target]').attr('data-ctrl-target'))\"><i class=\"fas fa-sort\"> </i> {$arrButtons['sort']['caption']}</button>")}
                </div>
                <script>
                    $('#{$tId}').val(base64_decode('{$tValue}'));
                    cmsFnCtrlRepeater_Initialize('{$tId}');
                </script>
EOL;

    }
}