<?php

class cms_assets extends BaseControllerCMS
{
    function __construct()
    {
        parent::__construct();
    }

    private $assetPath = '';

    public $arrAllowedExtensions = [
        'pdf','doc','docx','xls','xlsx','xlsm','ppt','pptx','jpg','jpeg','png','gif','bmp','tiff'
    ];

    public static $file_prefix = ['filename', '.', 'extension']; #['filename', '.', '[date("Y-d-m")]', '.', 'extension'];
    public static function file_format($pFileName)
    {
        $arrFileName = [];
        $arrPathInfo = pathinfo($pFileName);

        foreach (static::$file_prefix as $Index => $Value) {
            preg_match_all('#\[(.*?)\]#', $Value, $match);
            if (count($match[1]) > 0) {
                foreach ($match[1] as $subIndex => $subValue) {
                    eval("\$arrFileName[\$Index] = {$subValue};");
                }
            } else {
                if ($Value != '.') {
                    $arrFileName[$Index] = $arrPathInfo[$Value];
                } else {
                    $arrFileName[$Index] = $Value;
                }
            }
        }

        return implode('', $arrFileName);
    }

    function index()
    {
        global $CONFIG, $CMS_FN_MENU;

        if (isset($_REQUEST['CMS_POST_REQ'])) {
            $postControlSettings = json_decode(base64_decode(strval($_REQUEST['CMS_POST_REQ'])), true);

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
                $cmsAssetFileInfo = json_decode($_POST["cmsAssetFileInfo"], true);
                $cmsAssetFileInfo['upload_file'] = static::file_format($cmsAssetFileInfo['upload_file']);
                move_uploaded_file($_FILES['cmsAssetUploadFile']['tmp_name'], $assetsDir.$defaultPath.'/'.$cmsAssetFileInfo['upload_file']);
                if ($cmsAssetFileInfo['image_info'][0] > 0) {
                    copy($assetsDir.$defaultPath.'/'.$cmsAssetFileInfo['upload_file'], $assetsDir.$defaultPath.'/.cms.'.$cmsAssetFileInfo['upload_file']);

                    require_once(VENDORSPATH . 'php-image-resize/ImageResize.php');
                    require_once(VENDORSPATH . 'php-image-resize/ImageResizeException.php');

                    $image = new \Gumlet\ImageResize($assetsDir.$defaultPath.'/.cms.'.$cmsAssetFileInfo['upload_file']);
                    $image->resizeToWidth(220);
                    $image->save($assetsDir.$defaultPath.'/.cms.'.$cmsAssetFileInfo['upload_file']);
                }
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
                                    <td width="60%"><i class="'.$tFuncFileIcon((isset($tPathInfo['extension'])) ? $tPathInfo['extension'] : '').'" aria-hidden="true"> </i> <a href="javascript:void(0)" onclick="cmsAssetUpload(this, \''.$postControlSettings['id'].'\', 7, 1)">'.$tPathInfo['basename'].'</a></td>
                                    <td width="25%">'.cmsTools::formatBytes(filesize($filename)).'</td>
                                    <td width="15%" style="text-align: right"><a href="?CMS_REQ='.urlencode(base64_encode(json_encode($postControlSettings))).'&asset-download='.$assetFile.'" style="margin-right: 10px"><i class="fa fa-download" aria-hidden="true"> </i></a><a href="javascript:void(0)" onclick="cmsAssetUpload(this, \''.$postControlSettings['id'].'\', 7, 2)" style="margin-right: 10px" data-dir="'.$tPathInfo2["dirname"].'"><img src="'.RES_CMS_URL.'images/icon-rename.png" height="22" /></a><a href="javascript:void(0)" onclick="cmsAssetUpload(this, \''.$postControlSettings['id'].'\', 7, 3, \''.$assetFile.'\')" data-type="file" data-name="'.$tPathInfo['basename'].'" data-dir="'.$tPathInfo2["dirname"].'"><i class="fa fa-times" aria-hidden="true"></i></a></td>
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

            exit;
        }


        $this->setFormLayoutData(APPPATH."views/cms/layout/forms/cms_assets.xml");

        $this->assetPath = SITEROOTPATH.$CONFIG['cms']['directory_assets_name'];


        if (isset($_GET['new-dir'])) {
            mkdir(((isset($_GET['dir'])) ? $this->assetPath.'/'.$_GET['dir'].'/' : $this->assetPath.'/').trim($_GET['new-dir']));
            header("location: ".WEBSITE_URL.'cms/assets'.((isset($_GET['dir']) ? '?dir='.$_GET['dir'] : '')));
            exit;
        }

        if (isset($_GET['rm-dir'])) {
            if ($_GET['rm-dir'] != '') {
                cmsTools::rmDir($this->assetPath.'/'.$_GET['rm-dir']);
            }
            header("location: ".WEBSITE_URL.'cms/assets'.((isset($_GET['dir']) ? '?dir='.$_GET['dir'] : '')));
            exit;
        }

        if (isset($_GET['rename']) && isset($_GET['name'])) {
            if ($_GET['rename'] != '') {

                $error = '';
                if (is_file(((isset($_GET['dir'])) ? $this->assetPath.'/'.$_GET['dir'].'/' : $this->assetPath.'/').trim($_GET['rename']))) {
                    $error = ''.$_GET['rename'].' already exist.';
                } else {
                    if (is_dir(((isset($_GET['dir'])) ? $this->assetPath.'/'.$_GET['dir'].'/' : $this->assetPath.'/').trim($_GET['rename']))) {
                        $error = ''.$_GET['rename'].' already exist.';
                    }
                }

                if ($error == '') {
                    rename(
                        ((isset($_GET['dir'])) ? $this->assetPath.'/'.$_GET['dir'].'/' : $this->assetPath.'/').trim($_GET['name']),
                        ((isset($_GET['dir'])) ? $this->assetPath.'/'.$_GET['dir'].'/' : $this->assetPath.'/').trim($_GET['rename'])
                    );

                    if (is_file(((isset($_GET['dir'])) ? $this->assetPath.'/'.$_GET['dir'].'/' : $this->assetPath.'/').'.cms.'.trim($_GET['name']))) {
                        rename(
                            ((isset($_GET['dir'])) ? $this->assetPath.'/'.$_GET['dir'].'/' : $this->assetPath.'/').'.cms.'.trim($_GET['name']),
                            ((isset($_GET['dir'])) ? $this->assetPath.'/'.$_GET['dir'].'/' : $this->assetPath.'/').'.cms.'.trim($_GET['rename'])
                        );
                    }
                }
            }
            header("location: ".WEBSITE_URL.'cms/assets'.((isset($_GET['dir']) ? '?dir='.$_GET['dir'].(($error!='') ? '&error='.$error : '') : ''.(($error!='') ? '?error='.$error : ''))));
            exit;
        }

        if (isset($_GET["download"])) {
            $tFile = $this->assetPath.'/'.$_GET["download"];

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

        if (isset($_GET['rm-file'])) {
            unlink($this->assetPath.'/'.$_GET['rm-file']);
            header("location: ".WEBSITE_URL.'cms/assets'.((isset($_GET['dir']) ? '?dir='.$_GET['dir'] : '')));
            exit;
        }

        if (isset($_POST['cmsPostDeleteSelected'])) {
            $arrPost = json_decode(base64_decode($_POST["cmsPostDeleteSelected"]), true);

            foreach($arrPost as $Index => $Data) {
                if ($Data['type'] == 'folder') {
                    cmsTools::rmDir($this->assetPath.'/'.$Data['path']);
                } else if ($Data['type'] == 'file') {
                    unlink($this->assetPath.'/'.$Data['path']);
                }
            }

            print implode('', $this->cmsFnListFiles());

            exit;
        }

        if (isset($_POST['cmsAssetsUpload'])) {
            #print_r($_FILES);

            $path = (isset($_POST['cmsDirPath']) && $_POST['cmsDirPath'] != '') ? '/'.$_POST['cmsDirPath'] : '';

            if (isset($_POST['cmsDirPath']) && $_POST['cmsDirPath'] != '') {
                $_GET['dir'] = $_POST['cmsDirPath'];
            }

            $uploadFailed = false;
            $uploadFailedFile = '';
            foreach($_FILES['cmsFile']['tmp_name'] as $Index => $File) {
                $tPathInfo = pathinfo($_FILES['cmsFile']['name'][$Index]);
                if (in_array($tPathInfo['extension'], $this->arrAllowedExtensions)) {

                    move_uploaded_file($File, $this->assetPath.$path.'/'.static::file_format($_FILES['cmsFile']['name'][$Index]));

                    if (cmsTools::isImage($this->assetPath.$path.'/'.$_FILES['cmsFile']['name'][$Index])) {
                        copy($this->assetPath.$path.'/'.$_FILES['cmsFile']['name'][$Index], $this->assetPath.$path.'/.cms.'.static::file_format($_FILES['cmsFile']['name'][$Index]));

                        require_once(VENDORSPATH . 'php-image-resize/ImageResize.php');
                        require_once(VENDORSPATH . 'php-image-resize/ImageResizeException.php');

                        $image = new \Gumlet\ImageResize($this->assetPath.$path.'/.cms.'.$tPathInfo['basename']);
                        $image->resizeToWidth(220);
                        $image->save($this->assetPath.$path.'/.cms.'.$tPathInfo['basename']);
                    }
                } else {
                    $uploadFailed = true;
                    $uploadFailedFile = $_FILES['cmsFile']['name'][$Index];
                    break;
                }
            }

            if (!$uploadFailed) {
                print implode('', $this->cmsFnListFiles());
            } else {
                print '<tr class="upload-failed"><td>'.$uploadFailedFile.'</td></tr>';
            }

            exit;
        }

        if (isset($_POST["cmsPostBrowseFolder"])) {

            $arrFiles = [];

            $path = ($_POST['cmsPostBrowseFolder']!='') ? $_POST['cmsPostBrowseFolder'].'/*' : '*';
            $arrSITEROOTPATH = explode('/', $this->assetPath);

            if ($path != '*') {
                $tArrDir = explode('/', $_POST['cmsPostBrowseFolder']);
                $tArrPath = array_slice($tArrDir, 0, count($tArrDir)-1);
                $dataPath = implode('/', $tArrPath);
                $arrFiles[] = '
                    <div class="dvRowFolder" data-path="'.$dataPath.'" onclick="cmsFnBrowseFolder(this)">
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
                    <div class="dvRowFolder" data-path="'.$filename.'" onclick="cmsFnBrowseFolder(this)">
                       '.$tPathInfo['filename'].'
                    </div>
                ';
            }

            print implode('', $arrFiles);

            exit;
        }

        if (isset($_POST["cmsPostCopySelected"])) {
            $arrData = json_decode(base64_decode($_POST["cmsPostCopySelected"]), true);

            foreach($arrData['selected'] as $Index => $Data) {
                $tPath = pathinfo($Data['path']);
                if ($Data["type"] == 'file') {
                    copy($this->assetPath.'/'.$Data['path'], $this->assetPath.'/'.$arrData['destination'].'/'.$tPath['basename']);

                    if ($arrData['move']) {
                        unlink($this->assetPath.'/'.$Data['path']);
                    }
                } else if ($Data["type"] == 'folder') {
                    $tDir = $this->assetPath.'/'.$arrData['destination'].'/'.$tPath['basename'];
                    mkdir($tDir);
                    cmsTools::xcopy($this->assetPath.'/'.$Data['path'], $tDir);

                    if ($arrData['move']) {
                        cmsTools::rmDir($this->assetPath.'/'.$Data['path']);
                    }
                }
            }

            print implode('', $this->cmsFnListFiles());
            exit;
        }

        $arrFiles = $this->cmsFnListFiles();


        $dirPath = '';
        if (isset($_GET['dir'])) {
            $tArrDir = explode('/', $_GET['dir']);
            $tArr = [];
            foreach($tArrDir as $Index => $Value) {
                $tArrPath = array_slice($tArrDir, 0, $Index);
                $tDir = (count($tArrPath) > 0 ? implode('/', $tArrPath).'/' : '').$Value;
                $tArr[$Index] = '<a href="'.WEBSITE_URL.'cms/assets?dir='.$tDir.'">'.$Value.'</a>';
            }

            $dirPath = $_GET['dir'];
            $this->insertTagById("displayPath", implode('<span class="dir-separator">/</span>', explode('/',$CONFIG['website']['path'])).'<a href="'.WEBSITE_URL.'cms/assets">assets</a><span class="dir-separator">/</span>'.implode('<span class="dir-separator">/</span>', $tArr));
        } else {
            $this->deleteTagById("displayPathContainer");
        }

        $this->insertTagById("tableFilesBody", implode('', $arrFiles));

        $this->attributeById("dirPath", "value", $dirPath);

        $arrAccept = array();
        foreach($this->arrAllowedExtensions as $Index => $Extension) {
            $arrAccept[] = '.'.$Extension;
        }
        $this->attributeById("acceptedFiles", "value", implode(',',$arrAccept));

        $this->loadView("cms/cms_list", 1, ['self'=>$this, 'CONFIG'=>$CONFIG, 'CMS_FN_MENU'=>$CMS_FN_MENU]);
    }

    private function cmsFnListFiles() {
        $arrFiles = [];

        $path = (isset($_GET['dir'])) ? $_GET['dir'].'/*' : '*';
        $arrSITEROOTPATH = explode('/', $this->assetPath);

        if ($path != '*') {
            $tArrDir = explode('/', $_GET['dir']);
            $tArrPath = array_slice($tArrDir, 0, count($tArrDir)-1);
            $dataPath = implode('/', $tArrPath);
            $arrFiles[] = '
                <tr data-path="'.$dataPath.'" data-type="folder" onclick="window.location = \''.WEBSITE_URL.'cms/assets'.(($dataPath!='') ? '?dir='.$dataPath : '').'\'">
                    <td></td>
                    <td colspan="4"><i class="fa fa-arrow-up" aria-hidden="true"></i> ..</td>
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
                    <td><input type="checkbox" /></td>
                    <td><i class="fa fa-folder-o" aria-hidden="true"> </i> <a href="'.WEBSITE_URL.'cms/assets?dir='.$filename.'">'.$tPathInfo['filename'].'</a></td>
                    <td>Folder</td>
                    <td>'.date("Y-m-d H:i:s", filemtime(SITEROOTPATH.'assets/'.$filename)).'</td>
                    <td style="text-align: right"><a href="javascript:void(0)" onclick="cmsFnRename(this)" style="margin-right: 10px"><img src="'.RES_CMS_URL.'images/icon-rename.png" height="22" /></a><a href="'.WEBSITE_URL.'cms/assets?rm-dir='.$filename.((isset($_GET['dir'])) ? '&amp;dir='.$_GET['dir'] : '').'" onclick="return cmsFnConfirmRemove(this)" data-type="folder" data-name="'.$tPathInfo['filename'].'"><i class="fa fa-times" aria-hidden="true"></i></a></td>
                </tr>
            ';
        }

        $arrDisplayFiles = array_filter(glob(SITEROOTPATH."assets/{$path}"), 'is_file');
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
            if ($this->cmsFnIsImage($filename)) {
                $arrImageSize[] = getimagesize($filename)[0];
                $arrImageSize[] = getimagesize($filename)[1];
            }

            $arrFiles[] = '
                <tr data-path="'.$assetFile.'" data-type="file" data-name="'.$tPathInfo['basename'].'" '.(($this->cmsFnIsImage($filename)) ? 'data-image="1" data-image-size="'.implode('x', $arrImageSize).'"' : 'data-image="0"').'>
                    <td><input type="checkbox" /></td>
                    <td><i class="'.$this->cmsFnFileIcon((isset($tPathInfo['extension'])) ? $tPathInfo['extension'] : '').'" aria-hidden="true"> </i> <a href="javascript:void(0)" onclick="cmsFnFileInfo(this)">'.$tPathInfo['basename'].'</a></td>
                    <td>'.cmsTools::formatBytes(filesize($filename)).'</td>
                    <td>'.date("Y-m-d H:i:s", filemtime($filename)).'</td>
                    <td style="text-align: right"><a href="'.WEBSITE_URL.'cms/assets?download='.$assetFile.'" style="margin-right: 10px"><i class="fa fa-download" aria-hidden="true"> </i></a><a href="javascript:void(0)" onclick="cmsFnRename(this)" style="margin-right: 10px"><img src="'.RES_CMS_URL.'images/icon-rename.png" height="22" /></a><a href="'.WEBSITE_URL.'cms/assets?rm-file='.$assetFile.((isset($_GET['dir'])) ? '&amp;dir='.$_GET['dir'] : '').'" onclick="return cmsFnConfirmRemove(this)" data-type="file" data-name="'.$tPathInfo['basename'].'"><i class="fa fa-times" aria-hidden="true"></i></a></td>
                </tr>
            ';
        }

        return $arrFiles;
    }

    private function cmsFnFileIcon($pExtension) {
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
    }

    private function cmsFnIsImage($pFilePath) {
        if(@is_array(getimagesize($pFilePath))){
            return true;
        } else {
            return false;
        }
    }
}