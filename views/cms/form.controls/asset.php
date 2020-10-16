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

class cms_asset
{
    private $controlObj = NULL;
    private $data = NULL;

    public $id = NULL;
    public $repeaterId = "";
    public $isRepeaterControl = false;
    public $group_name = "";

    public $vendor_js_path = array('jquery.cropper/cropper.js', 'jquery.cropper/jquery-cropper.js', '[RES_CMS_URL]js/cms.ctrl.asset.js');
    public $vendor_css_path = array('jquery.cropper/cropper.min.css', '[RES_CMS_URL]css/cms.ctrl.asset.css');

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

        $tAccept = (isset($this->controlObj['accept'])) ? strval($this->controlObj['accept']) : '';

        $tAssetDefaultDir = (isset($this->controlObj['asset_default_dir'])) ? strval($this->controlObj['asset_default_dir']) : '';
        $tAspectRatio = (isset($this->controlObj['img_aspect_ratio'])) ? strval($this->controlObj['img_aspect_ratio']) : '';

        $controlSettings = array(
            'form_control_type'=>'asset',
            'id'=>$tName,
            'repeaterId' => $this->repeaterId,
            'asset_default_dir' => $tAssetDefaultDir,
            'accept' => $tAccept,
            'img_aspect_ratio' => $tAspectRatio,
            'asset_url' => ASSETS_URL
        );
        $strControlSettings = base64_encode(json_encode($controlSettings));

        $tDisplay = ($tValue!='') ? ASSETS_URL.$tValue : 'Upload File';

        return <<<EOL
            <div class="form-group" {$tGroup} {$tContainerStyle}>
                {$tCaption}
                <div class="input-group mb-3" style="cursor: pointer">
                  <div class="input-group-prepend">
                    <span class="input-group-text" id="basic-addon1" onclick="cmsAssetUpload(this, '{$tId}', 0);"><i class="fa fa-upload" aria-hidden="true"></i></span>
                  </div>
                  <input type="text" class="form-control cms-upload" id="{$tId}_display" aria-describedby="basic-addon1" readonly="readonly" value="{$tDisplay}" style="cursor: pointer" onclick="cmsAssetUpload(this, '{$tId}', 0);">
                </div>
                <input type="hidden" id="{$tId}" name="{$tName}" class="{$tClass}" cms-control-settings="{$strControlSettings}">
            </div>

            <script>
                $('#{$tId}').val('{$tValue}');

                if ($('#{$tId}').val()!='') {
                    {$tId}_add_file();
                }

                function {$tId}_add_file() {
                    if ($('#{$tId}_remove_file')[0]) $('#{$tId}_remove_file').remove();
                    $('#{$tId}_display').after('<div class="input-group-prepend"><span id="{$tId}_remove_file" class="input-group-text cms-file-remove" onclick="{$tId}_remove_file(this)"><i class="fa fa-times" aria-hidden="true"></i></span></div>');
                }

                function {$tId}_remove_file(pObj) {
                    $('#{$tId}').val('');
                    $('#{$tId}_display').val('Upload File');
                    $(pObj).remove();
                }
            </script>
EOL;
    }
}