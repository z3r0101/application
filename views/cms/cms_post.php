<?php
$cmsResVer = strtotime(date("Y-m-d H:i:s"));

if (isset($_GET["cms-style"])) {

    if (isset($self->formLayoutData->header->style->xpath('//*[@cms-style-name="'.$_GET["cms-style"].'"]')[0])) {
        header("Content-Type: text/css");
        $styleBlock = str_replace('[resources]', RES_URL, strval($self->formLayoutData->header->style->xpath('//*[@cms-style-name="'.$_GET["cms-style"].'"]')[0]));
        $styleBlock = str_replace('[vendors]', VENDORS_URL, $styleBlock);
        print "\n".$styleBlock."\n";
    }

    exit;
}

if (isset($_GET["cms-javascript"])) {

    if ($_GET["cms-javascript"]!='') {
        if (isset($self->formLayoutData->body->script->xpath('//*[@cms-javascript-name="'.$_GET["cms-javascript"].'"]')[0])) {
            header("Content-Type: text/javascript");
            $scriptBlock = str_replace('[resources]', RES_URL, strval($self->formLayoutData->body->script->xpath('//*[@cms-javascript-name="'.$_GET["cms-javascript"].'"]')[0]));
            $scriptBlock = str_replace('[vendors]', VENDORS_URL, $scriptBlock);
            print "\n". html_entity_decode($scriptBlock) . "\n";

            exit;
        }
    }

    include_once(APPPATH."views/cms/cms_post_js.php");
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    @include('cms.cms_inc_head')
    <!--link rel="stylesheet" href="https://unpkg.com/cropperjs/dist/cropper.css"-->
    <link href="{{RES_CMS_URL}}css/post.css" rel="stylesheet">
    <?php
    $arrVendorJS = array();
    $arrVendorCSS = array();
    $arrControlStyle = array();
    $arrControlJS = array();
    if (isset($self->formLayoutData->body)) {
        foreach ($self->formLayoutData->body->children() as $tagObjects) {
            if (strval($tagObjects->getName()) == 'panel') {
                $objectPanel = (object) array('panel' => $tagObjects);
                foreach ($objectPanel as $Panel) {
                    foreach($Panel as $Key => $Control) {
                        if ($Key == 'control') {
                            $tVisible = (isset($Control['visible'])) ? $Control['visible'] : 'true';
                            if ($tVisible == 'true') {
                                $formControlsPath = APPPATH.'views/cms/form.controls/'.$Control['type'].'.php';
                                if (file_exists($formControlsPath)) {
                                    include_once($formControlsPath);
                                    $classControlName = "cms_{$Control['type']}";
                                    if (class_exists($classControlName)) {
                                        $controlObj = new $classControlName($Control);
                                        if (isset($controlObj->vendor_js_path)) {
                                            if (is_string($controlObj->vendor_js_path)) {
                                                if ($controlObj->vendor_js_path!='')
                                                    $arrVendorJS[] = $controlObj->vendor_js_path;
                                            } else {
                                                foreach($controlObj->vendor_js_path as $subIndex => $subData) {
                                                    if ($subData!='')
                                                        $arrVendorJS[] = $subData;
                                                }
                                            }
                                        }
                                        if (isset($controlObj->vendor_css_path)) {
                                            if (is_string($controlObj->vendor_css_path)) {
                                                if ($controlObj->vendor_css_path!='')
                                                    $arrVendorCSS[] = $controlObj->vendor_css_path;
                                            } else {
                                                foreach($controlObj->vendor_css_path as $subIndex => $subData) {
                                                    if ($subData!='')
                                                        $arrVendorCSS[] = $subData;
                                                }
                                            }
                                        }
                                        if (isset($controlObj->control_style)) {
                                            $arrControlStyle[strval($Control['type'])] = $controlObj->control_style;
                                        }
                                        if (isset($controlObj->control_js)) {
                                            $arrControlJS[strval($Control['type'])] = $controlObj->control_js;
                                        }
                                    }
                                }
                            }
                        } else if ($Key == 'repeater') {
                            foreach($Control as $controlKey => $controlItem) {
                                if ($controlKey == 'control') {
                                    $formControlsPath = APPPATH.'views/cms/form.controls/'.$controlItem['type'].'.php';
                                    if (file_exists($formControlsPath)) {
                                        include_once($formControlsPath);
                                        $classControlName = "cms_{$controlItem['type']}";
                                        if (class_exists($classControlName)) {
                                            $controlObj = new $classControlName($controlItem);
                                            if (isset($controlObj->vendor_js_path)) {
                                                if (is_string($controlObj->vendor_js_path)) {
                                                    if ($controlObj->vendor_js_path!='')
                                                        $arrVendorJS[] = $controlObj->vendor_js_path;
                                                } else {
                                                    foreach($controlObj->vendor_js_path as $subIndex => $subData) {
                                                        if ($subData!='')
                                                            $arrVendorJS[] = $subData;
                                                    }
                                                }
                                            }
                                            if (isset($controlObj->vendor_css_path)) {
                                                if (is_string($controlObj->vendor_css_path)) {
                                                    if ($controlObj->vendor_css_path!='')
                                                        $arrVendorCSS[] = $controlObj->vendor_css_path;
                                                } else {
                                                    foreach($controlObj->vendor_css_path as $subIndex => $subData) {
                                                        if ($subData!='')
                                                            $arrVendorCSS[] = $subData;
                                                    }
                                                }
                                            }
                                            if (isset($controlObj->control_style)) {
                                                $arrControlStyle[$Control['type']] = $controlObj->control_style;
                                            }
                                            if (isset($controlObj->control_js)) {
                                                $arrControlJS[$Control['type']] = $controlObj->control_js;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $arrVendorJS = array_unique($arrVendorJS);
        $arrVendorCSS = array_unique($arrVendorCSS);
    }
    if (count($arrVendorCSS)>0) {
        foreach($arrVendorCSS as $Index => $Data) {
            print '<link rel="stylesheet" href="'.VENDORS_URL.$Data.'">';
        }
    }
    if (count($arrVendorJS)>0) {
        foreach($arrVendorJS as $Index => $Data) {
            print '<script src="'.VENDORS_URL.$Data.'"></script>';
        }
    }

    if (count($arrControlStyle) > 0) {
        print '<style type="text/css">';
        foreach ($arrControlStyle as $Index => $Data) {
            print $Data;
        }
        print '</style>';
    }

    if (count($arrControlJS) > 0) {
        print '<script>';
        foreach ($arrControlJS as $Index => $Data) {
            print $Data;
        }
        print '</script>';
    }
    ?>

    <title>{{$CONFIG['cms']['title']}}</title>

    <?php
    if (isset($self->formLayoutData->header->link)) {
        foreach($self->formLayoutData->header->link as $Index => $cssExternal) {

            $cssExternal = str_replace('[resources]', RES_URL, $cssExternal->asXML());
            $cssExternal = str_replace('[vendors]', VENDORS_URL, $cssExternal);

            print "\n".$cssExternal."\n";
        }
    }

    if (isset($self->formLayoutData->header->style)) {
        foreach($self->formLayoutData->header->style as $Index => $Style) {

            if (isset($Style["cms-style-name"])) {
                print '<link href="?cms-style='.$Style["cms-style-name"].'" rel="stylesheet" type="text/css">';
            } else {
                $styleBlock = str_replace('[resources]', RES_URL, $Style->asXML());
                $styleBlock = str_replace('[vendors]', VENDORS_URL, $styleBlock);
                print "\n".$styleBlock."\n";
            }

        }
    }

    print '<script>var CMS_CONTROLS = {}; var CMS_CONTROLS_OBJ = {}</script>';

    if (isset($self->formLayoutData->header->script)) {
        foreach($self->formLayoutData->header->script as $Index => $Script) {

            if (isset($Script["cms-javascript-name"])) {
                print '<script src="?cms-javascript='.$Script["cms-javascript-name"].'"></script>';
            } else {
                $scriptBlock = str_replace('[resources]', RES_URL, $Script->asXML());
                $scriptBlock = str_replace('[vendors]', VENDORS_URL, $scriptBlock);
                print "\n". html_entity_decode($scriptBlock) . "\n";
            }

        }
    }
    ?>

    <style type="text/css">
        <?php
            if (isset($self->formLayoutData->body)) {
                foreach ($self->formLayoutData->body->children() as $tagObjects) {
                    if (strval($tagObjects->getName()) == 'panel') {
                        $objectPanel = (object) array('panel' => $tagObjects);
                        foreach ($objectPanel as $Panel) {
                            foreach($Panel as $Key => $Control) {
                                if ($Key == 'control') {
                                        $formControlsPath = APPPATH.'views/cms/form.controls/'.$Control['type'].'.php';
                                        if (file_exists($formControlsPath)) {
                                            include_once($formControlsPath);
                                            $classControlName = "cms_{$Control['type']}";
                                            if (class_exists($classControlName)) {
                                                $controlObj = new $classControlName($Control);

                                                if (method_exists($classControlName,'css')) {
                                                    print  $controlObj->css();
                                                }
                                            }

                                        }
                                } else if ($Key == 'repeater') {

                                }
                            }
                        }
                    }
                }
            }
        ?>
    </style>

    @php
    $arrCustomCSS = glob(RESPATH_WWW."css/custom.cms.*.css");
    foreach($arrCustomCSS as $Index => $File) {
        print '<link href="'.RES_URL.'css/'.basename($File).'" rel="stylesheet" type="text/css">';
    }
    @endphp
</head>
<body>
<div class="wrapper">
    @include('cms.cms_inc_menu')
    <div class="cms-content-wrapper d-flex flex-column">
        <div class="cms-content">
            @include('cms.cms_inc_header')
            <div class="container-fluid">
                @if(isset($self->formLayoutData->header))
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 cms-page-title">
                        {!!((isset($self->formLayoutData->header->title)) ? strval($self->formLayoutData->header->title) : '')!!}
                        {!!((isset($self->formLayoutData->header->blurb)) ? '<br><small>'.strval($self->formLayoutData->header->blurb).'</small>' : '')!!}
                    </h1>
                </div>
                @endif
                <div class="row">
                    <div class="col-12">
                        <?php
                        if (isset($self->formLayoutData->body->navs)) {
                            print '<ul class="cms-tabs nav nav-tabs">';
                            foreach($self->formLayoutData->body->navs->children() as $Index => $Obj) {
                                $navId = (isset($Obj["id"])) ? "id=\"".$Obj["id"]."\"" : "";
                                $navHref = strval($Obj["href"]);
                                $navCaption = strval($Obj->asXML());
                                $navClass = strval($Obj["class"]);
                                $navHref = $self->layoutConfigCode($navHref);
                                print '<li class="nav-item"><a href="'.$navHref.'" class="nav-link '.$navClass.'" '.$navId.'>'.$navCaption.'</a></li>';
                            }
                            print '</ul>';
                        }
                        ?>
                        <div class="row cms-alert-message">
                            <div class="col-12">
                                <div class="alert alert-success" role="alert">Form Saved</div>
                            </div>
                        </div>
                        <input type="hidden" class="cms-form-primary-id" id="{{$self->primaryId['name']}}" name="{{$self->primaryId['name']}}" value="{{$self->primaryId['value']}}" />
                        <input type="hidden" name="postTemp" id="postTemp" />
                        <?php
                        $crypt = new cmsCryptonite();
                        $token = bin2hex(openssl_random_pseudo_bytes(1000));
                        $token = hash('sha256', $token);
                        $arrToken = array(
                            'token'=>$crypt->encrypt($token),
                            'upload_temp'=>$crypt->encrypt(time())
                        );
                        $strToken = base64_encode(json_encode($arrToken));
                        ?>
                        <input type="hidden" class="cms-form-token" id="cmsFormToken" value="{{$strToken}}" />
                        <div id="accordion">
                            <?php
                            $arrHTMLOut = array();
                            $tCounter = 0;

                            if (isset($self->formLayoutData->body)) {
                                foreach ($self->formLayoutData->body->children() as $tagObjects) {
                                    if (strval($tagObjects->getName()) == 'panel') {
                                        $objectPanel = (object) array('panel' => $tagObjects);
                                        foreach ($objectPanel as $Panel) {
                                            $tCaption = "";
                                            if (isset($Panel["caption"])) {
                                                $tCaption = $Panel["caption"];
                                            }

                                            $tCollapseIn = ($tCounter == 0) ? ' in' : '';
                                            $tHeading = ($tCounter == 0) ? ' on' : '';

                                            if (isset($Panel["expanded"])) {
                                                $tExpanded = (strval($Panel["expanded"]) == 'true') ? true : false;
                                                if ($tExpanded)
                                                    $tCollapseIn = ' in';
                                                else
                                                    $tCollapseIn = '';
                                            }

                                            $arrHTMLOut[] = <<<EOL
                                        <div class="card">
                                            <div class="card-header cms-card-main" id="headingOne">
                                                <h5 class="mb-0">
                                                    <button class="btn btn-link" data-toggle="collapse" data-target="#collapse-{$tCounter}" aria-expanded="true" aria-controls="collapse-{$tCounter}">
                                                        {$tCaption}
                                                    </button>
                                                </h5>
                                            </div>
                                            <div id="collapse-{$tCounter}" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
                                                <div class="card-body">
EOL;

                                            foreach($Panel as $Key => $Control) {
                                                #$arrHTMLOut[] = $Control['type'];
                                                #$arrHTMLOut[] =  '<pre>';
                                                #$arrHTMLOut[] =  print_r($Control, true);
                                                #$arrHTMLOut[] =  '</pre><hr>';

                                                if ($Key == 'control') {
                                                    $tVisible = (isset($Control['visible'])) ? $Control['visible'] : 'true';
                                                    if ($tVisible == 'true') {
                                                        $formControlsPath = APPPATH.'views/cms/form.controls/'.$Control['type'].'.php';
                                                        if (file_exists($formControlsPath)) {
                                                            include_once($formControlsPath);
                                                            $classControlName = "cms_{$Control['type']}";
                                                            if (class_exists($classControlName)) {
                                                                $controlObj = new $classControlName($Control);

                                                                if (method_exists($classControlName,'postId')) {
                                                                    if (isset($self->requestSlug[0])) $controlObj->postId($self->requestSlug[0]);
                                                                }

                                                                //print_r($self->postFields[0]);
                                                                //print_r($self->postFields[0][strval($Control['id'])]); print '<hr>';
                                                                if (isset($self->postFields[0][strval($Control['id'])])) {
                                                                    $controlObj->value($self->postFields[0][strval($Control['id'])]);
                                                                }
                                                                $arrHTMLOut[] = $controlObj->render();
                                                            }

                                                        }
                                                    }
                                                } else if ($Key == 'repeater') {
                                                    $formControlsPath = APPPATH.'views/cms/form.controls/repeater.php';
                                                    if (file_exists($formControlsPath)) {
                                                        include_once($formControlsPath);
                                                        $classControlName = "cms_repeater";
                                                        if (class_exists($classControlName)) {
                                                            $controlObj = new $classControlName($Control);
                                                            $controlObj->postRepeaterFields = $self->postRepeaterFields;
                                                            $arrHTMLOut[] = $controlObj->render();
                                                        }
                                                    }
                                                } else if ($Key == 'dom') {
                                                    foreach($Control->children() as $objXML) {
                                                        $arrHTMLOut[] = strval($objXML->asXML());
                                                    }
                                                }
                                            }

                                            $arrHTMLOut[] = <<<EOL
                                                </div>
                                            </div>
                                        </div>
EOL;
                                            $tCounter++;
                                        }
                                    }
                                }
                            }
                            print implode('', $arrHTMLOut);
                            ?>

                        <hr>

                        <?php
                        if (isset($self->formLayoutData->body->buttons)) {

                            $arrPostButtons = array();

                            foreach($self->formLayoutData->body->buttons->children() as $buttonIndex => $buttonObj) {
                                #print '<pre>';
                                #print_r($buttonObj['type']);
                                #print '</pre>';

                                $tVisible = (isset($buttonObj['visible'])) ? filter_var(strval($buttonObj['visible']), FILTER_VALIDATE_BOOLEAN) : true;

                                if ($tVisible) {
                                    $tClass = (isset($buttonObj['class'])) ? strval($buttonObj['class']) : '';
                                    $tId = (isset($buttonObj['id'])) ? 'id="'.strval($buttonObj['id']).'"' : '';

                                    if (strval($buttonObj['type'])=='save') {
                                        $tCaption = (isset($buttonObj['caption'])) ? strval($buttonObj['caption']) : 'Save';
                                        $arrPostButtons[] = '<a class="btn btn-primary cms-button save '.$tClass.'" href="javascript:void(0)" role="button">'.$tCaption.'</a>';
                                    } else if (strval($buttonObj['type'])=='cancel') {
                                        $tCaption = (isset($buttonObj['caption'])) ? strval($buttonObj['caption']) : 'Cancel';
                                        $arrPostButtons[] = '<a class="btn btn-secondary cms-button cancel '.$tClass.'" href="javascript:void(0)" role="button">'.$tCaption.'</a>';
                                    } else if (strval($buttonObj['type'])=='custom') {
                                        $tCaption = (isset($buttonObj['caption'])) ? strval($buttonObj['caption']) : '';

                                        $tOnClick = (isset($buttonObj['onclick'])) ? ' onclick="'.strval($buttonObj['onclick']).'"' : '';
                                        $tStyle = (isset($buttonObj['style'])) ? ' style="'.strval($buttonObj['style']).'"' : '';
                                        $arrPostButtons[] = '<a '.$tId.' class="btn cms-button '.$tClass.'" href="javascript:void(0)" role="button"'.$tOnClick.$tStyle.'>'.$tCaption.'</a>';
                                    }
                                }
                            }

                            $strButtons = implode('', $arrPostButtons);
                            print <<<HTML
                                    <div class="row">
                                        <div class="col-12 cms-buttons post">
                                            {$strButtons}
                                        </div>
                                    </div>
HTML;

                        } else {
                            if (isset($self->formLayoutData->body)) {
                                print <<<HTML
                                    <div class="row">
                                        <div class="col-12 cms-buttons post">
                                            <button class="btn btn-primary cms-button save">Save</button>
                                            <button class="btn btn-secondary cms-button cancel">Cancel</button>
                                        </div>
                                    </div>
HTML;
                            }
                        }
                        ?>
                        <hr>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('cms.cms_inc_body_end')

<!--script src="{{VENDORS_URL}}jquery.cropper/cropper.js"></script>
<script src="{{VENDORS_URL}}jquery.cropper/jquery-cropper.js"></script-->

<script src="{{RES_CMS_URL}}js/cms-content-blocks.js"></script>

<script src="?cms-javascript<?=isset($_SERVER['QUERY_STRING']) ? '&'.$_SERVER['QUERY_STRING'] : ''?>"></script>

<?php
if (isset($self->formLayoutData->body->script)) {
    foreach($self->formLayoutData->body->script as $Index => $Script) {

        if (isset($Script["cms-javascript-name"])) {
            print '<script src="?cms-javascript='.$Script["cms-javascript-name"].'"></script>';
        } else {
            $scriptBlock = str_replace('[resources]', RES_URL, $Script->asXML());
            $scriptBlock = str_replace('[vendors]', VENDORS_URL, $scriptBlock);
            print "\n". html_entity_decode($scriptBlock) . "\n";
        }

    }
}
?>

<?php
$arrCMSInfo = array(
    'route'=>array(
        'selectedUrlClass'=>$self->selectedUrlClass,
        'selectedUrlMethod'=>$self->selectedUrlMethod
    ),
    'config'=>array(
        'environment'=>$CONFIG['environment'],
        'website'=>array(
            'path'=>$CONFIG['website']['path'],
            'domain'=>$CONFIG['website']['domain'],
            'url'=>$CONFIG['website']['url']
        )
    ),
    'config.cms'=>array(
        'directory_name'=>$CONFIG['cms']['directory_name'],
        'route_name'=>$CONFIG['cms']['route_name'],
        'directory_upload_name'=>$CONFIG['cms']['directory_upload_name']
    )
)
?>
<input type="hidden" id="cms-info" value="<?=base64_encode(json_encode($arrCMSInfo))?>" />
<script>
    var cmsInfo = json_decode(base64_decode($('#cms-info').val()));
</script>

<div class="cms-media"></div>
</body>
</html>