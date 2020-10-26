function cmsFnCtrlRepeater_Fields(pCtrlId) {
    return eval('['+base64_decode($('#'+pCtrlId+'_Ctrl .repeater-table').val())+']');
}
function cmsFnCtrlRepeater_Asset(pCtrlId, pId, pOpt = 0) {
    if (pOpt == 0) {
        if ($('#'+pId).val().trim()!='') $('#'+pId+'_X').show();
    } else {
        $('#'+pId+'_X').hide();
        $('#'+pId).val('');
    }
    cmsFnCtrlRepeater_Update(pCtrlId, $('#'+pId)[0]);
}
function cmsFnCtrlRepeater_Block(pCtrlId, pRowIndex) {
    var arrData = cmsFnCtrlRepeater_Fields(pCtrlId);

    var strData = '';
    arrData.forEach(
        function (pData, pIndex) {
            if (typeof(pData['placeholder']) == 'undefined') pData['placeholder'] = '';

            var strCtrl = `<input type="text" class="form-control" placeholder="${pData['placeholder']}" data-ctrl-name="${pData['id']}" id="${pCtrlId+'_'+pData['id']+'_'+pRowIndex}" data-ctrl-type="${pData['type']}" onblur="cmsFnCtrlRepeater_Update('${pCtrlId}', this)" />`;
            if (pData['type'] == 'asset') {
                var controlSettings = {
                    form_control_type: 'asset',
                    id: pCtrlId+'_'+pData['id']+'_'+pRowIndex,
                    repeaterId: '',
                    asset_default_dir: pData['asset_default_dir'],
                    accept: pData['accept'],
                    img_aspect_ratio: pData['img_aspect_ratio'],
                    asset_url: ((pData['asset_url']) ? pData['asset_url'] : $('#'+pCtrlId+'_Ctrl').attr('data-asset-url'))
                };
                var strControlSettings = base64_encode(json_encode(controlSettings));
                strCtrl = `
                    <div class="input-group mb-3" style="cursor: pointer">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="${controlSettings['id']}_Icon" onclick="cmsAssetUpload(this, '${controlSettings['id']}', 0, 'input', 'cmsFnCtrlRepeater_Asset(\\'${pCtrlId}\\', \\'${controlSettings['id']}\\')');"><i class="fa fa-upload" aria-hidden="true"></i></span>
                        </div>
                        <input type="text" class="form-control cms-upload" data-ctrl-name="${pData['id']}" id="${controlSettings['id']}" data-ctrl-type="${pData['type']}" cms-control-settings="${strControlSettings}" placeholder="${(pData['placeholder']!='') ? pData['placeholder'] : 'Upload File'}" readonly="readonly" value="" style="cursor: pointer" onclick="cmsAssetUpload(this, '${controlSettings['id']}', 0, 'input', 'cmsFnCtrlRepeater_Asset(\\'${pCtrlId}\\', \\'${controlSettings['id']}\\')');">
                        <div class="input-group-prepend" style="cursor: pointer">
                            <span class="input-group-text" id="${controlSettings['id']}_X" onclick="cmsFnCtrlRepeater_Asset('${pCtrlId}', '${controlSettings['id']}', 1)" style="display: none"><i class="fa fa-times" aria-hidden="true"></i></span>
                        </div>
                    </div>
                `;
            } else if (pData['type'] == 'textarea') {
                strCtrl = `<textarea class="form-control" placeholder="${pData['placeholder']}" data-ctrl-name="${pData['id']}" id="${pCtrlId+'_'+pData['id']+'_'+pRowIndex}" data-ctrl-type="${pData['type']}" onblur="cmsFnCtrlRepeater_Update('${pCtrlId}', this)"> </textarea>`;
            } else if (pData['type'] == 'html') {
                strCtrl = `<textarea class="form-control" data-ctrl-name="${pData['id']}" id="${pCtrlId+'_'+pData['id']+'_'+pRowIndex}" data-ctrl-type="${pData['type']}"> </textarea>`;
            } else if (pData['type'] == 'select') {
                if (pData['options']) {
                    var tOption = '';
                    pData['options'].forEach(
                        function (pValue, pIndex) {
                            tOption += `<option value="${pIndex}">${pValue}</option>`;
                        }
                    );

                    strCtrl = `
                        <select class="form-control" data-ctrl-name="${pData['id']}" id="${pCtrlId+'_'+pData['id']+'_'+pRowIndex}" data-ctrl-type="${pData['type']}" onchange="cmsFnCtrlRepeater_Update('${pCtrlId}', this)">${tOption}</select>
                    `
                }
            } else if (pData['type'] == 'custom') {
                strCtrl = (pData['control_block']) ? `${pData['control_block']}<input type="hidden" data-ctrl-name="${pData['id']}" id="${pCtrlId+'_'+pData['id']+'_'+pRowIndex}" data-ctrl-type="${pData['type']}" />` : '';
            }

            strData += `
                <div class="form-group">
                    ${(pData['caption']) ? `<label>${pData['caption']}</label>` : ''}
                    <div>
                        ${strCtrl}
                    </div>
                </div>
            `;
        }
    );

    return strData;
}

function cmsFnCtrlRepeater_Render_Special(pType, pThis) {
    if (pType == 'textarea') {
        pThis.style.height = 'auto';
        pThis.style.height =
            (pThis.scrollHeight + 18) + 'px';
    }
}

function cmsFnCtrlRepeater_Render(pCtrlId, pData, pIndex) {
    var arrDataFields = cmsFnCtrlRepeater_Fields(pCtrlId);
    arrDataFields.forEach(
        function (pField, pFieldIndex) {
            if (pField['type'] == 'asset') {
                var tObj = $(`#${pCtrlId}_Ctrl .repeater-container .repeater-item[data-index="${pIndex}"] input[data-ctrl-name="${pField['id']}"]`);
                tObj.val(pData[pField['id']]);
                cmsFnCtrlRepeater_Asset(pCtrlId, tObj.attr('id'), (pData[pField['id']] != '' ? 0 : 1));
            } else if (pField['type'] == 'html') {
                $(`#${pCtrlId}_Ctrl .repeater-container .repeater-item[data-index="${pIndex}"] .form-control[data-ctrl-name="${pField['id']}"]`).val(pData[pField['id']]);

                var arrTinyMCESettings = {};
                arrTinyMCESettings['selector'] = `#${pCtrlId}_Ctrl .repeater-container .repeater-item[data-index="${pIndex}"] .form-control[data-ctrl-name="${pField['id']}"]`;
                arrTinyMCESettings['mode'] = 'textarea';
                arrTinyMCESettings['height'] = 200;
                arrTinyMCESettings['plugins'] = [
                    'advlist autolink lists link image charmap print preview anchor',
                    'searchreplace visualblocks code fullscreen textcolor',
                    'insertdatetime media table contextmenu paste code noneditable autoresize hr'
                ];
                arrTinyMCESettings['table_toolbar'] = '';
                arrTinyMCESettings['menubar'] = false;
                arrTinyMCESettings['toolbar1'] = (!pField['toolbar']) ? 'undo redo | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table | link | hr | code' : pField['toolbar'];
                arrTinyMCESettings['relative_urls'] = false;
                arrTinyMCESettings['remove_script_host'] = false;
                arrTinyMCESettings['convert_urls'] = false;
                arrTinyMCESettings['paste_as_text'] = true;
                arrTinyMCESettings['valid_elements'] = "@[id|class|style|title|dir<ltr?rtl|lang|xml::lang|onclick|ondblclick|"
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

                arrTinyMCESettings['setup'] = function (editor) {
                    editor.on('blur', function (e) {
                        $(arrTinyMCESettings['selector']).val(editor.getContent());
                        cmsFnCtrlRepeater_Update(pCtrlId, $(arrTinyMCESettings['selector'])[0]);
                    });
                };

                if (pField['settings']) {
                    for (const [key, value] of Object.entries(pField['settings'])) {
                        arrTinyMCESettings[key] = value;
                    }
                }

                if (pField['cms_control_settings']) {
                    $(`#${pCtrlId}_Ctrl .repeater-container .repeater-item[data-index="${pIndex}"] .form-control[data-ctrl-name="${pField['id']}"]`).attr('cms-control-settings', base64_encode(json_encode(pField['cms_control_settings'])));
                }

                tinymce.init(arrTinyMCESettings);
            } else if (pField['type'] == 'custom') {
                $(`#${pCtrlId}_Ctrl .repeater-container .repeater-item[data-index="${pIndex}"] input[data-ctrl-name="${pField['id']}"]`).val(pData[pField['id']]);
                if (pField['control_initialize']) pField['control_initialize'](pCtrlId, pData, pIndex);
            } else {
                $(`#${pCtrlId}_Ctrl .repeater-container .repeater-item[data-index="${pIndex}"] .form-control[data-ctrl-name="${pField['id']}"]`).val(pData[pField['id']]);

                if (pField['onchange']) {
                    $(`#${pCtrlId}_Ctrl .repeater-container .repeater-item[data-index="${pIndex}"] .form-control[data-ctrl-name="${pField['id']}"]`).on('change',
                        function () {
                            pField['onchange'](pCtrlId, this);
                        }
                    );
                    $(`#${pCtrlId}_Ctrl .repeater-container .repeater-item[data-index="${pIndex}"] .form-control[data-ctrl-name="${pField['id']}"]`).change();
                }

                if (pField['type'] == 'textarea') {
                    var tObj = $(`#${pCtrlId}_Ctrl .repeater-container .repeater-item[data-index="${pIndex}"] .form-control[data-ctrl-name="${pField['id']}"]`);
                    tObj.on('input', function () {
                        cmsFnCtrlRepeater_Render_Special('textarea', this);
                    });
                    setTimeout(
                        function () {
                            cmsFnCtrlRepeater_Render_Special('textarea', $(tObj)[0]);
                        }, 1000
                    );
                }
            }
        }
    );
}

function cmsFnCtrlRepeater_Initialize(pCtrlId) {
    var dataBase64 = $('#'+pCtrlId+'_Ctrl').attr('data-base64');
    var dataEmptyInsert = $('#'+pCtrlId+'_Ctrl').attr('data-empty-insert');
    var ctrlButtonRemoveHide = $('#'+pCtrlId+'_Ctrl .repeater-container').attr('data-button-remove-hide');

    var arrData = [];
    if ($('#'+pCtrlId).val() != '') {
        if (dataBase64 == 'true')
            arrData = json_decode(base64_decode($('#'+pCtrlId).val()));
        else if (dataBase64 == 'false')
            arrData = json_decode($('#'+pCtrlId).val());
    }

    if (arrData.length > 0) {
        arrData.forEach(
            function (pData, pIndex) {
                $(`#${pCtrlId}_Ctrl .repeater-container`).append(
                    `
                        <div class="repeater-item position-relative pt-2" data-index="${pIndex}">
                            ${cmsFnCtrlRepeater_Block(pCtrlId, pIndex)}
                            <div class="position-absolute" style="top: 0.2rem; right: 0.2rem">
                                ${(ctrlButtonRemoveHide == 'false') ? `<button class="btn btn-secondary btn-sm" onclick="cmsFnCtrlRepeater_Remove('${pCtrlId}', this)"><i class="fas fa-times"></i></button>` : ``}
                            </div>
                        </div>
                    `
                );

                cmsFnCtrlRepeater_Render(pCtrlId, pData, pIndex);
            }
        )
    } else {
        if (dataEmptyInsert == 'true') {
            cmsFnCtrlRepeater_Add(pCtrlId);
        }
    }

    var rowMax = ($(`#${pCtrlId}_Ctrl`).attr('data-ctrl-row-max')) ? parseInt($(`#${pCtrlId}_Ctrl`).attr('data-ctrl-row-max'), 10) : 0;
    if (arrData.length >= rowMax) {
        $(`#${pCtrlId}_Ctrl .repeater-add-item`).addClass('d-none');
    } else {
        $(`#${pCtrlId}_Ctrl .repeater-add-item`).removeClass('d-none');
    }
}
function cmsFnCtrlRepeater_Add(pCtrlId) {
    var dataBase64 = $('#'+pCtrlId+'_Ctrl').attr('data-base64');
    var ctrlButtonRemoveHide = $('#'+pCtrlId+'_Ctrl .repeater-container').attr('data-button-remove-hide');


    var item_index = $('#'+pCtrlId+'_Ctrl .repeater-container .repeater-item').length;

    $(`#${pCtrlId}_Ctrl .repeater-container`).append(
        `
            <div class="repeater-item position-relative pt-2" data-index="${item_index}">
                ${cmsFnCtrlRepeater_Block(pCtrlId, item_index)}
                ${(ctrlButtonRemoveHide == 'false') ? `<div class="position-absolute" style="top: 0.2rem; right: 0.2rem"><button class="btn btn-secondary btn-sm" onclick="cmsFnCtrlRepeater_Remove('${pCtrlId}', this)"><i class="fas fa-times"></i></button></button></div>` : ``}
            </div>
        `
    );

    var arrData = [];
    if ($('#'+pCtrlId).val() != '') {
        if (dataBase64 == 'true')
            arrData = json_decode(base64_decode($('#'+pCtrlId).val()));
        else if (dataBase64 == 'false')
            arrData = json_decode($('#'+pCtrlId).val());
    }

    var arrFields = cmsFnCtrlRepeater_Fields(pCtrlId);

    arrData[item_index] = {};
    arrFields.forEach(
        function (pArrField, pIndexField) {
            arrData[item_index][pArrField['id']] = (typeof(pArrField['value'])!='undefined') ? pArrField['value'] : '';
        }
    );

    if (dataBase64 == 'true')
        $('#'+pCtrlId).val(base64_encode(json_encode(arrData)));
    else if (dataBase64 == 'false')
        $('#'+pCtrlId).val((json_encode(arrData)));

    cmsFnCtrlRepeater_Render(pCtrlId, arrData[item_index], item_index);

    var rowMax = ($('#'+pCtrlId+'_Ctrl').attr('data-ctrl-row-max')) ? parseInt($('#'+pCtrlId+'_Ctrl').attr('data-ctrl-row-max'), 10) : 0;
    if (arrData.length >= rowMax) {
        $('#'+pCtrlId+'_Ctrl .repeater-add-item').addClass('d-none');
    } else {
        $('#'+pCtrlId+'_Ctrl .repeater-add-item').removeClass('d-none');
    }
}
function cmsFnCtrlRepeater_Update(pCtrlId, pObj) {
    var dataBase64 = $('#'+pCtrlId+'_Ctrl').attr('data-base64');
    var dataIndex = $(pObj).parents('.repeater-item').attr('data-index');

    var arrData = [];
    if ($('#'+pCtrlId).val() != '') {
        if (dataBase64 == 'true')
            arrData = json_decode(base64_decode($('#'+pCtrlId).val()));
        else if (dataBase64 == 'false')
            arrData = json_decode($('#'+pCtrlId).val());
    }

    if (arrData[dataIndex]) {
        if ($(pObj)[0]) {
            arrData[dataIndex][$(pObj).attr('data-ctrl-name')] = $(pObj).val().trim();
        }
    }

    if (dataBase64 == 'true')
        $('#'+pCtrlId).val(base64_encode(json_encode(arrData)));
    else if (dataBase64 == 'false')
        $('#'+pCtrlId).val(json_encode(arrData));
}
function cmsFnCtrlRepeater_Remove(pCtrlId, pObj) {
    var dataBase64 = $('#'+pCtrlId+'_Ctrl').attr('data-base64');
    var dataIndex = $(pObj).parents('.repeater-item').attr('data-index');

    var arrData = [];
    if ($('#'+pCtrlId).val() != '') {
        if (dataBase64 == 'true')
            arrData = json_decode(base64_decode($('#'+pCtrlId).val()));
        else if (dataBase64 == 'false')
            arrData = json_decode($('#'+pCtrlId).val());
    }

    delete arrData[dataIndex];
    arrData = arrData.filter(val => val);

    if (dataBase64 == 'true')
        $('#'+pCtrlId).val(base64_encode(json_encode(arrData)));
    else if (dataBase64 == 'false')
        $('#'+pCtrlId).val(json_encode(arrData));

    $(pObj).parents('.repeater-item').remove();

    $('#'+pCtrlId+'_Ctrl .repeater-container .repeater-item').each(
        function (pIndex, pObj) {
            $(pObj).attr('data-index', pIndex);
        }
    );

    var rowMax = ($('#'+pCtrlId+'_Ctrl').attr('data-ctrl-row-max')) ? parseInt($('#'+pCtrlId+'_Ctrl').attr('data-ctrl-row-max'), 10) : 0;
    if (arrData.length >= rowMax) {
        $('#'+pCtrlId+'_Ctrl .repeater-add-item').addClass('d-none');
    } else {
        $('#'+pCtrlId+'_Ctrl .repeater-add-item').removeClass('d-none');
    }
}
function cmsFnCtrlRepeater_Sort(pCtrlId) {
    var dataBase64 = $('#'+pCtrlId+'_Ctrl').attr('data-base64');

    var arrData = [];
    if ($('#'+pCtrlId).val() != '') {
        if (dataBase64 == 'true')
            arrData = json_decode(base64_decode($('#'+pCtrlId).val()));
        else if (dataBase64 == 'false')
            arrData = json_decode($('#'+pCtrlId).val());
    }

    var arrFields = cmsFnCtrlRepeater_Fields(pCtrlId);

    var filteredResults = arrFields.filter(
        function(item) {
            return (item['default'] == true);
        }
    );
    var defaultField = '';
    if (filteredResults[0]) {
        defaultField = filteredResults[0]['id'];
    }

    var arrList = [];
    arrData.forEach(
        function (pData, pIndex) {

            var tArrResult = arrFields.filter(
                function(item) {
                    return (item['id'] == defaultField);
                }
            );

            if (typeof(tArrResult[0]['control_sort_render']) == 'undefined') {
                arrList[arrList.length] = `<li class="repeater-sort-item" data-index="${pIndex}">${(pData[defaultField].trim() != '') ? pData[defaultField] : '...'}</li>`;
            } else {
                var tDisplay = tArrResult[0]['control_sort_render'](pData, pIndex);
                arrList[arrList.length] = `<li class="repeater-sort-item" data-index="${pIndex}">${tDisplay}</li>`;
            }
        }
    );

    var tMsg = `
      <ul class="repeater-sort-container">
         ${arrList.join('')}
      </ul>
    `;

    BootstrapDialog.show(
        {
            title: 'Sort Order',
            message: tMsg.replace(/\r?\n|\r/g, ''),
            size: BootstrapDialog.SIZE_WIDE,
            onshown: function () {
                $('.repeater-sort-container').sortable(
                    {
                        axis: 'y',
                        forcePlaceholderSize: true,
                        placeholder: 'highlight',
                        start: function(e, ui){
                            ui.placeholder.height(ui.item.height());
                        },
                    }
                );
            },
            closable: false,
            buttons: [
                {
                    label: 'Cancel',
                    action: function (pDialog) {
                        pDialog.close();
                    }
                },
                {
                    label: 'Apply',
                    id: 'btn-add',
                    action: function (pDialog) {

                        var arrNewOrder = [];
                        $('.repeater-sort-container li').each(
                            function (pIndex, pObj) {
                                var tIndex = parseInt($(pObj).attr('data-index'), 10);
                                arrNewOrder[arrNewOrder.length] = arrData[tIndex];
                            }
                        );

                        arrNewOrder.forEach(
                            function (pObj, pIndex) {
                                arrFields.forEach(
                                    function (subObj, subIndex) {
                                        if (typeof(subObj['control_sort_update']) == 'undefined') {
                                            $(`#${pCtrlId}_Ctrl .repeater-container .repeater-item[data-index="${pIndex}"] .form-control[data-ctrl-name="${subObj['id']}"]`).val(pObj[subObj['id']]);
                                        } else {
                                            subObj['control_sort_update'](pObj, pIndex);
                                        }
                                    }
                                );
                            }
                        );

                        if (dataBase64 == 'true')
                            $('#'+pCtrlId).val(base64_encode(json_encode(arrNewOrder)));
                        else if (dataBase64 == 'false')
                            $('#'+pCtrlId).val((json_encode(arrNewOrder)));

                        pDialog.close();
                    }
                }
            ]
        }
    );
}