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

class cms_file
{
    private $controlObj = NULL;
    private $data = NULL;

    public $id = NULL;
    public $repeaterId = "";
    public $repeaterRowIndex = 0;
    public $isRepeaterControl = false;
    public $group_name = "";

    #public $vendor_js_path = array('jquery.cropper/cropper.js', 'jquery.cropper/jquery-cropper.js');
    #public $vendor_css_path = 'cropperjs/cropper.css';

    public $vendor_js_path = array('cropperjs/1.0.0/cropper.min.js');
    public $vendor_css_path = 'cropperjs/1.0.0/cropper.min.css';

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
        $tValue = ($this->data !== NULL) ? $this->data : '';
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

        $file_type = strval($this->controlObj['file_type']);

        $crop = strval($this->controlObj['crop']); #auto | manual
        $cropSize = strval($this->controlObj['crop_size']); #e.g: 200x200

        $image_max_width = strval($this->controlObj['image_max_width']);
        $image_max_height = strval($this->controlObj['image_max_height']);

        $dir = (isset($this->controlObj['dir'])) ? strval($this->controlObj['dir']) : $this->selectedClass.'/'.$this->selectedUrlMethod;

        $controlSettings = array(
            'form_control_type'=>'file',
            'id'=>$tName,
            'file_type' => $file_type,
            'crop' => $crop,
            'crop_size' => $cropSize,
            'image_max_width' => $image_max_width,
            'image_max_height' => $image_max_height,
            'dir' => $dir,
            'repeaterId' => $this->repeaterId
        );

        $strControlSettings = base64_encode(json_encode($controlSettings));

        $arrAcceptFile = array();
        $arrFileType = explode('|', $file_type);
        foreach($arrFileType as $File) {
            $arrAcceptFile[] = ".{$File}";
        }
        $strAcceptFile = implode(', ', $arrAcceptFile);

        return <<<HTML
            <div class="form-group" {$tGroup} {$tContainerStyle}>
                {$tCaption}
                <div {$tContainerObjClass} {$tContainerObjStyle}>
                <div class="input-group">
                  <span class="input-group-addon" id="basic-addon1"><i class="fa fa-file-image-o" aria-hidden="true"></i></span>
                  <input type="file" class="form-control cms-upload-file" id="{$tId}_file" cms_target_id="{$tId}" style="opacity: 0; position: absolute; left: 0px; top: 0px; z-index: 10" onchange="cmsFnFileUpload(this, '{$tId}')" accept="{$strAcceptFile}">
                  <input type="text" class="form-control cms-upload" id="{$tId}_display" aria-describedby="basic-addon1" readonly="readonly" value="Upload File">
                </div>
                <input type="hidden" id="{$tId}" name="{$tName}" class="{$tClass}" cms-control-settings="{$strControlSettings}">
                </div>
            </div>
            <script>
                $('#{$tId}').val('{$tValue}');

                var jsonControlSettings = json_decode(base64_decode($('#{$tId}').attr('cms-control-settings')));
                jsonControlSettings['repeaterRowIndex'] = {$this->repeaterRowIndex};
                $('#{$tId}').attr('cms-control-settings', base64_encode(json_encode(jsonControlSettings)));
                console.log(jsonControlSettings);

                if ($('#{$tId}').val()!='') {
                    var jsonFileDetails = json_decode($('#{$tId}').val());

                    console.log(jsonFileDetails);

                    $('#{$tId}_file').hide();
                    $('#{$tId}_display').val(jsonFileDetails['name']);
                    $('#{$tId}_display').after('<span class="input-group-addon cms-file-remove"><a href="javascript:void(0)" onclick="cmsFnFileUploadDelete(this, \'{$tId}\')"><i class="fa fa-times" aria-hidden="true"></i></a></span>');

                    $('#{$tId}_display').on('click',
                        function () {
                            var arrData = JSON.parse($('#{$tId}').val());
                            console.log(arrData);



                            var uploadedFile = cmsInfo['config']['website']['path']+'uploads/'+arrData['path']+'/'+arrData['name'];

                            if (arrData['upload_temp']) {
                                uploadedFile = '/'+arrData['path']+'/'+arrData['name']+'?rand='+Math.random(10);
                            }
                            
                            if (arrData['is_url']) {
                                uploadedFile = arrData['o_url'];
                            }

                            var arrControlSettings = json_decode(base64_decode($('#{$tId}').attr('cms-control-settings')));

                            var arrValidImage = ['jpg', 'jpeg', 'png', 'gif'];
                            for(var i=0; i<arrValidImage.length; i++) {
                                if (arrData['file_type'].indexOf(arrValidImage[i]) >= 0) {
                                    BootstrapDialog.show({
                                                title: '',
                                                message: '<img id="image" src="'+uploadedFile+'" class="img-responsive">'
                                    });
                                    break;
                                }
                            }
                            /*var arrOtherFile = ['doc', 'docx', 'pdf', 'xls', 'xlsx', 'msword'];
                            for(var i=0; i<arrOtherFile.length; i++) {
                                if (arrData['file_type'].indexOf(arrOtherFile[i]) >= 0) {
                                    window.open(uploadedFile);
                                    break;
                                }
                            }*/
                            
                            window.open(uploadedFile);


                            /*BootstrapDialog.show({
                                                title: 'Crop Image',
                                                message: '\
                                                <div class="image-container" cms-control-id="{$tId}" style="width: 100%; height: '+($(window).height()-300)+'px"><img id="image" src="'+uploadedFile+'" class="img-responsive">\
                                                    <input type="hidden" class="cropper-data" name="cropper_data">\
                                                    \
                                                </div>\
                                                ',
                                                buttons: [{
                                                    label: 'Crop',
                                                    action: function(dialog) {

                                                    }
                                                }, {
                                                    label: 'Cancel',
                                                    action: function(dialog) {
                                                        dialog.close();
                                                    }
                                                }],
                                                closable: false,
                                                onshown: function () {
                                                }
                            });*/
                        }
                    );
                }
            </script>
HTML;

    }

    static function upload($table_name, $postControlSettings) {
        global $CONFIG;

        $arrUploadReturn = array();
        $arrUploadReturn['control-settings'] = $postControlSettings; #json_decode(base64_decode(strval($_POST['control-settings'])), true);
        $arrUploadReturn['cms-token'] = json_decode(base64_decode(strval($_POST['cms-file-upload'])), true);
        if ( 0 < $_FILES['file']['error'] ) {
            $arrUploadReturn['error'] = $_FILES['file']['error'];
        }
        else {

            $db = new cmsDatabaseClass();
            $crypt = new cmsCryptonite();

            $postRepeaterId = (isset($arrUploadReturn['control-settings']['repeaterId'])) ? $arrUploadReturn['control-settings']['repeaterId'] : '';
            $postRepeaterRowIndex = (isset($arrUploadReturn['control-settings']['repeaterId'])) ? $arrUploadReturn['control-settings']['repeaterRowIndex'] : '';

            $arrData = $db->select(
                sprintf(
                    "
                                SELECT *
                                FROM information_schema.columns
                                WHERE
                                TABLE_SCHEMA = '%s' AND
                                TABLE_NAME = '%s' AND
                                COLUMN_NAME = '%s'
                              ",
                    $db->mysqli->real_escape_string($db->database),
                    $db->mysqli->real_escape_string($table_name),
                    $db->mysqli->real_escape_string($arrUploadReturn['control-settings']['id'])
                )
            );

            if (count($arrData) > 0) {
                $uploadType = (isset($arrUploadReturn['control-settings']['file']['upload_type'])) ? intval($arrUploadReturn['control-settings']['file']['upload_type']) : 0;

                $uploadPath = $CONFIG['cms']['directory_upload_name'] . '/temp/' . $crypt->decrypt($arrUploadReturn['cms-token']['upload_temp']).(($postRepeaterId!='') ? "/".cmsTools::makeSlug($postRepeaterId).'/'.$postRepeaterRowIndex : '');
                $uploadedDir = $uploadPath . '/' . cmsTools::makeSlug($arrUploadReturn['control-settings']['id']);
                $tempDir = WWWPATH . '/' . $uploadedDir;

                if ($uploadType == 0) {
                    if (!is_dir($tempDir)) {
                        mkdir($tempDir, 0777, true);
                    }
                    if (isset($arrUploadReturn['control-settings']['file']['name'])) {
                        @unlink($tempDir . '/' . $arrUploadReturn['control-settings']['file']['name']);
                    }
                }

                $tPathInfo = pathinfo($_FILES['file']['name']);
                $tFile = cmsTools::makeSlug($tPathInfo['filename']).".".$tPathInfo['extension'];

                if ($uploadType == 1) {
                    $uploadPath = $CONFIG['cms']['directory_upload_name'] . '/temp';
                    $uploadedDir = $uploadPath;
                    $tempDir = WWWPATH . '/' . $uploadedDir;

                    $tFile = strtotime(date("Y-m-d H:i:s")).".".$tPathInfo['extension'];
                }

                $arrUploadReturn['control-settings']['file']['name'] = $tFile;
                $arrUploadReturn['control-settings']['file']['path'] = $uploadedDir;
                $arrUploadReturn['control-settings']['file']['base_path'] = '';
                $arrUploadReturn['control-settings']['file']['upload_temp'] = $arrUploadReturn['cms-token']['upload_temp'];
                $arrUploadReturn['control-settings']['file']['file_type'] = $arrUploadReturn['control-settings']['file_type'];

                move_uploaded_file($_FILES['file']['tmp_name'], $tempDir . '/' . $tFile);

                if ($arrUploadReturn['control-settings']['image_max_width'] != '' && is_numeric($arrUploadReturn['control-settings']['image_max_width'])):
                    require_once(VENDORSPATH . 'php-image-resize/ImageResize.php');
                    require_once(VENDORSPATH . 'php-image-resize/ImageResizeException.php');
                    $image = new \Gumlet\ImageResize($tempDir . '/' . $tFile);
                    $image->resizeToWidth(intval($arrUploadReturn['control-settings']['image_max_width']));
                    #$image->output();
                    $image->save($tempDir . '/' . $tFile);
                    $arrUploadReturn['control-settings']['test'] = 'Resized Width: '.intval($arrUploadReturn['control-settings']['image_max_width']);
                endif;
                if ($arrUploadReturn['control-settings']['image_max_height'] != '' && is_numeric($arrUploadReturn['control-settings']['image_max_height'])):
                    require_once(VENDORSPATH . 'php-image-resize/ImageResize.php');
                    require_once(VENDORSPATH . 'php-image-resize/ImageResizeException.php');
                    $image = new \Gumlet\ImageResize($tempDir . '/' . $tFile);
                    $image->resizeToHeight(intval($arrUploadReturn['control-settings']['image_max_height']));
                    #$image->output();
                    $image->save($tempDir . '/' . $tFile);
                    $arrUploadReturn['control-settings']['test'] = 'Resized Height: '.intval($arrUploadReturn['control-settings']['image_max_height']);
                endif;

                $tImage = getimagesize($tempDir . '/' . $tFile);
                $arrUploadReturn['control-settings']['file']['image_size'][0] = $tImage[0];
                $arrUploadReturn['control-settings']['file']['image_size'][1] = $tImage[1];

            } else {
                $arrUploadReturn['error'] = "Column Name {$arrUploadReturn['control-settings']['id']} not found in {$db->database}.{$table_name}";
            }

            #$arrUploadReturn['control-settings']['test'] = $arrUploadReturn['control-settings']['image_max_width'];
        }

        print json_encode($arrUploadReturn);
        exit;

    }
}