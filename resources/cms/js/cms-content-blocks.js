function cmsContentPictureBlock(pBlockObj, pObj, pOption, pData) {
    var arrBlockObj = json_decode(base64_decode(pBlockObj));

    var arrBlockRef = [
        function () {
            var arrForms = [];

            var strID = 'block_picture';
            var strControlSettings = base64_encode(json_encode(
                {
                    'form_control_type': 'asset',
                    'id': strID,
                    'asset_default_dir': '',
                    'accept': '',
                    'img_aspect_ratio': ''
                }
            ));

            arrForms[arrForms.length] = '\
                <div class="form-group">\
                    <label>Image:</label>\
                    <div class="input-group mb-3">\
                        <div class="input-group-prepend" style="cursor: pointer">\
                            <span class="input-group-text" id="'+strID+'Icon" onclick="cmsAssetUpload(this, \''+strID+'\', 0, \'input\', \'cmsContentBlockFn(\\\''+pBlockObj+'\\\', \\\''+strID+'\\\', 7)\'))"><i class="fa fa-upload" aria-hidden="true"></i></span>\
                        </div>\
                            <input type="text" class="form-control cms-upload" id="'+strID+'" cms-control-settings="'+strControlSettings+'" placeholder="Upload File" style="cursor: pointer" readonly onclick="cmsAssetUpload(this, \''+strID+'\', 0, \'input\', \'cmsContentBlockFn(\\\''+pBlockObj+'\\\', \\\''+strID+'\\\', 7)\')">\
                        <div class="input-group-prepend" style="cursor: pointer">\
                            <span class="input-group-text" id="'+strID+'X" onclick="$(\'#'+strID+'\').val(\'\'); $(this).hide()" style="display: none"><i class="fa fa-times" aria-hidden="true"></i></span>\
                        </div>\
                    </div>\
                </div>\
            ';

            /*arrForms[arrForms.length] = '\
                    <div class="form-group">\
                        <label>Title</label>\
                        <input type="text" id="block_picture_title" class="form-control">\
                    </div>\
            ';

            arrForms[arrForms.length] = '\
                    <div class="form-group">\
                        <label>Description:</label>\
                        <textarea id="block_picture_description" class="form-control"></textarea>\
                    </div>\
            ';*/

            return arrForms.join('');
        },
        function () {
            return true;
        }
    ];

    if (pOption == 0) {
        BootstrapDialog.show(
            {
                type: BootstrapDialog.TYPE_INFO,
                title: arrBlockObj['caption'],
                message: arrBlockRef[0],
                onshown: function () {
                },
                buttons: [
                    {
                        id: 'btn-save',
                        label: 'Insert',
                        action: function(dialog) {
                            if (!arrBlockRef[1]()) {
                                return false;
                            }

                            if (($('#block_picture_title').val().trim()+$('#block_picture_description').val().trim())=='') {
                                tinyMCE.activeEditor.insertContent('<img src="'+$('#block_picture').val()+'" style="display: block; float: left; margin-right: 1%; width: 33.3333%">');
                            } else {
                                tinyMCE.activeEditor.insertContent('<div class="mceNonEditable" style="display: block; float: left; margin-right: 1%; width: 33.3333%">\
                                        <img src="'+$('#block_picture').val()+'" style="max-width: 100%">\
                                        '+($('#block_picture_title').val().trim()!='' ? '<div>'+$('#block_picture_title').val().trim()+'</div>' : '')+'\
                                        '+($('#block_picture_description').val().trim()!='' ? '<div>'+$('#block_picture_description').val().trim()+'</div>' : '')+'\
                                     </div>\
                                ');
                            }
                            dialog.close();
                        }
                    },
                    {
                        id: 'btn-cancel',
                        label: 'Cancel',
                        action: function(dialog) {
                            dialog.close();
                        }
                    }
                ],
                //size: BootstrapDialog.SIZE_WIDE
            }
        );
    }
}

function cmsContentBlockFn(pBlockObj, pObj, pOption, pData) {
    var gridSort = null;

    pData = (typeof(pData) != 'undefined') ? pData : null;

    var arrBlockObj = json_decode(base64_decode(pBlockObj));

    var fieldTitle = '';
    var fieldDesc = '';
    var fieldImage = '';

    var arrForms = [];
    $.each(arrBlockObj['controls'],
        function (pIndex, pObj) {
            if (pObj['type'] == 'text') {
                arrForms[arrForms.length] = '\
                    <div class="form-group">\
                        <label>'+pObj['caption']+':</label>\
                        <input type="text" id="'+pObj['id']+'" class="form-control">\
                    </div>\
                ';
            } else if (pObj['type'] == 'textarea') {
                arrForms[arrForms.length] = '\
                    <div class="form-group">\
                        <label>'+pObj['caption']+':</label>\
                        <textarea id="'+pObj['id']+'" class="form-control"></textarea>\
                    </div>\
                ';
            } else if (pObj['type'] == 'asset') {
                var strControlSettings = base64_encode(json_encode(
                    {
                        'form_control_type': 'asset',
                        'id': pObj['id'],
                        'asset_default_dir': pObj['asset_default_dir'],
                        'accept': pObj['accept'],
                        'img_aspect_ratio': pObj['img_aspect_ratio']
                    }
                ));
                arrForms[arrForms.length] = '\
                    <div class="form-group">\
                        <label>'+pObj['caption']+':</label>\
                        <div class="input-group mb-3">\
                            <div class="input-group-prepend" style="cursor: pointer">\
                                <span class="input-group-text" id="'+pObj['id']+'Icon" onclick="cmsAssetUpload(this, \''+pObj['id']+'\', 0, \'input\', \'cmsContentBlockFn(\\\''+pBlockObj+'\\\', \\\''+pObj['id']+'\\\', 7)\'))"><i class="fa fa-upload" aria-hidden="true"></i></span>\
                            </div>\
                                <input type="text" class="form-control cms-upload" id="'+pObj['id']+'" cms-control-settings="'+strControlSettings+'" placeholder="Upload File" style="cursor: pointer" readonly onclick="cmsAssetUpload(this, \''+pObj['id']+'\', 0, \'input\', \'cmsContentBlockFn(\\\''+pBlockObj+'\\\', \\\''+pObj['id']+'\\\', 7)\')">\
                            <div class="input-group-prepend" style="cursor: pointer">\
                                <span class="input-group-text" id="'+pObj['id']+'X" onclick="$(\'#'+pObj['id']+'\').val(\'\'); $(this).hide()" style="display: none"><i class="fa fa-times" aria-hidden="true"></i></span>\
                            </div>\
                        </div>\
                    </div>\
                ';
            }
            if (pObj['block_type']) {
                if (pObj['block_type'] == 'image') {
                    fieldImage = pObj['id'];
                }
                if (pObj['block_type'] == 'title') {
                    fieldTitle = pObj['id'];
                }
                if (pObj['block_type'] == 'description') {
                    fieldDesc = pObj['id'];
                }
            }
        }
    );

    var arrBlockRef = [
        '\
            '+arrForms.join('')+'\
        ',
        function () {
            var ret = true;
            for(var i=0; i<arrBlockObj['controls'].length; i++) {
                if (arrBlockObj['controls'][i]['required']) {
                    if (arrBlockObj['controls'][i]['required'] == 'true') {
                        if ($('#' + arrBlockObj['controls'][i]['id']).val().trim() == '') {
                            $('#' + arrBlockObj['controls'][i]['id']).focus();
                            ret = false;
                            break;
                        }
                    }
                }
            }
            return ret;
        },
        function () {
            var arrBlockData = [];
            $.each(arrBlockObj['controls'],
                function (pIndex, pSubObj) {
                    arrBlockData[arrBlockData.length] = '<div cms-block-control="'+pSubObj['id']+'" cms-block-type="'+pSubObj['block_type']+'">'+$('#'+pSubObj['id']).val()+'</div>';
                }
            );

            return '\
                <div class="cms-html-item" style="overflow: hidden; display: block; float: left; width: 20%; height: 150px; margin-bottom: 0.4%">\
                    <div style="padding: 1%">\
                        <div style="position: relative; inline-block; width: 100%; height: 150px; background-color: #999">\
                            <div style="padding: 5%">\
                                '+((fieldImage!='') ? '<div cms-data="image" style="display: block; float: left; width: 50%; height: 120px; background-image: url('+(cmsFnDirName($('#'+fieldImage).val())+'/.cms.'+cmsFnBaseName($('#'+fieldImage).val()))+'); background-repeat: no-repeat; background-size: contain;"></div>' : '')+'\
                                <div style="display: block; float: left; width: 50%; font-size: 10px">\
                                    <div style="padding: 5%">\
                                        '+((fieldTitle!='') ? '<div cms-data="title" style="font-weight: bold; width: 100%">'+$('#'+fieldTitle).val()+'</div>' : '')+'\
                                        '+((fieldDesc!='') ? '<div cms-data="description" style="font-weight: normal; width: 100%">'+$('#'+fieldDesc).val()+'</div>' : '')+'\
                                    </div>\
                                </div>\
                                <z3r0101 class="cms-block-data" cms-block-type="'+arrBlockObj['type']+'" style="display: none">\
                                    '+arrBlockData.join('')+'\
                                </z3r0101>\
                            </div>\
                            <a href="javascript:void(0)" onclick="window.parent.cmsContentBlockFn(\''+pBlockObj+'\', this, 2)" style="position: absolute; width: 22px; height: 24px; top: 0px; right: 0px; font-size: 18px; background-color: rgb(0, 0, 0, .5); color: #fff;"><i class="fa fa-pencil-square-o" aria-hidden="true" style="margin: 4px"></i></a>\
                            <a href="javascript:void(0)" onclick="window.parent.cmsContentBlockFn(\''+pBlockObj+'\', this, 3)" style="position: absolute; width: 22px; height: 24px; bottom: 0px; right: 0px; font-size: 18px; background-color: rgb(0, 0, 0, .5); color: #fff;"><i class="fa fa-times" aria-hidden="true" style="margin: 4px"></i></a>\
                        </div>\
                    </div>\
                </div>\
            ';
        }
    ];

    if (pOption == 0) {
        //INITIAL ADD
        BootstrapDialog.show(
            {
                type: BootstrapDialog.TYPE_INFO,
                title: arrBlockObj['caption'],
                message: arrBlockRef[0],
                onshown: function () {
                },
                buttons: [
                    {
                        id: 'btn-save',
                        label: 'Insert',
                        action: function(dialog) {
                            if (!arrBlockRef[1]()) {
                                return false;
                            }

                            tinyMCE.activeEditor.insertContent('\
                                <div class="mceNonEditable">\
                                    <p><a href="javascript:void(0)" style="text-decoration: none" onclick="window.parent.cmsContentBlockFn(\''+pBlockObj+'\', this, 6)"><strong>'+((arrBlockObj['title']) ? arrBlockObj['title'] : '')+'</strong></a></p>\
                                    <div class="cms-html-block" cms-block-type="'+arrBlockObj['type']+'" style="position: relative; display: inline-block; width: 100%; height: auto; min-height: 150px; margin-bottom: 5px; background-color: #ccc;">\
                                        <div class="cms-html-toolbar" style="display: inline-block; width: 100%;">\
                                            <div style="padding: 0.5%">\
                                                <button class="btn btn-default" style="display: inline-block; padding: 0.5%; font-weight: bold; font-size: 1em; border: 1px solid transparent; border-radius: 4px;" onclick="window.parent.cmsContentBlockFn(\''+pBlockObj+'\', this, 1)"><i class="fa fa-plus" aria-hidden="true"></i> Add</button>\
                                                <button class="btn btn-default" style="display: inline-block; padding: 0.5%; font-weight: bold; font-size: 1em; border: 1px solid transparent; border-radius: 4px;" onclick="window.parent.cmsContentBlockFn(\''+pBlockObj+'\', this, 5)"><i class="fa fa-sort" aria-hidden="true"></i> Order</button>\
                                                <button class="btn btn-default" style="display: inline-block; float: right; padding: 0.5%; font-weight: bold; font-size: 1em; border: 1px solid transparent; border-radius: 4px;" onclick="window.parent.cmsContentBlockFn(\''+pBlockObj+'\', this, 4)"><i class="fa fa-times" aria-hidden="true"></i> Remove Block</button>\
                                            </div>\
                                        </div>\
                                        <div class="cms-html-container" style="display: inline-block; width: 100%;">\
                                            <div class="cms-html-container-data" style="padding: 0.5%">\
                                                '+arrBlockRef[2]()+'\
                                            </div>\
                                        </div>\
                                    </div>\
                                </div>\
                            ');
                            dialog.close();
                        }
                    },
                    {
                        id: 'btn-cancel',
                        label: 'Cancel',
                        action: function(dialog) {
                            dialog.close();
                        }
                    }
                ],
                size: BootstrapDialog.SIZE_WIDE
            }
        );
    } else if (pOption == 1) {
        //ADD
        BootstrapDialog.show(
            {
                type: BootstrapDialog.TYPE_INFO,
                title: arrBlockObj['caption'],
                message: arrBlockRef[0],
                onshown: function () {
                },
                buttons: [
                    {
                        id: 'btn-save',
                        label: 'Insert',
                        action: function(dialog) {
                            if (!arrBlockRef[1]()) {
                                return false;
                            }

                            $(pObj).parents('.cms-html-block').find('.cms-html-container-data').append(arrBlockRef[2]());

                            dialog.close();
                        }
                    },
                    {
                        id: 'btn-cancel',
                        label: 'Cancel',
                        action: function(dialog) {
                            dialog.close();
                        }
                    }
                ],
                size: BootstrapDialog.SIZE_WIDE
            }
        );
    } else if (pOption == 2) {
        //EDIT
        BootstrapDialog.show(
            {
                type: BootstrapDialog.TYPE_INFO,
                title: arrBlockObj['caption'],
                message: arrBlockRef[0],
                onshown: function () {
                    $.each(arrBlockObj['controls'],
                        function (pIndex, pSubObj) {
                            if (pSubObj['type'] == 'asset') {
                                $('#'+pSubObj['id']).val($(pObj).parents('.cms-html-item').find('.cms-block-data div[cms-block-control="'+pSubObj['id']+'"]').html());
                                $('#'+pSubObj['id']+'X').show();
                            } else {
                                $('#'+pSubObj['id']).val($(pObj).parents('.cms-html-item').find('.cms-block-data div[cms-block-control="'+pSubObj['id']+'"]').html());
                            }
                        }
                    );
                },
                buttons: [
                    {
                        id: 'btn-save',
                        label: 'Save',
                        action: function(dialog) {
                            $.each(arrBlockObj['controls'],
                                function (pIndex, pSubObj) {
                                    $(pObj).parents('.cms-html-item').find('.cms-block-data div[cms-block-control="'+pSubObj['id']+'"]').html($('#'+pSubObj['id']).val());

                                    if (pSubObj['block_type'] == 'image') {
                                        $(pObj).parents('.cms-html-item').find('div[cms-data="image"]').css('background-image', 'url('+(cmsFnDirName($('#'+pSubObj['id']).val())+'/.cms.'+cmsFnBaseName($('#'+pSubObj['id']).val()))+')');
                                        var tStyle = ($(pObj).parents('.cms-html-item').find('div[cms-data="image"]').attr('style'));
                                        $(pObj).parents('.cms-html-item').find('div[cms-data="image"]').attr('style', tStyle);
                                    } else if (pSubObj['block_type'] == 'title') {
                                        $(pObj).parents('.cms-html-item').find('div[cms-data="title"]').html($('#'+pSubObj['id']).val());
                                    } else if (pSubObj['block_type'] == 'description') {
                                        $(pObj).parents('.cms-html-item').find('div[cms-data="description"]').html($('#'+pSubObj['id']).val());
                                    }
                                }
                            );

                            dialog.close();
                        }
                    },
                    {
                        id: 'btn-cancel',
                        label: 'Cancel',
                        action: function(dialog) {
                            dialog.close();
                        }
                    }
                ],
                size: BootstrapDialog.SIZE_WIDE
            }
        );
    } else if (pOption == 3) {
        //DELETE
        $(pObj).parents('.cms-html-item').remove();
    } else if (pOption == 4) {
        //DELETE BLOCK
        $(pObj).parents('.cms-html-block').parent().remove();
    } else if (pOption == 5) {
        //SORT
        BootstrapDialog.show(
            {
                type: BootstrapDialog.TYPE_INFO,
                title: arrBlockObj['caption']+' : Order',
                message: '<div class="cms-html-order grid" style="position: relative; width: 100%; display: none"></div>',
                onshown: function () {
                    var tItemWidth = ($('.modal-body').width()/5)-5;
                    $(pObj).parents('.cms-html-block').find('.cms-html-container-data .cms-html-item').each(
                        function (pIndex, pSubObj) {
                            $('.cms-html-order.grid').append('\
                                              <div class="cms-html-order item" style="overflow: hidden; width: '+tItemWidth+'px">\
                                                <div class="item-content" style="position: relative; width: 100%; height: 100%;">\
                                                  <div style="padding: 5%">'+$(pSubObj).find('div[cms-data="image"]').parent().html()+'</div>\
                                                  \
                                                </div>\
                                              </div>\
                                            ');
                        }
                    );
                    $('.cms-html-order.grid a').hide();

                    $('.cms-html-order.grid').css('display', 'inline-block');
                    gridSort = new Muuri('.cms-html-order.grid',
                        {
                            dragEnabled: true
                        }
                    );
                    gridSort.on('dragEnd', function (item, event) {});
                },
                buttons: [
                    {
                        id: 'btn-save-order',
                        label: 'Save',
                        action: function(dialog) {
                            $(pObj).parents('.cms-html-block').find('.cms-html-container-data').empty();
                            $.each(gridSort.getItems(),
                                function (pIndex, pSubObj) {
                                    pSubObj = pSubObj.getElement();
                                    console.log($(pSubObj).find('div[cms-data="image"]').parent().html());
                                    $(pObj).parents('.cms-html-block').find('.cms-html-container-data').append('\
                                        <div class="cms-html-item" style="overflow: hidden; display: block; float: left; width: 20%; height: 150px; margin-bottom: 0.4%">\
                                            <div style="padding: 1%">\
                                                <div style="position: relative; inline-block; width: 100%; height: 150px; background-color: #999">\
                                                    <div style="padding: 5%">\
                                                        '+$(pSubObj).find('div[cms-data="image"]').parent().html()+'\
                                                    <a href="javascript:void(0)" onclick="window.parent.cmsContentBlockFn(\''+pBlockObj+'\', this, 2)" style="position: absolute; width: 22px; height: 24px; top: 0px; right: 0px; font-size: 18px; background-color: rgb(0, 0, 0, .5); color: #fff;"><i class="fa fa-pencil-square-o" aria-hidden="true" style="margin: 4px"></i></a>\
                                                    <a href="javascript:void(0)" onclick="window.parent.cmsContentBlockFn(\''+pBlockObj+'\', this, 3)" style="position: absolute; width: 22px; height: 24px; bottom: 0px; right: 0px; font-size: 18px; background-color: rgb(0, 0, 0, .5); color: #fff;"><i class="fa fa-times" aria-hidden="true" style="margin: 4px"></i></a>\
                                                </div>\
                                            </div>\
                                        </div>\
                                    ');

                                }
                            );
                            dialog.close();
                        }
                    },
                    {
                        id: 'btn-cancel-order',
                        label: 'Cancel',
                        action: function(dialog) {
                            dialog.close();
                        }
                    }
                ],
                size: BootstrapDialog.SIZE_WIDE
            }
        );
    } else if (pOption == 6) {
        //EDIT TITLE
        BootstrapDialog.show(
            {
                type: BootstrapDialog.TYPE_INFO,
                title: arrBlockObj['caption'],
                message: '\
                                    <div class="form-group">\
                                        <label>Block Title:</label>\
                                        <input type="text" id="inputTitle" class="form-control">\
                                    </div>\
                                ',
                onshown: function () {
                    var blockTitle = $(pObj).find('strong').html();
                    if (blockTitle == '[Insert Title]') {
                        blockTitle = '';
                    }
                    $('#inputTitle').val(blockTitle);
                },
                buttons: [
                    {
                        id: 'btn-save-order',
                        label: 'Save',
                        action: function(dialog) {
                            var blockTitle = $('#inputTitle').val().trim();
                            if (blockTitle == '' || blockTitle == '[Insert Title]') {
                                blockTitle = '[Insert Title]';
                                $(pObj).addClass('block-no-title');
                            } else {
                                $(pObj).removeClass('block-no-title');
                            }
                            $(pObj).html('<strong>'+blockTitle+'</strong>');
                            dialog.close();
                        }
                    },
                    {
                        id: 'btn-cancel-order',
                        label: 'Cancel',
                        action: function(dialog) {
                            dialog.close();
                        }
                    }
                ],
                closable: false
            }
        );
    } else if (pOption == 7) {
        $('#'+pObj+'X').show();
    }
}


function mcrFnBodyBlock(pType) {
    if (pType == 0) {
        mcrFnYouTubeVid(null, 0);
    } else if (pType == 1) {
        mcrFnGallery(null, 0);
    } else if (pType == 2) {
        mcrFnDownloadsLinks(null, 0);
    } else if (pType == 3) {
        if (tinymce.activeEditor.selection.getContent().trim()!='') {
            if (!$(tinymce.activeEditor.selection.getNode()).hasClass('mcr-body-quote')) {
                tinyMCE.activeEditor.insertContent('<div class="mcr-body-quote">'+tinymce.activeEditor.selection.getContent()+'</div>');
            } else {
                $(tinymce.activeEditor.selection.getNode()).removeClass('mcr-body-quote')
            }
        } else {
            BootstrapDialog.alert('No selected text.');
        }
    } else if (pType == 4) {
        if (tinymce.activeEditor.selection.getContent().trim()!='') {
            var strControlSettings = base64_encode(json_encode(
                {
                    'form_control_type': 'asset',
                    'id': 'MCR_Content_Body_Temp',
                    'asset_default_dir': cmsInfo["route"]["selectedUrlClass"],
                    'accept': '.jpg,.jpeg,.png,.gif,.bmp,.tiff',
                    'img_aspect_ratio': ''
                }
            ));

            var strControlSettingsDoc = base64_encode(json_encode(
                {
                    'form_control_type': 'asset',
                    'id': 'MCR_Content_Body_Temp',
                    'asset_default_dir': cmsInfo["route"]["selectedUrlClass"]+'/documents',
                    'accept': '.pdf,.doc,.docx,.xls,.xlsx,.xlsm,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.bmp,.tiff',
                    'img_aspect_ratio': ''
                }
            ));
            
            var iniFile = '';
            if ($(tinymce.activeEditor.selection.getNode()).attr('href')) {
                iniFile = $(tinymce.activeEditor.selection.getNode()).attr('href');
            }

            if (!$('#MCR_Content_Body_Temp')[0]) $('body').append('<input type="hidden" id="MCR_Content_Body_Temp" cms-control-settings="'+strControlSettingsDoc+'">');

            cmsAssetUpload(this, 'MCR_Content_Body_Temp', 0, 'custom', iniFile /*initial file*/,
                function (pFile) {
                    if (!$(tinymce.activeEditor.selection.getNode()).hasClass('mcr-body-attach-file')) {
                        tinyMCE.activeEditor.insertContent('<a href="'+pFile+'" class="mcr-body-attach-file" target="_blank">'+tinymce.activeEditor.selection.getContent()+'</a>');
                    } else {
                        tinymce.activeEditor.dom.setAttrib(tinymce.activeEditor.selection.getNode(), 'href' , pFile);
                    }
                }
            );
        } else {
            BootstrapDialog.alert('No selected text.');
        }
    }
}