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

class cms_html
{
    private $controlObj = NULL;
    private $data = NULL;

    public $id = NULL;
    public $isRepeaterControl = false;
    public $group_name = "";

    public $vendor_js_path = array("tinymce/4.4.3/js/tinymce/tinymce.min.js", "tinymce/4.4.3/js/tinymce/jquery.tinymce.min.js");

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

    function css() {
        global $CONFIG;

        $resCMSURL = RES_CMS_URL;

        return <<<CSS
            .mce-ico.mce-i-far, .mce-ico.mce-i-fas {
                display: inline-block;
                font-family: 'Font Awesome 5 Free';
                /*font: normal normal normal 14px/1 'Font Awesome 5 Free';*/
                font-size: inherit;
                text-rendering: auto;
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
                
                font-weight: 900;
            }

            .mce-ico.mce-i-cmsassetimageleft {
                display: inline-block;
                background-image: url({$resCMSURL}images/icon-image-left.png);
                background-size: contain;
                background-repeat: no-repeat;
                height: 16px;
                width: 13px;
            }

            .mce-ico.mce-i-cmsassetimageright {
                display: inline-block;
                background-image: url({$resCMSURL}resources/cms/images/icon-image-right.png);
                background-size: contain;
                background-repeat: no-repeat;
                height: 16px;
                width: 13px;
            }

            .mce-ico.mce-i-cmsassetimagecaption {
                display: inline-block;
                background-image: url({$resCMSURL}resources/cms/images/icon-image-caption.png);
                background-size: contain;
                background-repeat: no-repeat;
                height: 16px;
                width: 13px;
            }
CSS;

    }


    function render() {
        $tId = ($this->id === NULL) ? $this->controlObj['id'] : $this->id;
        $tName = strval($this->controlObj['id']);
        $tCaption = (isset($this->controlObj['caption'])) ? '<label for="'.$tId.'">'.strval($this->controlObj['caption']).'</label>' : '';
        $tHeight = (isset($this->controlObj['height'])) ? strval($this->controlObj['height']) : 600;
        $toolbar = (isset($this->controlObj['toolbar'])) ? strval($this->controlObj['toolbar']) : 'undo redo | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table | link | hr | code'; #cmsAssetToolBar cmsAssetToolBarLeft cmsAssetToolBarRight cmsAssetToolBarCaption cmsAssetToolBarRemove
        $toolbar2 = (isset($this->controlObj['toolbar2'])) ? strval($this->controlObj['toolbar2']) : '';
        $contextMenu = (isset($this->controlObj['context_menu'])) ? strval($this->controlObj['context_menu']) : 'link openlink image inserttable | cell row column deletetable'; #cmsAssetContextMenu cmsAssetContextMenuLeft cmsAssetContextMenuRight cmsAssetContextMenuCaption cmsAssetContextMenuRemove
        $tValue = ($this->data !== NULL) ? $this->data : '';
        $tGroup = (isset($this->controlObj['group'])) ? 'cms-group="'.strval($this->controlObj['group']).'"' : "";
        $tAutoResizeMinHeight = (isset($this->controlObj['auto_resize_min_height'])) ? strval($this->controlObj['auto_resize_min_height']) : 400;
        if ($tGroup!='') {
            if ($this->group_name!=strval($this->controlObj['group'])) {
                $tGroup .= ' style="display: none"';
            }
        }

        $tClass = (!$this->isRepeaterControl) ? 'cms-form-control' : 'cms-form-control-repeater';

        $tContentCSS = (isset($this->controlObj['content_css'])) ? strval($this->controlObj['content_css']) : '';
        $tContentCSS = str_replace('[resources]', RES_URL, $tContentCSS);

        $tContainerStyle = (isset($this->controlObj['container-style'])) ? 'style="'.$this->controlObj['container-style'].'"' : '';
        $tContainerObjStyle = (isset($this->controlObj['container-obj-style'])) ? 'style="'.$this->controlObj['container-obj-style'].'"' : '';
        $tContainerObjClass = (isset($this->controlObj['container-obj-class'])) ? 'class="'.$this->controlObj['container-obj-class'].'"' : '';

        $arrTinyMCESettings = array(
            'height'=>$tHeight,
            'plugins'=> array(
                'advlist autolink lists link image charmap print preview anchor',
                'searchreplace visualblocks code fullscreen textcolor',
                'insertdatetime media table contextmenu paste code noneditable autoresize hr'
            ),
            "noneditable_noneditable_class"=> "mceNonEditable",
            "table_toolbar" => "",
            "autoresize_bottom_margin" => 25,
            "autoresize_min_height" => $tAutoResizeMinHeight,
            'menubar' => false,
            'toolbar1' => $toolbar,
            'contextmenu' => $contextMenu,
            'content_css' => VENDORS_URL.'fontawesome/4.6.3/css/font-awesome.min.css'.(($tContentCSS!='') ? ', '.$tContentCSS : ''),
            'relative_urls' => false,
            'remove_script_host' => false,
            'convert_urls' => false
        );
        if ($toolbar2!='') $arrTinyMCESettings['toolbar2'] = $toolbar2;

        $strTinyMCESettings = base64_encode(json_encode($arrTinyMCESettings));

        $controlSettings = array(
            'form_control_type'=>'asset',
            'id'=>$tName,
            'asset_default_dir' => '',
            'accept' => '.jpg,.jpeg,.png,.gif,.bmp,.tiff',
            'img_aspect_ratio' => ''
        );
        $strControlSettings = base64_encode(json_encode($controlSettings));

        $strSetup = strval($this->controlObj->setup);

        $arrBlocks = array();
        $strBlocks = '';
        if (isset($this->controlObj->blocks)) {
            foreach ($this->controlObj->blocks->block as $objBlock) {

                $arrControls = array();
                foreach ($objBlock->control as $objControl) {
                    $arrObj = array(
                        'id' => strval($objControl['id']),
                        'type' => strval($objControl['type']),
                        'caption' => strval($objControl['caption'])
                    );

                    if (isset($objControl['block_type'])) {
                        $arrObj['block_type'] = strval($objControl['block_type']);
                    } else {
                        $objControl['block_type'] = '';
                    }
                    if (isset($objControl['required'])) {
                        $arrObj['required'] = strval($objControl['required']);
                    }
                    if (isset($objControl['asset_default_dir'])) {
                        $arrObj['asset_default_dir'] = strval($objControl['asset_default_dir']);
                    }
                    if (isset($objControl['accept'])) {
                        $arrObj['accept'] = strval($objControl['accept']);
                    }
                    if (isset($objControl['img_aspect_ratio'])) {
                        $arrObj['img_aspect_ratio'] = strval($objControl['img_aspect_ratio']);
                    }

                    $arrControls[] = $arrObj;
                }

                $arrControlsHandle = array(
                    'id' => strval($objBlock['id']),
                    'type' => strval($objBlock['type']),
                    'caption' => strval($objBlock['caption']),
                    'title' => strval($objBlock['title']),
                    'controls' => $arrControls
                );

                $jsCall = '';
                if ($objBlock['type'] == 'gallery') {
                    $jsCall = 'cmsContentBlockFn(\'' . base64_encode(json_encode($arrControlsHandle)) . '\', null, 0);';
                } else if ($objBlock['type'] == 'picture') {
                    $jsCall = 'cmsContentPictureBlock(\'' . base64_encode(json_encode($arrControlsHandle)) . '\', null, 0);';
                }

                $arrBlocks[] = '
                editor.addButton(\'' . $objBlock['id'] . '\', {
                    tooltip: \'' . $objBlock['caption'] . '\',
                    context: \'' . $objBlock['caption'] . '\',
                    text: \'' . $objBlock['caption'] . '\',
                    icon: \'' . $objBlock['icon'] . '\',
                    onclick: function () {
                        ' . $jsCall . ';
                    }
                });
            ';
            }
        }
        $strBlocks = implode(' ', $arrBlocks);

        return <<<HTML
            <div class="form-group" {$tGroup} {$tContainerStyle}>
                {$tCaption}
                <div {$tContainerObjClass} {$tContainerObjStyle}>
                <textarea class="form-control {$tClass} html" id="{$tId}" name="{$tName}" cms-tinymce-settings="{$strTinyMCESettings}" cms-control-settings="{$strControlSettings}">{$tValue}</textarea>
                </div>
            </div>
            <script>
                var tinyMCESettings = JSON.parse(base64_decode($('#{$tId}').attr('cms-tinymce-settings')));
                tinyMCESettings['setup'] = function (editor) {
                    {$strSetup}
                    
                    {$strBlocks}
                }

                /*tinyMCESettings['style_formats'] = [
                    {
                      title: 'Image Left',
                      selector: 'img',
                      styles: {
                        'float': 'left',
                        'margin': '0 10px 0 10px'
                      }
                    },
                    {
                      title: 'Image Right',
                      selector: 'img',
                      styles: {
                        'float': 'right',
                        'margin': '0 0 10px 10px'
                      }
                    }
                ]*/

                tinyMCESettings['valid_elements'] = "@[id|class|style|title|dir<ltr?rtl|lang|xml::lang|onclick|ondblclick|"
                + "onmousedown|onmouseup|onmouseover|onmousemove|onmouseout|onkeypress|"
                + "onkeydown|onkeyup],a[rel|rev|charset|hreflang|tabindex|accesskey|type|"
                + "name|href|target|title|class|onfocus|onblur],strong/b,em/i,strike,u,"
                + "#p,-ol[type|compact],-ul[type|compact],-li,br,img[longdesc|usemap|"
                + "src|border|alt=|title|hspace|vspace|width|height|align|cms-data],-sub,-sup,"
                + "-blockquote,-table[border=0|cellspacing|cellpadding|width|frame|rules|"
                + "height|align|summary|bgcolor|background|bordercolor|cms-data],-tr[rowspan|width|"
                + "height|align|valign|bgcolor|background|bordercolor],tbody,thead,tfoot,"
                + "#td[colspan|rowspan|width|height|align|valign|bgcolor|background|bordercolor"
                + "|scope],#th[colspan|rowspan|width|height|align|valign|scope],caption,-div,"
                + "-span,-code,-pre,address,-h1,-h2,-h3,-h4,-h5,-h6,hr[size|noshade],-font[face"
                + "|size|color],dd,dl,dt,cite,abbr,acronym,del[datetime|cite],ins[datetime|cite],"
                + "object[classid|width|height|codebase|*],param[name|value|_value],embed[type|width"
                + "|height|src|*],script[src|type],map[name],area[shape|coords|href|alt|target],bdo,"
                + "button,col[align|char|charoff|span|valign|width],colgroup[align|char|charoff|span|"
                + "valign|width],dfn,fieldset,form[action|accept|accept-charset|enctype|method],"
                + "input[accept|alt|checked|disabled|maxlength|name|readonly|size|src|type|value],"
                + "kbd,label[for],legend,noscript,optgroup[label|disabled],option[disabled|label|selected|value],"
                + "q[cite],samp,select[disabled|multiple|name|size],small,"
                + "textarea[cols|rows|disabled|name|readonly],tt,var,big,div[cms-data|cms-data-val|cms-data-caption|cms-data-link|onclick|cms-block-control|cms-block-type],iframe[src|width|height|frameborder|style],button[class|style|onclick],span[cms-data|cms-data-val],z3r0101[cms-block-type]"

                $('#{$tId}').tinymce(tinyMCESettings);
            </script>
HTML;
    }
}