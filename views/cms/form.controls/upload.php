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
 * Sample Tag
 *
 * Images
 *
 *
 * PDF
 *
 */

class cms_upload
{
    private $controlObj = NULL;
    private $data = NULL;

    public $id = NULL;
    public $repeaterId = "";
    public $isRepeaterControl = false;
    public $group_name = "";

    public $vendor_js_path = array('jquery.cropper/cropper.js', 'jquery.cropper/jquery-cropper.js', '[RES_CMS_URL]js/cms.ctrl.upload.js');
    public $vendor_css_path = array('jquery.cropper/cropper.min.css', '[RES_CMS_URL]css/cms.ctrl.asset.css');

    function __construct($controlObj)
    {
        #parent::__construct();

        $this->controlObj = $controlObj;

        if (isset($this->controlObj['value'])) {
            $this->data = strval($this->controlObj['value']);
        }
    }

    function value($data)
    {
        $this->data = $data;
    }


    function css()
    {
        return <<<CSS
                .cmsUploadTable {
                  border-collapse: collapse;
                  width: 100%;
                  margin-bottom: 0px;
                }

                .cmsUploadTable td, .cmsUploadTable th {
                  border: 1px solid #dddddd;
                  text-align: left;
                  padding: 8px;
                }

                .cmsUploadTable thead tr {
                  background-color: #f0f0f0;
                }

                .cmsUploadTable tr:nth-child(even) {
                  background-color: #f1f1f1;
                }

                #cmsUploadBrowseFolders {
                    display: inline-block;
                    width: 100%;
                    height: 300px;
                    min-height: 300px;
                    border: 1px solid #999;
                    overflow-x: auto;
                }

                #cmsUploadBrowseFolders .dvFoldersLoading {
                    position: absolute;
                    width: 140px;
                    height: 25px;
                    text-align: center;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    margin: auto;
                    display: none;
                }

                #cmsUploadBrowseFolders .dvRowFolder {
                    display: inline-block;
                    width: 100%;
                    padding: 5px;
                    border-bottom: 1px #c0c0c0 solid;
                    cursor: pointer;
                }

                #cmsUploadBrowseFolders .dvRowFolder:nth-child(n+3):last-child {
                    border-bottom: none;
                }

                #cmsUploadUploadBody .cmsUploadUploadToolbar .btn:focus {
                  outline: none;
                }
CSS;
    }

    function render() {
        global $CONFIG;

        $UPLOADS_URL = UPLOADS_URL;

        $tId = ($this->id === NULL) ? $this->controlObj['id'] : $this->id;
        $tName = strval($this->controlObj['id']);
        $tCaption = (isset($this->controlObj['caption'])) ? (($this->controlObj['caption']!='') ? '<label for="'.$tId.'">'.$this->controlObj['caption'].'</label>' : '') : '';
        $tPlaceHolder = (isset($this->controlObj['placeholder'])) ? $this->controlObj['placeholder'] : '';
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

        $tAssetDefaultDir = (isset($this->controlObj['upload_parent_dir'])) ? strval($this->controlObj['upload_parent_dir']) : '';
        $tAssetContainerDir = (isset($this->controlObj['upload_container_dir'])) ? strval($this->controlObj['upload_container_dir']) : '';
        $tAspectRatio = (isset($this->controlObj['img_aspect_ratio'])) ? strval($this->controlObj['img_aspect_ratio']) : '';

        $controlSettings = array(
            'form_control_type'=>'upload',
            'id'=>$tName,
            'repeaterId' => $this->repeaterId,
            'upload_parent_dir' => $tAssetDefaultDir,
            'upload_container_dir' => $tAssetContainerDir,
            'upload_root_dir' => $CONFIG['cms']['directory_upload_name'],
            'accept' => $tAccept,
            'img_aspect_ratio' => $tAspectRatio
        );
        $strControlSettings = base64_encode(json_encode($controlSettings));

        $tDisplay = ($tValue!='') ? $UPLOADS_URL.$tValue : (($tPlaceHolder!='') ? $tPlaceHolder : 'Upload File');



        return <<<EOL
            <div class="form-group" {$tGroup} {$tContainerStyle}>
                {$tCaption}
                <div class="input-group mb-3" style="cursor: pointer">
                  <div class="input-group-prepend">
                    <span class="input-group-text" id="basic-addon1" onclick="cmsUploadUpload(this, '{$tId}', 0);"><i class="fa fa-upload" aria-hidden="true"></i></span>
                  </div>
                  <input type="text" class="form-control cms-upload" id="{$tId}_display" aria-describedby="basic-addon1" readonly="readonly" value="{$tDisplay}" style="cursor: pointer" onclick="cmsUploadUpload(this, '{$tId}', 0);">
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