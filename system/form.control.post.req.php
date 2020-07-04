<?php

if (isset($_GET['CMS_REQ'])) {
    $_REQUEST['CMS_POST_REQ'] = $_GET['CMS_REQ'];
}

if (isset($_REQUEST['CMS_POST_REQ'])) {

    $postControlSettings = json_decode(base64_decode(strval($_REQUEST['CMS_POST_REQ'])), true);

    if (isset($postControlSettings['form_control_type'])) {
        switch($postControlSettings['form_control_type']) {
            case 'dropdown':
                $table_name = (isset($postControlSettings['table_name'])) ? strval($postControlSettings['table_name']) : '';
                $data_default = (isset($postControlSettings['table_name'])) ? strval($postControlSettings['data_default']) : '';

                if (isset($_POST["CMS_POST_REQ_DATA"])) {
                    $postControlData = json_decode(base64_decode(strval($_REQUEST['CMS_POST_REQ_DATA'])), true);

                    if ($postControlData['type'] == 0) {
                        #INSERT
                        if ($data_default!='') {
                            $arrDataDef = json_decode($data_default, true);
                            $postControlData['data'] = array_merge($postControlData['data'], $arrDataDef);
                        }
                        $arrRet = $this->dbClass->insert($table_name,
                            $postControlData['data']
                        );

                        print json_encode(array('id'=>$arrRet['value']));
                    } else if ($postControlData['type'] == 1) {

                        $this->dbClass->delete($table_name, $postControlData['data']);

                    }
                }
                break;

            case 'select2':
                $table_name = (isset($postControlSettings['table_name'])) ? strval($postControlSettings['table_name']) : '';
                $table_select = (isset($postControlSettings['table_select'])) ? strval($postControlSettings['table_select']) : '';
                $table_field_caption = (isset($postControlSettings['table_field_caption'])) ? strval($postControlSettings['table_field_caption']) : '';
                $table_field_value = (isset($postControlSettings['table_field_value'])) ? strval($postControlSettings['table_field_value']) : '';
                $table_order_by = (isset($postControlSettings['table_order_by'])) ? strval($postControlSettings['table_order_by']) : '';
                $search_field = (isset($postControlSettings['search_field'])) ? strval($postControlSettings['search_field']) : '';
                $table_db_index = (isset($postControlSettings['table_db_index'])) ? intval($postControlSettings['table_db_index']) : 0;

                if ($table_name != '') {
                    $arrSQLWhere = array();

                    $tArrOptions = array();
                    $db = new cmsDatabaseClass($table_db_index);

                    $tSqlWhere = "";
                    if (isset($_REQUEST['q'])) {
                        $tKeyword = strval($_REQUEST['q']);

                        $arrSQLWhere[] = sprintf("{$table_field_caption} LIKE '%s'", "%".$db->mysqli->real_escape_string($tKeyword)."%");

                        if ($search_field!='') {
                            $tArr = explode(",", $search_field);
                            foreach($tArr as $Index => $lFieldName) {
                                $arrSQLWhere[] = sprintf("{$lFieldName} LIKE '%s'", "%".$db->mysqli->real_escape_string($tKeyword)."%");
                            }
                        }

                        if (count($arrSQLWhere)>0) {
                            $tSqlWhere = " WHERE ".implode(" OR ", $arrSQLWhere);
                        }
                    }

                    if ($table_select!='') {
                        $table_name = "
                                        (
                                          $table_select
                                        ) AS $table_name
                                        ";
                    }

                    $arrData = $db->select("SELECT * FROM {$table_name} {$tSqlWhere} ".(($table_order_by!="") ? " ORDER BY {$table_order_by}" : ""));

                    #print "SELECT * FROM {$table_name} {$tSqlWhere} ".(($table_order_by!="") ? " ORDER BY {$table_order_by}" : "");
                    #exit;

                    for($i=0; $i<count($arrData); $i++) {
                        /*$tArrOptions[] = array(
                            'items'=>array('id'=>intval($arrData[$i][$table_field_value]), 'text'=>$arrData[$i][$table_field_caption])
                        );*/

                        $tArrFields = array();
                        $tArrFields['id'] = intval($arrData[$i][$table_field_value]);
                        $tArrFields['text'] = utf8_encode($arrData[$i][$table_field_caption]);


                        $tGetFields = array_keys($arrData[$i]);
                        foreach($tGetFields as $Index => $Field) {
                            $tArrFields[$Field] = utf8_encode($arrData[$i][$Field]);
                        }

                        $tArrOptions[] = $tArrFields;

                        #$tArrOptions[] = array('id'=>intval($arrData[$i][$table_field_value]), 'text'=>$arrData[$i][$table_field_caption], 'stock'=>'777');
                    }

                    #header('Content-type:application/json;charset=utf-8');
                    #print_r($tArrOptions);
                    print json_encode($tArrOptions);
                }
                break;
            case 'upload':
                $uploadDir = UPLOADSPATH;

                $defaultPath = ($postControlSettings['upload_parent_dir']!='') ? $CONFIG['website']['path'].$postControlSettings['upload_parent_dir'] : '';
                #$defaultPath = '/content'

                $defaultPath = (isset($_POST['cmsUploadSavePath'])) ? (($_POST['cmsUploadSavePath'] != '') ? ltrim($_POST['cmsUploadSavePath'], $CONFIG['website']['path']) : $defaultPath) : $defaultPath;
                if (substr($defaultPath, 0, strlen($CONFIG['cms']['directory_upload_name'])) == $CONFIG['cms']['directory_upload_name']) $defaultPath = substr($defaultPath, strlen($CONFIG['cms']['directory_upload_name']));

                if (isset($_POST["cmsUploadWebURL"])) {
                    $upload_file = basename($_POST["cmsUploadWebURL"]);
                    $image = file_get_contents($_POST["cmsUploadWebURL"]);

                    $arrPathDestination = array();
                    if (strval($postControlSettings['upload_parent_dir'])!='') $arrPathDestination[] = strval($postControlSettings['upload_parent_dir']);
                    if (strval($postControlSettings['upload_container_dir'])!='') $arrPathDestination[] = strval($postControlSettings['upload_container_dir']);
                    $uploadContainerDir = CMS_Users_Id.'_'.time().'/'.implode('/',$arrPathDestination);
                    mkdir(UPLOADSTEMPPATH.$uploadContainerDir, 0777, true);

                    file_put_contents(UPLOADSTEMPPATH.$uploadContainerDir.'/'.$upload_file, $image);

                    #rawurlencode
                    print 'temp/'.$uploadContainerDir.'/'.($upload_file);
                    #print $CONFIG['website']['path'].$CONFIG['cms']['directory_upload_name'].$uploadTempDir.'/'.rawurlencode($upload_file);
                    exit;
                }

                if (isset($_FILES['cmsUploadUploadFile'])) {
                    include_once(APPPATH.'controllers/cms/cms_assets.php');
                    $cmsUploadFileInfo = json_decode($_POST["cmsUploadFileInfo"], true);
                    $cmsUploadFileInfo['upload_file'] = cms_assets::file_format($cmsUploadFileInfo['upload_file']);

                    $arrPathDestination = array();
                    if (strval($postControlSettings['upload_parent_dir'])!='') $arrPathDestination[] = strval($postControlSettings['upload_parent_dir']);
                    if (strval($postControlSettings['upload_container_dir'])!='') $arrPathDestination[] = strval($postControlSettings['upload_container_dir']);
                    $uploadContainerDir = CMS_Users_Id.'_'.time().'/'.implode('/',$arrPathDestination);
                    mkdir(UPLOADSTEMPPATH.$uploadContainerDir, 0777, true);

                    move_uploaded_file($_FILES['cmsUploadUploadFile']['tmp_name'], UPLOADSTEMPPATH.$uploadContainerDir.'/'.$cmsUploadFileInfo['upload_file']);

                    print json_encode(
                        array(
                            'temp_short_url' =>'temp/'.$uploadContainerDir.'/'.$cmsUploadFileInfo['upload_file'],
                            'temp_full_url' => 'assets/uploads/temp/'.$uploadContainerDir.'/'.$cmsUploadFileInfo['upload_file']
                        )
                    );
                    exit;
                }
                break;
            case 'asset':
                $assetsDir = SITEROOTPATH.$CONFIG['cms']['directory_assets_name'];
                $uploadDir = SITEROOTPATH.$CONFIG['cms']['directory_upload_name'];

                $defaultPath = ($postControlSettings['asset_default_dir']!='') ? $CONFIG['website']['path'].$postControlSettings['asset_default_dir'] : '';

                $defaultPath = (isset($_POST['cmsAssetSavePath'])) ? (($_POST['cmsAssetSavePath'] != '') ? ltrim($_POST['cmsAssetSavePath'], $CONFIG['website']['path']) : $defaultPath) : $defaultPath;
                if (substr($defaultPath, 0, strlen($CONFIG['cms']['directory_assets_name'])) == $CONFIG['cms']['directory_assets_name']) $defaultPath = substr($defaultPath, strlen($CONFIG['cms']['directory_assets_name']));

                if (isset($_POST["cmsAssetFileCheck"])) {
                    $cmsAssetFileInfo = json_decode($_POST["cmsAssetFileCheck"], true);
                    if (is_file($assetsDir.$defaultPath.'/'.$cmsAssetFileInfo['upload_file'])) {
                        print 1;
                    } else {
                        print 0;
                    }
                    exit;
                }

                if (isset($_POST["cmsAssetWebURL"])) {
                    $image = file_get_contents($_POST["cmsAssetWebURL"]);
                    $tempFile = $uploadDir.'/temp/'.time();
                    file_put_contents($tempFile, $image);
                    $tempExtension = cmsTools::getFileMimeExtension($tempFile);
                    rename($tempFile, $tempFile.'.'.$tempExtension);
                    $tPathInfo = pathinfo($tempFile.'.'.$tempExtension);
                    print WEBSITE_URL.$CONFIG['cms']['directory_upload_name'].'/temp/'.$tPathInfo['basename'];
                    exit;
                }

                if (isset($_GET['asset-download'])) {
                    $tFile = $assetsDir.'/'.$_GET["asset-download"];

                    $quoted = sprintf('"%s"', addcslashes(basename($tFile), '"\\'));
                    $size   = filesize($tFile);

                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename=' . $quoted);
                    header('Content-Transfer-Encoding: binary');
                    header('Connection: Keep-Alive');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                    header('Pragma: public');
                    header('Content-Length: ' . $size);
                    ob_clean();
                    flush();
                    readfile($tFile); //Absolute URL
                    exit;
                }

                if (isset($_POST['cmsAssetRename'])) {
                    $tArr = json_decode(base64_decode($_POST['cmsAssetRename']), true);

                    $error = '';
                    if (is_file(((isset($_POST["cmsAssetListDirFiles"])) ? $assetsDir.'/'.$_POST["cmsAssetListDirFiles"].'/' : $assetsDir.'/').trim($tArr[1]))) {
                        $error = ''.$tArr[1].' already exist.';
                    } else {
                        if (is_dir(((isset($_POST["cmsAssetListDirFiles"])) ? $assetsDir.'/'.$_POST["cmsAssetListDirFiles"].'/' : $assetsDir.'/').trim($tArr[1]))) {
                            $error = ''.$tArr[1].' already exist.';
                        }
                    }

                    if ($error == '') {
                        rename(
                            ((isset($_POST["cmsAssetListDirFiles"])) ? $assetsDir.'/'.$_POST["cmsAssetListDirFiles"].'/' : $assetsDir.'/').trim($tArr[0]),
                            ((isset($_POST["cmsAssetListDirFiles"])) ? $assetsDir.'/'.$_POST["cmsAssetListDirFiles"].'/' : $assetsDir.'/').trim($tArr[1])
                        );

                        if (is_file(((isset($_POST["cmsAssetListDirFiles"])) ? $assetsDir.'/'.$_POST["cmsAssetListDirFiles"].'/' : $assetsDir.'/').'.cms.'.trim($tArr[0]))) {
                            rename(
                                ((isset($_POST["cmsAssetListDirFiles"])) ? $assetsDir.'/'.$_POST["cmsAssetListDirFiles"].'/' : $assetsDir.'/').'.cms.'.trim($tArr[0]),
                                ((isset($_POST["cmsAssetListDirFiles"])) ? $assetsDir.'/'.$_POST["cmsAssetListDirFiles"].'/' : $assetsDir.'/').'.cms.'.trim($tArr[1])
                            );
                        }

                    } else {
                        print '<textarea class="cmsAssetError">'.$error.'</textarea>';
                    }
                }

                if (isset($_POST['cmsAssetDelete'])) {
                    if (is_file($assetsDir.'/'.$_POST['cmsAssetDelete'])) {
                        unlink($assetsDir.'/'.$_POST['cmsAssetDelete']);
                        $tPathInfo = pathinfo($_POST['cmsAssetDelete']);
                        $path = ($_POST["cmsAssetListDirFiles"] != '') ? '/'.$_POST["cmsAssetListDirFiles"] : '';
                        if (is_file($assetsDir.$path.'/.cms.'.$tPathInfo["basename"])) {
                            unlink($assetsDir.$path.'/.cms.'.$tPathInfo["basename"]);
                        }
                    }

                    if (is_dir($assetsDir.'/'.$_POST['cmsAssetDelete'])) {
                        cmsTools::rmDir($assetsDir.'/'.$_POST['cmsAssetDelete']);
                    }
                }

                if (isset($_FILES['cmsAssetUploadFile'])) {
                    include_once(APPPATH.'controllers/cms/cms_assets.php');
                    $cmsAssetFileInfo = json_decode($_POST["cmsAssetFileInfo"], true);

                    $cmsAssetFileInfo['upload_file'] = cms_assets::file_format($cmsAssetFileInfo['upload_file']);

                    move_uploaded_file($_FILES['cmsAssetUploadFile']['tmp_name'], $assetsDir.$defaultPath.'/'.$cmsAssetFileInfo['upload_file']);
                    if ($cmsAssetFileInfo['image_info'][0] > 0) {
                        copy($assetsDir.$defaultPath.'/'.$cmsAssetFileInfo['upload_file'], $assetsDir.$defaultPath.'/.cms.'.$cmsAssetFileInfo['upload_file']);

                        require_once(VENDORSPATH . 'php-image-resize/ImageResize.php');
                        require_once(VENDORSPATH . 'php-image-resize/ImageResizeException.php');

                        $image = new \Gumlet\ImageResize($assetsDir.$defaultPath.'/.cms.'.$cmsAssetFileInfo['upload_file']);
                        $image->resizeToWidth(220);
                        $image->save($assetsDir.$defaultPath.'/.cms.'.$cmsAssetFileInfo['upload_file']);
                    }

                    print rawurlencode($cmsAssetFileInfo['upload_file']);
                    exit;
                }
                if (isset($_POST['cmsAssetSavePathIni'])) {
                    $defaultPathIni = (($_POST['cmsAssetSavePathIni'] != '') ? ltrim($_POST['cmsAssetSavePathIni'], '/') : $defaultPath);
                    if (substr($defaultPathIni, 0, strlen($CONFIG['cms']['directory_assets_name'])) == $CONFIG['cms']['directory_assets_name']) $defaultPathIni = substr($defaultPathIni, strlen($CONFIG['cms']['directory_assets_name']));

                    $cmsAssetFileInfo = json_decode($_POST["cmsAssetFileInfo"], true);
                    move_uploaded_file($assetsDir.$defaultPathIni.'/'.$cmsAssetFileInfo['upload_file'], $assetsDir.$defaultPath.'/'.$cmsAssetFileInfo['upload_file']);
                }

                if (isset($_POST["cmsAssetBrowseFolder"])) {
                    $arrFiles = [];

                    $path = ($_POST['cmsAssetBrowseFolder']!='') ? $_POST['cmsAssetBrowseFolder'].'/*' : '*';
                    $arrSITEROOTPATH = explode('/', $assetsDir);

                    if ($path != '*') {
                        $tArrDir = explode('/', $_POST['cmsAssetBrowseFolder']);
                        $tArrPath = array_slice($tArrDir, 0, count($tArrDir)-1);
                        $dataPath = implode('/', $tArrPath);
                        $arrFiles[] = '
                            <div class="dvRowFolder" data-path="'.$dataPath.'" onclick="cmsAssetUpload(this, \''.$postControlSettings['id'].'\', 11)">
                               <i class="fa fa-arrow-up" aria-hidden="true"></i> ..
                            </div>
                        ';
                    }

                    $arrDisplayFolders = glob(SITEROOTPATH."assets/{$path}", GLOB_ONLYDIR);
                    natcasesort($arrDisplayFolders);
                    foreach ($arrDisplayFolders as $filename) {
                        $tPathInfo = pathinfo($filename);

                        $tArr = explode('/', $filename);
                        foreach($tArr as $Index => $Value) {
                            if (isset($arrSITEROOTPATH[$Index]) && $arrSITEROOTPATH[$Index] == $Value) {
                                unset($tArr[$Index]);
                            }
                        }
                        $filename = implode('/', $tArr);

                        $arrFiles[] = '
                            <div class="dvRowFolder" data-path="'.$filename.'" onclick="cmsAssetUpload(this, \''.$postControlSettings['id'].'\', 11)">
                               '.$tPathInfo['filename'].'
                            </div>
                        ';
                    }

                    print implode('', $arrFiles);
                    exit;
                }

                if (isset($_POST["cmsAssetListDirFiles"])) {
                    $tFuncListFiles = function ($CONFIG, $assetsDir, $postControlSettings) {
                        $arrFiles = [];

                        $tFuncFileIcon = function ($pExtension) {
                            $pExtension = strtolower($pExtension);

                            $arrIcons = [
                                'jpg'=>'fa fa-file-image-o',
                                'gif'=>'fa fa-file-image-o',
                                'jpeg'=>'fa fa-file-image-o',
                                'png'=>'fa fa-file-image-o',
                                'pdf'=>'fa fa-file-pdf-o',
                                'xls'=>'fa fa-file-excel-o',
                                'xlsx'=>'fa fa-file-excel-o',
                                'doc'=>'fa fa-file-word-o',
                                'docx'=>'fa fa-file-word-o',
                                ''=>'fa fa-file-o'
                            ];

                            return (isset($arrIcons[$pExtension])) ? $arrIcons[$pExtension] : 'fa fa-file-o';
                        };


                        $path = ($_POST["cmsAssetListDirFiles"] != '') ? $_POST["cmsAssetListDirFiles"].'/*' : '*';
                        $arrSITEROOTPATH = explode('/', $assetsDir);

                        if ($path != '*') {
                            $tArrDir = explode('/', $_POST["cmsAssetListDirFiles"]);
                            $tArrPath = array_slice($tArrDir, 0, count($tArrDir)-1);
                            $dataPath = implode('/', $tArrPath);
                            $arrFiles[] = '
                                <tr data-path="'.$dataPath.'" data-type="folder" onclick="cmsAssetUpload(this, \''.$postControlSettings['id'].'\', 7, 0, \''.(($dataPath!='') ? $dataPath : '').'\')" style="cursor: pointer">
                                    <td colspan="3"><i class="fa fa-arrow-up" aria-hidden="true"></i> ..</td>
                                </tr>
                            ';
                        }

                        $arrDisplayFolders = glob(SITEROOTPATH."assets/{$path}", GLOB_ONLYDIR);
                        natcasesort($arrDisplayFolders);
                        foreach ($arrDisplayFolders as $filename) {
                            $tPathInfo = pathinfo($filename);

                            $tArr = explode('/', $filename);
                            foreach($tArr as $Index => $Value) {
                                if (isset($arrSITEROOTPATH[$Index]) && $arrSITEROOTPATH[$Index] == $Value) {
                                    unset($tArr[$Index]);
                                }
                            }
                            $filename = implode('/', $tArr);

                            $arrFiles[] = '
                                <tr data-path="'.$filename.'" data-type="folder" data-name="'.$tPathInfo['filename'].'">
                                    <td width="60%"><i class="fa fa-folder-o" aria-hidden="true"> </i> <a href="javascript:void(0)" onclick="cmsAssetUpload(this, \''.$postControlSettings['id'].'\', 7, 0, \''.$filename.'\')">'.$tPathInfo['filename'].'</a></td>
                                    <td width="25%">Folder</td>
                                    <td width="15%" style="text-align: right"><a href="javascript:void(0)" onclick="cmsAssetUpload(this, \''.$postControlSettings['id'].'\', 7, 2)" style="margin-right: 10px" data-dir="'.(($_POST["cmsAssetListDirFiles"]!='') ? $_POST["cmsAssetListDirFiles"] : '').'"><img src="'.RES_CMS_URL.'images/icon-rename.png" height="22" /></a><a href="javascript:void(0)" onclick="cmsAssetUpload(this, \''.$postControlSettings['id'].'\', 7, 3, \''.$filename.'\')" data-type="folder" data-name="'.$tPathInfo['filename'].'" data-dir="'.(($_POST["cmsAssetListDirFiles"]!='') ? $_POST["cmsAssetListDirFiles"] : '').'"><i class="fa fa-times" aria-hidden="true"></i></a></td>
                                </tr>
                            ';
                        }

                        $tFileType = '*';
                        if ($postControlSettings["accept"]!='') {
                            $tArr = explode(',',$postControlSettings["accept"]);
                            array_walk($tArr,
                                function (&$v) {
                                    $v = str_replace('.', '', $v);
                                }
                            );
                            $tFileType = '*.{'.implode(',', $tArr).'}';
                        }
                        $path = ($_POST["cmsAssetListDirFiles"] != '') ? $_POST["cmsAssetListDirFiles"].'/'.$tFileType : $tFileType;

                        $arrDisplayFiles = array_filter(glob(SITEROOTPATH."assets/{$path}", GLOB_BRACE), 'is_file');
                        natcasesort($arrDisplayFiles);
                        foreach ($arrDisplayFiles as $filename) {
                            $tPathInfo = pathinfo($filename);

                            $tArr = explode('/', $filename);
                            foreach($tArr as $Index => $Value) {
                                if (isset($arrSITEROOTPATH[$Index]) && $arrSITEROOTPATH[$Index] == $Value) {
                                    unset($tArr[$Index]);
                                }
                            }
                            $assetFile = implode('/', $tArr);

                            $arrImageSize = [];
                            if (cmsTools::isImage($filename)) {
                                $arrImageSize[] = getimagesize($filename)[0];
                                $arrImageSize[] = getimagesize($filename)[1];
                            }

                            $tPathInfo2 = pathinfo($assetFile);
                            $tPathInfo2["dirname"] = ($tPathInfo2["dirname"] != '.') ? $tPathInfo2["dirname"] : '';

                            $arrFiles[] = '
                                <tr data-path="'.$assetFile.'" data-type="file" data-name="'.$tPathInfo['basename'].'" '.((cmsTools::isImage($filename)) ? 'data-image="1" data-image-size="'.implode('x', $arrImageSize).'"' : 'data-image="0"').'>
                                    <td width="55%"><i class="'.$tFuncFileIcon((isset($tPathInfo['extension'])) ? $tPathInfo['extension'] : '').'" aria-hidden="true"> </i> <a href="javascript:void(0)" onclick="cmsAssetUpload(this, \''.$postControlSettings['id'].'\', 7, 1)">'.$tPathInfo['basename'].'</a></td>
                                    <td width="25%">'.cmsTools::formatBytes(filesize($filename)).'</td>
                                    <td width="20%" style="text-align: right"><a href="?CMS_REQ='.urlencode(base64_encode(json_encode($postControlSettings))).'&asset-download='.$assetFile.'" style="margin-right: 10px"><i class="fa fa-download" aria-hidden="true"> </i></a><a href="javascript:void(0)" onclick="cmsAssetUpload(this, \''.$postControlSettings['id'].'\', 7, 2)" style="margin-right: 10px" data-dir="'.$tPathInfo2["dirname"].'"><img src="'.RES_CMS_URL.'images/icon-rename.png" height="22" /></a><a href="javascript:void(0)" onclick="cmsAssetUpload(this, \''.$postControlSettings['id'].'\', 7, 3, \''.$assetFile.'\')" data-type="file" data-name="'.$tPathInfo['basename'].'" data-dir="'.$tPathInfo2["dirname"].'"><i class="fa fa-times" aria-hidden="true"></i></a></td>
                                </tr>
                            ';
                        }

                        return $arrFiles;
                    };

                    print '<textarea class="cmsAssetListDirFiles">'.implode('', $tFuncListFiles($CONFIG, $assetsDir, $postControlSettings)).'</textarea>';

                    $tArrDir = explode('/', $_POST["cmsAssetListDirFiles"]);
                    $tArr = [];
                    foreach($tArrDir as $Index => $Value) {
                        $tArrPath = array_slice($tArrDir, 0, $Index);
                        $tDir = (count($tArrPath) > 0 ? implode('/', $tArrPath).'/' : '').$Value;
                        $tArr[$Index] = '<a href="javascript:void(0)" onclick="cmsAssetUpload(this, \''.$postControlSettings['id'].'\', 7, 0, \''.$tDir.'\')">'.$Value.'</a>';
                    }
                    print '<textarea class="cmsAssetListDirPath">'.implode('<span class="dir-separator">/</span>', explode('/',$CONFIG['website']['path'])).'<a href="javascript:void(0)" onclick="cmsAssetUpload(this, \''.$postControlSettings['id'].'\', 7, 0, \'\')">assets</a><span class="dir-separator">/</span>'.implode('<span class="dir-separator">/</span>', $tArr).'</textarea>';
                    exit;
                }
                break;
        }
    }

    exit;
}
?>