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

    include_once(APPPATH."views/cms/cms_list_js.php");
    exit;
}
$error = array();
?>
<!doctype html>
<html lang="en">
<head>
    @include('cms.cms_inc_head')
    <link href="{{VENDORS_URL}}DataTables/datatables.min.css" rel="stylesheet">

    <link href="{{RES_CMS_URL}}css/list.css" rel="stylesheet">

    <title>{!!$CONFIG['cms']['title']!!}</title>

    <?php
    if (isset($self->formLayoutData->header->link)) {
        foreach($self->formLayoutData->header->link as $Index => $cssExternal) {

            $cssExternal = str_replace('[resources]', RES_URL, $cssExternal->asXML());
            $cssExternal = str_replace('[RES_CMS_URL]', VENDORS_URL, $cssExternal);
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
                $styleBlock = str_replace('[RES_CMS_URL]', RES_CMS_URL, $styleBlock);
                $styleBlock = str_replace('[vendors]', VENDORS_URL, $styleBlock);
                print "\n".$styleBlock."\n";
            }

        }
    }

    if (isset($self->formLayoutData->header->script)) {
        foreach($self->formLayoutData->header->script as $Index => $Script) {

            $scriptBlock = str_replace('[resources]', RES_URL, $Script->asXML());
            $scriptBlock = str_replace('[RES_CMS_URL]', RES_CMS_URL, $scriptBlock);
            $scriptBlock = str_replace('[vendors]', VENDORS_URL, $scriptBlock);

            print "\n".$scriptBlock."\n";
        }
    }

    $arrCustomCSS = glob(RESPATH_WWW."css/custom.cms.*.css");
    foreach($arrCustomCSS as $Index => $File) {
        print '<link href="'.RES_URL.'css/'.basename($File).'" rel="stylesheet" type="text/css">';
    }
    ?>
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
                            foreach ($self->formLayoutData->body->navs as $navIndex => $navObj) {
                                print '<ul class="cms-tabs nav nav-tabs">';
                                foreach ($navObj->children() as $Index => $Obj) {
                                    $navId = (isset($Obj["id"])) ? "id=\"" . $Obj["id"] . "\"" : "";
                                    $navHref = strval($Obj["href"]);
                                    $navCaption = strval($Obj->asXML());
                                    $navClass = strval($Obj["class"]);
                                    $navHref = $self->layoutConfigCode($navHref);
                                    print '<li class="nav-item"><a href="' . $navHref . '" class="nav-link ' . $navClass . '" ' . $navId . '>' . $navCaption . '</a></li>';
                                }
                                print '</ul>';
                            }
                        }
                        ?>
                        <?php
                        if (count($error) == 0) {
                            if (isset($self->formLayoutData->body)) {
                                if (is_object($self->formLayoutData->body)) {
                                    foreach ($self->formLayoutData->body->children() as $tagObjects) {
                                        if (strval($tagObjects->getName()) == 'datatable') {
                                            $arrHTMLOut = array();
                                            $dataTable = (object)array('datatable' => $tagObjects);

                                            foreach ($dataTable as $child) {
                                                $arrListColumn = array();
                                                $tableId = strval($child->table['id']);
                                                $tableCaption = strval($child->table['caption']);
                                                $footer = strval($child->table['footer']);
                                                $table_order_field = (isset($self->dataTable[$tableId]['table_order_field'])) ? strval($self->dataTable[$tableId]['table_order_field']) : strval($child->table['table_order_field']);
                                                $table_order_by = (isset($self->dataTable[$tableId]['table_order_by'])) ? strval($self->dataTable[$tableId]['table_order_by']) : strval($child->table['table_order_by']);
                                                $tableDisplay = strval($child->table['display']);

                                                if ($tableDisplay != 'none') {
                                                    if ($table_order_field != '') $arrListColumn[$table_order_field] = 'asc';
                                                    if ($table_order_by != '') {
                                                        $tArr = explode(',', $table_order_by);
                                                        foreach ($tArr as $Index => $Value) {
                                                            $tSArr = explode(' ', trim($Value));
                                                            $arrListColumn[$tSArr[0]] = (isset($tSArr[1])) ? (($tSArr[1] != '') ? strtolower($tSArr[1]) : 'asc') : 'asc';
                                                        }
                                                    }

                                                    if (isset($tagObjects->table->buttons->button) && count($tagObjects->table->buttons->button) > 0) {
                                                        $arrHTMLOut[] = '<div class="cms-buttons row"><div class="col-12">';
                                                        $hasButtons = false;
                                                        foreach ($tagObjects->table->buttons->button as $subButton) {
                                                            $tVisible = (isset($subButton['visible'])) ? filter_var(strval($subButton['visible']), FILTER_VALIDATE_BOOLEAN) : true;
                                                            if ($tVisible && isset($subButton['type'])) {
                                                                if ($subButton['type'] == 'add') {
                                                                    $buttonId = $child->table['id'] . '_top_add';
                                                                    $cmsUrl = ((isset($subButton["cms-url"])) ? 'cms-url="'.strval($subButton["cms-url"]).'"' : '');

                                                                    $cmsUrl = str_replace('[CONFIG_WEBSITE_PATH]', $CONFIG['website']['path'], $cmsUrl);

                                                                    $arrHTMLOut[] = '<a class="btn btn-secondary cms-button add" cms-data-dt="'.$child->table['id'].'" href="javascript:void(0)" role="button" id="' . $buttonId . '" '.$cmsUrl.'>' . $subButton . '</a>';
                                                                } else if ($subButton['type'] == 'delete') {
                                                                    $buttonId = $child->table['id'] . '_top_delete';
                                                                    $arrHTMLOut[] = '<a class="btn btn-secondary cms-button delete" cms-data-dt="'.$child->table['id'].'" href="javascript:void(0)" role="button" id="' . $buttonId . '" cms-target-datatable="'.$tableId.'">' . $subButton . '</a>';
                                                                } else if ($subButton['type'] == 'post') {
                                                                    $buttonId = (isset($subButton['id'])) ? $child->table['id'] . '_' . $subButton['id'] : "";
                                                                    $arrHTMLOut[] = '<a class="btn btn-secondary cms-button post" cms-data-dt="'.$child->table['id'].'" href="javascript:void(0)" role="button"' . (($buttonId != '') ? ' id="' . $buttonId . '"' : '') . '>' . $subButton . '</a>';
                                                                } else if ($subButton['type'] == 'custom') {
                                                                    $buttonId = (isset($subButton['id'])) ? $child->table['id'] . '_' . $subButton['id'] : "";
                                                                    $buttonHref = (isset($subButton['href'])) ? $subButton['href'] : "javascript:void(0)";

                                                                    $buttonHref = $self->layoutConfigCode($buttonHref);

                                                                    $buttonOnClick = (isset($subButton['onclick'])) ? 'onclick="'.$self->layoutConfigCode($subButton['onclick']).'"' : '';

                                                                    $tButtonCaption = '';
                                                                    foreach($subButton->children() as $tChild) {
                                                                        $tButtonCaption .= $tChild->asXML();
                                                                    }
                                                                    $tButtonCaption = ($tButtonCaption!='') ? $tButtonCaption : strval($subButton);
                                                                    $arrHTMLOut[] = '<a class="btn btn-secondary cms-button custom" cms-data-dt="'.$child->table['id'].'" href="' . $buttonHref . '" role="button"' . (($buttonId != '') ? ' id="' . $buttonId . '"' : '') . ' '.$buttonOnClick.'>' . $tButtonCaption . '</a>';
                                                                } else if ($subButton['type'] == 'batch_upload') {
                                                                    $buttonId = (isset($subButton['id'])) ? $child->table['id'] . '_' . $subButton['id'] : "";
                                                                    $tButtonCaption = '';
                                                                    foreach($subButton->children() as $tChild) {
                                                                        $tButtonCaption .= $tChild->asXML();
                                                                    }
                                                                    $tButtonCaption = ($tButtonCaption!='') ? $tButtonCaption : strval($subButton);
                                                                    $arrHTMLOut[] = '<a class="btn btn-secondary cms-button cms-batch-upload" cms-data-dt="'.$child->table['id'].'" href="javascript:void(0)" role="button"' . (($buttonId != '') ? ' id="' . $buttonId . '"' : '') . '>' . $tButtonCaption . '</a>';
                                                                }
                                                                $hasButtons = true;
                                                            }
                                                        }
                                                        if ($hasButtons) {
                                                            $arrHTMLOut[] = '<hr></div></div>';
                                                            foreach ($arrHTMLOut as $Index => $Value) {
                                                                $arrHTMLOut[$Index] = str_replace('[cms-list-spacing]', '', $Value);
                                                            }
                                                        } else {
                                                            unset($arrHTMLOut[count($arrHTMLOut)]);
                                                        }
                                                    } else {
                                                        foreach ($arrHTMLOut as $Index => $Value) {
                                                            $arrHTMLOut[$Index] = str_replace('[cms-list-spacing]', 'cms-list-spacing', $Value);
                                                        }
                                                    }


                                                    if (count($child->children())) {
                                                        $arrHTMLOut[] = '<table id="' . $child->table['id'] . '" cms-data="'.strval($child->table['cms-data']).'" cms-data-val="'.strval($child->table['cms-data-val']).'" class="table table-striped table-bordered table-hover" cellspacing="0" width="100%">';
                                                        foreach ($child->children() as $subChild) {

                                                            /* HEADER */
                                                            if (count($subChild->body->column) > 0) {
                                                                $arrHTMLOut[] = '<thead><tr>';
                                                                foreach ($subChild->body->column as $subColumn) {

                                                                    $tVisible = (isset($subColumn['visible'])) ? filter_var(strval($subColumn['visible']), FILTER_VALIDATE_BOOLEAN) : true;

                                                                    if ($tVisible) {
                                                                        $arrHTMLOut[] = "<th ".(isset($subColumn['fieldname']) ? 'cms-fieldname="'.$subColumn['fieldname'].'"' : '').">" . $subColumn['caption'] . "</th>";

                                                                        if (isset($arrListColumn[strval($subColumn['fieldname'])])) {
                                                                            unset($arrListColumn[strval($subColumn['fieldname'])]);
                                                                        }
                                                                    }

                                                                }

                                                                foreach ($arrListColumn as $Index => $Value) {
                                                                    $arrHTMLOut[] = "<th></th>";
                                                                }

                                                                $arrHTMLOut[] = '</tr></thead>';
                                                            }

                                                            /* HEADER */

                                                            /* FOOTER */
                                                            if ($footer == "yes") {
                                                                if (count($subChild->body->column) > 0) {
                                                                    $arrHTMLOut[] = '<tfoot><tr>';
                                                                    foreach ($subChild->body->column as $subColumn) {
                                                                        $arrHTMLOut[] = "<th>" . $subColumn['caption'] . "</th>";
                                                                    }

                                                                    if ($table_order_field != '') {
                                                                        $arrHTMLOut[] = "<th></th>";
                                                                    }

                                                                    $arrHTMLOut[] = '</tr></tfoot>';
                                                                }
                                                            }
                                                            /* FOOTER */


                                                        }
                                                        $arrHTMLOut[] = '</table>';

                                                        if (isset($tagObjects->table->buttons->button) && count($tagObjects->table->buttons->button) > 0) {
                                                            $arrHTMLOut[] = '<div class="cms-buttons row"><div class="col-12"><hr>';
                                                            foreach ($tagObjects->table->buttons->button as $subButton) {

                                                                $tVisible = (isset($subButton['visible'])) ? filter_var(strval($subButton['visible']), FILTER_VALIDATE_BOOLEAN) : true;

                                                                if ($tVisible && isset($subButton['type'])) {

                                                                    if ($subButton['type'] == 'add') {
                                                                        $buttonId = $child->table['id'] . '_bottom_add';
                                                                        $cmsUrl = ((isset($subButton["cms-url"])) ? 'cms-url="'.$self->layoutConfigCode(strval($subButton["cms-url"])).'"' : '');
                                                                        $arrHTMLOut[] = '<a class="btn btn-secondary cms-button add" cms-data-dt="'.$child->table['id'].'" href="javascript:void(0)" role="button" id="' . $buttonId . '" '.$cmsUrl.'>' . $subButton . '</a>';
                                                                    } else if ($subButton['type'] == 'delete') {
                                                                        $buttonId = $child->table['id'] . '_bottom_delete';
                                                                        $arrHTMLOut[] = '<a class="btn btn-secondary cms-button delete" cms-data-dt="'.$child->table['id'].'" href="javascript:void(0)" role="button" id="' . $buttonId . '" cms-target-datatable="'.$tableId.'">' . $subButton . '</a>';
                                                                    } else if ($subButton['type'] == 'post') {
                                                                        $buttonId = (isset($subButton['id'])) ? $child->table['id'] . '_' . $subButton['id'] : "";
                                                                        $arrHTMLOut[] = '<a class="btn btn-secondary cms-button post" cms-data-dt="'.$child->table['id'].'" href="javascript:void(0)" role="button"' . (($buttonId != '') ? ' id="' . $buttonId . '"' : '') . '>' . $subButton . '</a>';
                                                                    } else if ($subButton['type'] == 'custom') {
                                                                        $buttonId = (isset($subButton['id'])) ? $child->table['id'] . '_' . $subButton['id'] : "";
                                                                        $buttonHref = (isset($subButton['href'])) ? $subButton['href'] : "javascript:void(0)";

                                                                        $buttonHref = $self->layoutConfigCode($buttonHref);

                                                                        $buttonOnClick = (isset($subButton['onclick'])) ? 'onclick="'.$self->layoutConfigCode($subButton['onclick']).'"' : '';

                                                                        $tButtonCaption = '';
                                                                        foreach($subButton->children() as $tChild) {
                                                                            $tButtonCaption .= strval($tChild->asXML());
                                                                        }
                                                                        $tButtonCaption = ($tButtonCaption!='') ? $tButtonCaption : strval($subButton);
                                                                        $arrHTMLOut[] = '<a class="btn btn-secondary cms-button custom" cms-data-dt="'.$child->table['id'].'" href="' . $buttonHref . '" role="button"' . (($buttonId != '') ? ' id="' . $buttonId . '"' : '') . ' '.$buttonOnClick.'>' . $tButtonCaption . '</a>';

                                                                        #$arrHTMLOut[] = '<a class="btn btn-secondary cms-button custom" href="' . $buttonHref . '" role="button"' . (($buttonId != '') ? ' id="' . $buttonId . '"' : '') . ' '.$buttonOnClick.'>' . $subButton . '</a>';
                                                                    } else if ($subButton['type'] == 'batch_upload') {
                                                                        $buttonId = (isset($subButton['id'])) ? $child->table['id'] . '_' . $subButton['id'] : "";
                                                                        $tButtonCaption = '';
                                                                        foreach($subButton->children() as $tChild) {
                                                                            $tButtonCaption .= $tChild->asXML();
                                                                        }
                                                                        $tButtonCaption = ($tButtonCaption!='') ? $tButtonCaption : strval($subButton);
                                                                        $arrHTMLOut[] = '<a class="btn btn-secondary cms-button cms-batch-upload" cms-data-dt="'.$child->table['id'].'" href="javascript:void(0)" role="button"' . (($buttonId != '') ? ' id="' . $buttonId . '"' : '') . '>' . $tButtonCaption . '</a>';
                                                                    }

                                                                }


                                                            }
                                                            $arrHTMLOut[] = '</div></div>';
                                                            foreach ($arrHTMLOut as $Index => $Value) {
                                                                $arrHTMLOut[$Index] = str_replace('[cms-list-spacing]', '', $Value);
                                                            }
                                                        } else {
                                                            foreach ($arrHTMLOut as $Index => $Value) {
                                                                $arrHTMLOut[$Index] = str_replace('[cms-list-spacing]', 'cms-list-spacing', $Value);
                                                            }
                                                        }
                                                    }
                                                }
                                            }

                                            print '<div class="cms-datatable" data-grid="">'.(($tableCaption!='') ? '<div><h4>'.$tableCaption.'</h4></div>' : '').implode("", $arrHTMLOut).'</div>';
                                        } else if (strval($tagObjects->getName()) == 'dom') {
                                            $arrHTMLOut = array();

                                            #print_r($tagObjects->xpath('//*[@id="JT_Customer"]'));

                                            foreach($tagObjects->children() as $objXML) {
                                                $arrHTMLOut[] = strval($objXML->asXML());
                                            }

                                            #$arrHTMLOut[] = strval($tagObjects->asXML());

                                            print implode("", $arrHTMLOut);
                                        }
                                    }
                                }
                            }
                        } else {
                            print "<pre>".implode("\n", $error)."</pre>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('cms.cms_inc_body_end')

<script src="{{VENDORS_URL}}DataTables/datatables.js"></script>

<?php
$strQuery = (isset($_SERVER['QUERY_STRING'])) ? '&'.$_SERVER['QUERY_STRING'] : "";
?>
<script src="?cms-javascript<?=$strQuery?>"></script>

<?php
//if ($self->formLayoutData->body->script!='') {
//    echo <<<SCRIPT
//        <script>
//            {$self->formLayoutData->body->script}
//        </script>
//SCRIPT;
//}

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