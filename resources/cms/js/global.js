var cmsInfo = [];

var cssIsScrMob = false;
var glbScrollBarWidth = 0;
var glbWinCall = function () {
    cssIsScrMob = (($('.cms-media')[0]) ? ((window.getComputedStyle($('.cms-media')[0],':after').content.toString().replace(/\"/g,'')=='mobile') ? true : false) : false);
    if ($(document).height() > $(window).height()) {
        glbScrollBarWidth = 15;
    }

    //Check datatable in mobile
    if (cssIsScrMob) {
        $('.dataTable').each(
            function (pIndex, pData) {
                if ($(pData).width() > $(window).width()) {
                    $(pData).parent().addClass('dt-overflow-table');
                    $(pData).parents('.dataTables_wrapper').addClass('dt-wrapper');
                }
            }
        );
    } else {
        $('.dataTable').each(
            function (pIndex, pData) {
                if ($(pData).width() > $(window).width()) {
                    $(pData).parent().removeClass('dt-overflow-table');
                    $(pData).parents('.dataTables_wrapper').removeClass('dt-wrapper');
                }
            }
        );
    }
}



$(document).ready(
    function () {
        $('.cms-mobile-menu').on('click',
            function () {
                if ($(this).hasClass('is-active')) {
                    $(this).removeClass('is-active');
                    $('.cms-sidebar').hide();
                    $('.cms-content .container-fluid').css({width: '100%'});
                } else {
                    $(this).addClass('is-active');
                    $('.cms-sidebar').show();
                    $('.cms-content .container-fluid').css({width: '200%'});
                }
            }
        );

        $('.cms-sidebar ul li.separator').each(
            function (pIndex, pObj) {
                if (!$('.cms-sidebar ul li[data-menu-name="'+$(pObj).attr('data-menu-parent')+'"]')[0]) {
                    $(pObj).remove();
                } else {
                    $(pObj).removeClass('d-none');
                }
            }
        );
    }
);


function cmsFnFileUploadDelete(pObj, pFileId, pIsTemp) {
    pIsTemp || (pIsTemp = false)

    if (pIsTemp) {
        var tCmsControlSettings = JSON.parse(base64_decode($('#'+pFileId).attr('cms-control-settings')));
        $.ajax(
            {
                cache: false,
                type: 'POST',
                data: 'control-settings='+$('#'+pFileId).attr('cms-control-settings')+'&cms-file-upload='+$('#cmsFormToken').val()+'&cms-file-upload-opt=1'
            }
        ).done(
            function (data) {

            }
        )
    }

    $('#'+pFileId+'_file').show();
    $('#'+pFileId).parents().find('.cms-file-remove').remove();
    $('#'+pFileId+'_display').val('Upload File');
    $('#'+pFileId).val('');
}

function cmsFnDirName(path) {
    const rx1 = /(.*)\/+([^/]*)$/;    // (dir/) (optional_file)
    const rx2 = /()(.*)$/;
    return (rx1.exec(path) || rx2.exec(path))[1];
}
function cmsFnBaseName(path) {
    const rx1 = /(.*)\/+([^/]*)$/;    // (dir/) (optional_file)
    const rx2 = /()(.*)$/;
    return (rx1.exec(path) || rx2.exec(path))[2];
}
function cmsFnFileExtension(pFileName) {
    const rx = /(?:\.([^.]+))?$/;
    return rx.exec(pFileName)[1];
}
function cmsFnValidateFileName(pFileName) {
    var rg1=/^[^\\/:\*\?"<>\|]+$/; // forbidden characters \ / : * ? " < > |
    var rg2=/^\./; // cannot start with dot (.)
    var rg3=/^(nul|prn|con|lpt[0-9]|com[0-9])(\.|$)/i; // forbidden file names
    return rg1.test(pFileName)&&!rg2.test(pFileName)&&!rg3.test(pFileName);
}


var $image = null;
var cropBoxData;
var canvasData;
var cropper;

function cmsFnFileUpload(pObj, pFileId) {
    $('#'+pFileId+'_display').val($(pObj)[0].files[0].name+' - Uploading 1%. Please wait...');

    var file_data = $(pObj).prop("files")[0];
    var form_data = new FormData();
    form_data.append("file", file_data);
    form_data.append('CMS_POST_REQ', $('#'+pFileId).attr('cms-control-settings'));
    //form_data.append('control-settings', $('#'+pFileId).attr('cms-control-settings'));
    form_data.append('cms-file-upload', $('#cmsFormToken').val());
    form_data.append('cms-file-upload-opt', 0);

    $.ajax({
        xhr: function() {
            var xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener("progress", function(evt) {
                if (evt.lengthComputable) {
                    var percentComplete = evt.loaded / evt.total;
                    //Do something with upload progress here
                    var progressCounter = parseInt(percentComplete*100, 10);
                    $('#'+pFileId+'_display').val($(pObj)[0].files[0].name+' - Uploading '+progressCounter+'%. Please wait...');
                }
            }, false);

            xhr.addEventListener("progress", function(evt) {
                if (evt.lengthComputable) {
                    var percentComplete = evt.loaded / evt.total;
                    //Do something with download progress
                    var progressCounter = parseInt(percentComplete*100, 10);
                    $('#'+pFileId+'_display').val($(pObj)[0].files[0].name+' - Uploading '+progressCounter+'%. Please wait...');
                }
            }, false);

            return xhr;
        },
        url: "",
        /*dataType: 'script',*/
        cache: false,
        contentType: false,
        processData: false,
        data: form_data,
        type: 'post',
        success: function(){
            $('#'+pFileId+'_display').val($(pObj)[0].files[0].name+' - Uploaded');
        }
    }).done(
        function (data) {
            var jsonData = JSON.parse(data);

            $('#'+pFileId+'_file').hide();
            $('#'+pFileId).val((json_encode(jsonData['control-settings']['file']))); //base64_encode
            $('#'+pFileId+'_display').after('<span class="input-group-text cms-file-remove"><a href="javascript:void(0)" onclick="cmsFnFileUploadDelete(this, \''+pFileId+'\', true)"><i class="fa fa-times" aria-hidden="true"></i></a></span>');
            $('#'+pFileId).attr('cms-control-settings', base64_encode(json_encode(jsonData['control-settings'])));

            if (jsonData['control-settings']['crop_size']) {
                var tImgSetSize = jsonData['control-settings']['crop_size'].split('x');
                tImgSetSize[0] = parseInt(tImgSetSize[0], 10);
                tImgSetSize[1] = parseInt(tImgSetSize[1], 10);
                var tImgSize = [0, 0];

                if (jsonData['control-settings']['file']['image_size'][0] && jsonData['control-settings']['file']['image_size'][1]) {
                    tImgSize[0] = jsonData['control-settings']['file']['image_size'][0];
                    tImgSize[1] = jsonData['control-settings']['file']['image_size'][1];
                }

                if ((tImgSetSize[0]+tImgSetSize[1]) != (tImgSize[0]+tImgSize[1])) {
                    var imgFile = cmsInfo['config']['website']['path']+jsonData['control-settings']['file']['path']+'/'+jsonData['control-settings']['file']['name'];

                    //style="width: 100%; height: '+($(window).height()-300)+'px"
                    BootstrapDialog.show({
                        title: 'Crop Image',
                        message: '\
                    <div class="cropper-container" cms-control-id="'+pFileId+'"><img id="image" src="'+imgFile+'" class="img-responsive">\
                        <input type="hidden" class="cropper-data" name="cropper_data">\
                        \
                    </div>\
                    ',
                        buttons: [{
                            label: 'Crop',
                            action: function(dialog) {
                                console.log($('.cropper-container[cms-control-id="'+pFileId+'"] input.cropper-data').val());

                                $.ajax(
                                    {
                                        type: 'POST',
                                        url: '',
                                        data: 'CMS_POST_REQ='+$('#'+pFileId).attr('cms-control-settings')+'&cms-file-upload='+$('#cmsFormToken').val()+'&cms-file-upload-opt=3&cropper-data='+$('.cropper-container[cms-control-id="'+pFileId+'"] input.cropper-data').val()
                                    }
                                ).done(
                                    function (data) {
                                        console.log(data);
                                        dialog.close();
                                    }
                                );
                            }
                        }, {
                            label: 'Cancel',
                            action: function(dialog) {
                                dialog.close();
                            }
                        }],
                        closable: false,
                        onshown: function () {
                            $image = $('#image');

                            var tCropSizeWidth = parseInt(jsonData['control-settings']['crop_size'].split('x')[0], 10);
                            var tCropSizeHeight = parseInt(jsonData['control-settings']['crop_size'].split('x')[1], 10);

                            cropper = new Cropper(image, {
                                aspectRatio: tCropSizeWidth/tCropSizeHeight,
                                autoCropArea: tCropSizeWidth/tCropSizeHeight,
                                ready: function () {

                                    // Strict mode: set crop box data first
                                    cropper.setCropBoxData(cropBoxData).setCanvasData(canvasData);
                                },
                                crop: function(e) {
                                    console.log('x:'+e.detail.x);
                                    console.log('y:'+e.detail.y);
                                    console.log('width:'+e.detail.width);
                                    console.log('height:'+e.detail.height);
                                    console.log('rotate:'+e.detail.rotate);
                                    console.log('scaleX:'+e.detail.scaleX);
                                    console.log('scaleY:'+e.detail.scaleY);

                                    /*
                                     var json = [
                                     '{"x":' + e.detail.x,
                                     '"y":' + e.detail.y,
                                     '"height":' + e.detail.height,
                                     '"width":' + e.detail.width,
                                     '"rotate":' + e.detail.rotate + '}'
                                     ].join();

                                     var json = [
                                     '{"x":' + e.detail.x,
                                     '"y":' + e.detail.y,
                                     '"height":' + tCropSizeHeight,
                                     '"width":' + tCropSizeWidth,
                                     '"rotate":' + e.detail.rotate + '}'
                                     ].join();
                                     */

                                    var json = [
                                        '{"x":' + e.detail.x,
                                        '"y":' + e.detail.y,
                                        '"height":' + e.detail.height,
                                        '"width":' + e.detail.width,
                                        '"rotate":' + e.detail.rotate + '}'
                                    ].join();

                                    console.log(json);

                                    $('.cropper-container[cms-control-id="'+pFileId+'"] input.cropper-data').val(json);

                                }
                            });

                        }
                    });
                }

            }
        }
    );
}

var $cmsAssetImage = null;
var cmsAssetEditHistory = [];
var cmsAssetEditHistoryPointer = 0;
var cmsAssetFileInfo = {};
var cmsAssetDialog = null;
function cmsAssetUpload(pObj, pId, pMode, pOption, pExt, pExt2) {
    pOption = (typeof(pOption) != 'undefined') ? pOption : null;
    pExt = (typeof(pExt) != 'undefined') ? pExt : null;
    pExt2 = (typeof(pExt2) != 'undefined') ? pExt2 : null;

    var cmsControlSettings = json_decode(base64_decode($('#'+pId).attr('cms-control-settings'))); //console.log(cmsControlSettings);
    if (cmsControlSettings['repeaterId']) {
        if (cmsControlSettings['repeaterId']!='') {
            cmsControlSettings['id'] = pId;
            $('#'+pId).attr('cms-control-settings', base64_encode(json_encode(cmsControlSettings)));
        }
    }

    var cmsAssetUploadFileType = {
        'jpg': {type: 'image', name: '', icon: ''},
        'gif': {type: 'image', name: '', icon: ''},
        'jpeg': {type: 'image', name: '', icon: ''},
        'png': {type: 'image', name: '', icon: ''},
        'bmp': {type: 'image', name: '', icon: ''},
        'tiff': {type: 'image', name: '', icon: ''},
        'pdf': {type: 'document', name: 'PDF File', icon: 'fa fa-file-pdf-o'},
        'xls': {type: 'document', name: 'Excel File', icon: 'fa fa-file-excel-o'},
        'xlsx': {type: 'document', name: 'Excel File', icon: 'fa fa-file-excel-o'},
        'xlsm': {type: 'document', name: 'Excel File', icon: 'fa fa-file-excel-o'},
        'doc': {type: 'document', name: 'Word Doc File', icon: 'fa fa-file-word-o'},
        'docx': {type: 'document', name: 'Word Doc File', icon: 'fa fa-file-word-o'},
        'ppt': {type: 'document', name: 'Power Point File', icon: 'fa-file-powerpoint-o'},
        'pptx': {type: 'document', name: 'Power Point File', icon: 'fa-file-powerpoint-o'}
    }

    var cmsAssetUploadMimeType = {
        'image/jpeg': 'jpg',
        'image/gif': 'gif',
        'image/png': 'png',
        'image/tiff': 'tif',
        'image/x-icon': 'ico',
        'application/pdf': 'pdf',
        'application/msword': 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'docx',
        'application/vnd.ms-excel': 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'xlxs',
        'application/vnd.ms-excel.sheet.macroEnabled.12': 'xlsm',
        'application/vnd.ms-powerpoint': 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation': 'pptx',
        'text/plain': 'txt',
        'video/mpeg': 'mpeg',
        'audio/x-wav': 'wav',
        'video/webm': 'webm',
        'audio/webm': 'weba',
        'video/3gpp': '3gp',
        'audio/3gpp': '3gp',
        'video/3gpp2': '3g2',
        'audio/3gpp2': '3g2',
        'audio/ogg': 'oga',
        'video/ogg': 'ogv',
        'audio/midi': 'mid',
        'audio/aac': 'aac',
        'video/x-msvideo': 'avi'
    };

    var cmsAssetUploadAcceptFile = cmsControlSettings['accept'].replace(/\./g,'').split(',');

    var cmsAssetUploadFileTypeIncluded = [];
    $.each(cmsAssetUploadAcceptFile,
        function (pIndex, pExt) {
            if (cmsAssetUploadFileType[pExt]) {
                if (!cmsAssetUploadFileTypeIncluded.includes(cmsAssetUploadFileType[pExt]['type'])) {
                    if (cmsAssetUploadAcceptFile.length > 1) {
                        cmsAssetUploadFileTypeIncluded[cmsAssetUploadFileTypeIncluded.length] = cmsAssetUploadFileType[pExt]['type'];
                    } else {
                        cmsAssetUploadFileTypeIncluded[cmsAssetUploadFileTypeIncluded.length] = cmsAssetUploadFileType[pExt]['name'];
                    }
                }
            }
        }
    );

    var cmsAssetLoadCropper = function () {
        var tArr = cmsControlSettings['img_aspect_ratio'].split(':');

        var tAspectRatio = cmsAssetFileInfo['image_info'][0]/cmsAssetFileInfo['image_info'][1];
        if (tArr.length == 2) {
            tAspectRatio = parseInt(tArr[0],10) / parseInt(tArr[1],10);
        } else {
            if (tArr[0].toLowerCase() == 'free') {
                tAspectRatio = NaN;
            }
        }

        $cmsAssetImage.cropper({
            aspectRatio: tAspectRatio,
            crop: function(event) {
            }
        });
        // Get the Cropper.js instance after initialized
        var cropper = $cmsAssetImage.data('cropper');
    };

    var cmsAssetLoadImage = function (pImageURL, pFileName, pFileType) {
        $('#cmsAssetUploadBody .cmsAssetUploadContainer').append('<img class="cmsAssetUploadImage" xsrc="'+pImageURL+'" style="position: absolute; top: -10000px; left: -10000px;">');
        $('#cmsAssetUploadBody .cmsAssetUploadImage').unbind('load');
        $('#cmsAssetUploadBody .cmsAssetUploadImage').on('load',
            function () {
                cmsAssetFileInfo['upload_file'] = pFileName;
                cmsAssetFileInfo['upload_file_type'] = pFileType;
                cmsAssetFileInfo['image_info'] = [$('#cmsAssetUploadBody .cmsAssetUploadImage')[0].width, $('#cmsAssetUploadBody .cmsAssetUploadImage')[0].height, pFileType];

                $('#cmsAssetUploadBody .cmsAssetUploadMessageLoading').hide();
                $('#cmsAssetUploadBody .cmsAssetUploadContainer').append(
                    '\
                    <div class="cmsAssetUploadImagePreviewContainer" style="position: absolute; width: 100%; height: 380px; top: 0; left: 0; right: 0; bottom: 0; margin: auto;">\
                        <img class="cmsAssetUploadImagePreview" src="'+pImageURL+'" style="max-width: 100%;">\
                    </div>\
                    '
                );
                $('#cmsAssetUploadBody .cmsAssetUploadImagePreviewContainer').width($('#cmsAssetUploadBody .cmsAssetUploadImagePreview').width());

                $cmsAssetImage = $('#cmsAssetUploadBody .cmsAssetUploadImagePreview');

                cmsAssetLoadCropper();

                $('#cmsAssetUploadBody .cmsAssetUploadImagePreview')[0].addEventListener('ready', function () {
                        /*var tCanvasData = $cmsAssetImage.cropper('getCanvasData');
                         var tContainerData =  $cmsAssetImage.cropper('getContainerData');
                         $cmsAssetImage.cropper('setCropBoxData', {"width":tContainerData['width'],"height":tContainerData['height']});
                         $cmsAssetImage.cropper('setCropBoxData', {"left":tCanvasData['left'],"top":tCanvasData['top']});*/
                        $cmsAssetImage.cropper('clear');
                    }
                );

                $('#cmsAssetUploadBody .cmsAssetUploadImagePreview')[0].addEventListener('cropstart', function (event) {
                        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="crop-ok"]').show();
                        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="crop-cancel"]').show();
                        cmsAssetKeyPress();
                    }
                );

                $('#cmsAssetUploadBody .cmsAssetUploadToolbar').show();
                $('#cmsAssetUploadBody .cmsAssetUploadSavePath').show();
                $('#cmsAssetUploadBody .cmsAssetUploadSavePath .cmsAssetUploadSavePathFile').html(cmsAssetFileInfo['upload_file']);
                cmsAssetDialog.getButton('btn-save').show();
                cmsAssetEditHistory[cmsAssetEditHistory.length] = $('#cmsAssetUploadBody .cmsAssetUploadImagePreview').attr('src');
            }
        );
        $('#cmsAssetUploadBody .cmsAssetUploadImage').attr('src', $('#cmsAssetUploadBody .cmsAssetUploadImage').attr('xsrc'));
    }

    var cmsAssetLoadFile = function (pXhrURL, pFileName, pFileType) {
        cmsAssetFileInfo['upload_file'] = pFileName;
        cmsAssetFileInfo['upload_file_type'] = pFileType;
        cmsAssetFileInfo['image_info'] = [0, 0, pFileType];

        var tExtension = cmsAssetUploadFileType[cmsFnFileExtension(pFileName).toLowerCase()]['icon'];

        $('#cmsAssetUploadBody .cmsAssetUploadMessageLoading').hide();
        $('#cmsAssetUploadBody .cmsAssetUploadContainer').append(
            '\
            <div class="cmsAssetUploadImagePreviewContainer" style="position: absolute; width: auto; height: 20px; top: 0; left: 0; right: 0; bottom: 0; margin: auto; text-align: center">\
                '+((tExtension!='') ? '<i class="'+tExtension+'" aria-hidden="true" style="font-size: 22px"></i> ' : '')+'<strong>'+pFileName+'</strong>\
            </div>\
            '
        );

        $('#cmsAssetUploadBody .cmsAssetUploadToolbar').show();
        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="crop"]').css('visibility', 'hidden');
        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="flip-horizontal"]').css('visibility', 'hidden');
        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="flip-vertical"]').css('visibility', 'hidden');
        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="rotate-left"]').css('visibility', 'hidden');
        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="rotate-right"]').css('visibility', 'hidden');
        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="redo"]').css('visibility', 'hidden');
        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="undo"]').css('visibility', 'hidden');
        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="redo"]').css('visibility', 'hidden');
        $('#cmsAssetUploadBody .cmsAssetUploadSavePath').show();
        $('#cmsAssetUploadBody .cmsAssetUploadSavePath .cmsAssetUploadSavePathFile').html(cmsAssetFileInfo['upload_file']);
        cmsAssetDialog.getButton('btn-save').show();
    }

    var cmsAssetPaste = function() {
        $(window).unbind('paste');
        $(window).on('paste',
            function (event) {
                var items = (event.clipboardData || event.originalEvent.clipboardData).items;
                for (index in items) {
                    var item = items[index];
                    if (item.kind === 'file') {
                        var blob = item.getAsFile();
                        var reader = new FileReader();
                        reader.onload = function(event){
                            var tArr = event.target.result.split(';');
                            var tFileType = tArr[0].substring(5);

                            if (/^image\/\w+$/.test(tFileType)) {

                                if (cmsAssetUploadAcceptFile.includes(cmsAssetUploadMimeType[tFileType])) {
                                    $('#cmsAssetUploadBody span').hide();
                                    $('#cmsAssetUploadBody .cmsAssetUploadMessage button').hide();
                                    $('#cmsAssetUploadBody .cmsAssetUploadFile').hide();
                                    $('#cmsAssetUploadBody .cmsAssetUploadMessageLoading').html('Uploading...');
                                    $('#cmsAssetUploadBody .cmsAssetUploadMessageLoading').show();

                                    if ($('#cmsAssetUploadBody .cmsAssetUploadContainer .cmsAssetUploadImage')[0]) {
                                        $('#cmsAssetUploadBody .cmsAssetUploadContainer .cmsAssetUploadImage').remove();
                                    }

                                    cmsAssetDialog.getButton('btn-save').html('Save');

                                    var tFileName = 'New Image';

                                    $('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-new-file', tFileType);

                                    if ($cmsAssetImage) {
                                        $cmsAssetImage.cropper('destroy');
                                        $('#cmsAssetUploadBody .cmsAssetUploadImagePreviewContainer').remove();
                                    }
                                    var uploadedImageURL = event.target.result; //URL.createObjectURL(blob);
                                    cmsAssetLoadImage(uploadedImageURL, tFileName, tFileType);
                                }
                            }

                        };
                        reader.readAsDataURL(blob);
                    }
                }
            }
        );
    };

    var cmsAssetKeyPress = function () {
        $(window).on('keypress',
            function (e) {
                var keyCode = (e.keyCode ? e.keyCode : e.which);
                if (keyCode == '13') {
                    if ($('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="crop-ok"]').css('display') == 'inline-block') {
                        cmsAssetUpload(pObj, pId, 2, 1);
                    }
                }
            }
        );
    }

    var cmsAssetFileTypeCaption = (((cmsAssetUploadFileTypeIncluded.length > 2) ? cmsAssetUploadFileTypeIncluded.join(', ') : cmsAssetUploadFileTypeIncluded.join(' and '))).toLowerCase().replace(/\b[a-z]/g, function(letter) {
        return letter.toUpperCase();
    });

    if (pMode == 0) {
        //OPEN ASSET BROWSER

        var tDialogHeader = cmsAssetFileTypeCaption;
        tDialogHeader = tDialogHeader.replace(/And/g, 'and');

        cmsAssetPaste();
        cmsAssetKeyPress();

        var cmsAssetIsTinyMCE = (pOption) ? ((pOption=='tinymce') ? true : false) : false;

        var cmsAssetIsInput = (pOption) ? ((pOption=='input') ? true : false) : false;

        var cmsAssetIsCustom = (pOption) ? ((pOption=='custom') ? true : false) : false;

        cmsAssetDialog = BootstrapDialog.show({
            type: BootstrapDialog.TYPE_INFO,
            title: 'CMS : Assets',
            message: '\
            <div id="cmsAssetDialogContainer">\
                <h3 style="margin-top: 0px">Insert '+tDialogHeader+'</h3>\
                <ul class="nav nav-tabs" style="margin-bottom: 10px">\
                    <li class="nav-item active"><a href="javascript:void(0)" class="nav-link active" onclick="cmsAssetUpload(this, \''+pId+'\', 6)">Upload</a></li>\
                    <li class="nav-item"><a href="javascript:void(0)" class="nav-link" onclick="cmsAssetUpload(this, \''+pId+'\', 6)">Assets</a></li>\
                    <li class="nav-item"><a href="javascript:void(0)" class="nav-link" onclick="cmsAssetUpload(this, \''+pId+'\', 6)">Web Address (URL)</a></li>\
                </ul>\
                <div id="cmsAssetUploadBody" class="cmsAssetDialogGroup" data-option="0" style="display: inline-block; width: 100%; height: 400px; margin-bottom: 10px">\
                    <div class="cmsAssetUploadToolbar" style="display: none; margin-bottom: 10px">\
                        <button data-type="crop" data-mode="-1" class="btn btn-default btn-sm" onclick="cmsAssetUpload(this, \''+pId+'\', 2, 0)" alt="Crop"><i class="fa fa-crop" aria-hidden="true"></i></button>\
                        <button data-type="crop-ok" style="display: none" class="btn btn-default btn-sm" onclick="cmsAssetUpload(this, \''+pId+'\', 2, 1)" alt="Crop"><i class="fa fa-check" aria-hidden="true"></i></button>\
                        <button data-type="crop-cancel" data-mode="-1" style="display: none" class="btn btn-default btn-sm" onclick="cmsAssetUpload(this, \''+pId+'\', 2, 2)" alt="Crop"><i class="fa fa-ban" aria-hidden="true"></i></button>\
                        <button data-type="flip-horizontal" data-mode="-1" class="btn btn-default btn-sm" onclick="cmsAssetUpload(this, \''+pId+'\', 2, 3)" alt="Crop"><img src="'+cmsInfo["config"]["website"]["path"]+'application/resources/cms/images/icon-flip-horizontal.png"></button>\
                        <button data-type="flip-vertical" data-mode="-1" class="btn btn-default btn-sm" onclick="cmsAssetUpload(this, \''+pId+'\', 2, 4)" alt="Crop"><img src="'+cmsInfo["config"]["website"]["path"]+'application/resources/cms/images/icon-flip-vertical.png"></button>\
                        <button data-type="rotate-left" class="btn btn-default btn-sm" onclick="cmsAssetUpload(this, \''+pId+'\', 2, 6)" alt="Rotate Left"><i class="fa fa-undo" aria-hidden="true"></i></button>\
                        <button data-type="rotate-right" class="btn btn-default btn-sm" onclick="cmsAssetUpload(this, \''+pId+'\', 2, 5)" alt="Rotate Right"><i class="fa fa-repeat" aria-hidden="true"></i></button>\
                        <button data-type="undo" disabled class="btn btn-default btn-sm" onclick="cmsAssetUpload(this, \''+pId+'\', 3)" alt="Undo"><i class="fa fa-reply" aria-hidden="true"></i></button>\
                        <button data-type="redo" disabled class="btn btn-default btn-sm" onclick="cmsAssetUpload(this, \''+pId+'\', 4)" alt="Redo"><i class="fa fa-share" aria-hidden="true"></i></button>\
                        <label data-type="import" class="btn btn-default btn-sm pull-right" for="cmsAssetUploadImport" title="Upload image file" style="margin-bottom: 0px; padding: 5px 10px;">\
                            <input type="file" '+((cmsControlSettings['accept']!='') ? 'accept="'+cmsControlSettings['accept']+'"' : '')+' onchange="cmsAssetUpload(this, \''+pId+'\', 5)" id="cmsAssetUploadImport" style="position: absolute; width: 1px; height: 1px; padding: 0; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0;">\
                            <i class="fa fa-upload"></i>\
                        </label>\
                    </div>\
                    <div class="cmsAssetUploadContainer" style="position: relative; display: inline-block; width: 100%; height: 400px; border: 3px dotted #ddd; overflow: hidden">\
                        <input class="cmsAssetUploadFile" onchange="cmsAssetUpload(this, \''+pId+'\', 1)" type="file" '+((cmsControlSettings['accept']!='') ? 'accept="'+cmsControlSettings['accept']+'"' : '')+' style="position: absolute; width: 140%; height: 450px; top: -50px; left: -310px; right: 0; bottom: 0; margin: auto; z-index: 100; cursor: pointer; overflow: hidden; white-space: nowrap;">\
                        <div class="cmsAssetUploadMessage" style="position: absolute; width: 200px; height: 100px; top: 0; left: 0; right: 0; bottom: 0; margin: auto; text-align: center; color: #999">\
                            <span style="display: inline-block; font-size: 18px; font-weight: bold; width: 100%; margin-bottom: 10px">Drag '+((cmsAssetUploadFileTypeIncluded.length > 2) ? cmsAssetUploadFileTypeIncluded.join(', ') : cmsAssetUploadFileTypeIncluded.join(' and '))+' here</span>\
                            <span style="display: inline-block; font-size: 14px; font-weight: bold; width: 100%; margin-bottom: 5px">Or, if you prefer...</span>\
                            <button class="btn btn-sm btn-primary">Choose '+((cmsAssetUploadFileTypeIncluded.length > 2) ? cmsAssetUploadFileTypeIncluded.join(', ') : cmsAssetUploadFileTypeIncluded.join(' and '))+' to upload</button>\
                            <span class="cmsAssetUploadMessageLoading" style="display: none; font-size: 14px; font-weight: bold; width: 100%;">Loading...</span>\
                        </div>\
                        \
                    </div>\
                    <div class="cmsAssetUploadSavePath" style="display: none;" data-new-file="" data-save-owner="0" data-save-path="'+cmsInfo["config"]["website"]["path"]+'assets'+((cmsControlSettings['asset_default_dir']!='') ? '/'+cmsControlSettings['asset_default_dir'] : '')+'" data-save-path-ini="'+cmsInfo["config"]["website"]["path"]+'assets'+((cmsControlSettings['asset_default_dir']!='') ? '/'+cmsControlSettings['asset_default_dir'] : '')+'">\
                        <div class="cmsAssetUploadSavePathDir" style="display: inline-block; width: 50%; cursor: pointer" onclick="cmsAssetUpload(this, \''+pId+'\', 10)">Save path: '+cmsInfo["config"]["website"]["path"]+'assets'+((cmsControlSettings['asset_default_dir']!='') ? '/'+cmsControlSettings['asset_default_dir'] : '')+'</div><div class="cmsAssetUploadSavePathFile" style="display: inline-block; width: 50%; text-align: right"></div>\
                    </div>\
                </div>\
                <div id="cmsAssetsBrowser" class="cmsAssetDialogGroup" data-option="1" style="display: none; width: 100%; height: 400px; margin-bottom: 10px">\
                    <div style="display: inline-block; width: 100%; height: 400px;">\
                        <div style="position: relative; display: block; float: left; width: 70%;">\
                            <div style="position: relative; display: block; float: left; width: 100%; height: auto; background-color: #f3f3f3;">\
                                <table class="table cmsAssetTable dt-header">\
                                    <thead>\
                                        <tr>\
                                            <th width="55%">Name</th>\
                                            <th width="25%">Size</th>\
                                            <th width="20%"></th>\
                                        </tr>\
                                    </thead>\
                                </table>\
                            </div>\
                            <div style="position: relative; display: block; float: left; width: 100%; height: 360px; overflow-y: scroll; ; border-bottom: 1px solid #ddd">\
                                <table class="table cmsAssetTable dt-body" width="100%">\
                                    <tbody id="cmsAssetFilesBody">\
                                    </tbody>\
                                </table>\
                            </div>\
                        </div>\
                        <div style="display: block; float: left; width: 30%">\
                            <div style="display: block; margin-left: 5%; width: 95%; height: 400px; border: 1px solid #ddd">\
                                <div id="cmsAssetFilePreview" style="padding: 5%; display: none">\
                                    <div class="cmsAssetFilePreviewImg" style="position: relative; display: inline-block; width: 100%; height: 155px; margin-bottom: 5%; background-color: #ddd; background-repeat: no-repeat; background-size: contain; background-position: center center;"></div>\
                                    <span class="cmsAssetFilePreviewPath" style="-ms-word-break: break-all; word-break: break-all; word-break: break-word;"></span><hr>\
                                    <button class="btn btn-sm btn-default" onclick="cmsAssetUpload(this, \''+pId+'\', 9)">Select</button>\
                                </div>\
                            </div>\
                        </div>\
                    </div>\
                    <div class="cmsAssetListDirPath" style="display: inline-block; width: 100%; margin-top: 10px; margin-bottom: 10px">\
                    </div>\
                </div>\
                <div id="cmsAssetWebURL" class="cmsAssetDialogGroup" data-option="2" style="display: none; width: 100%; height: 400px; margin-bottom: 10px">\
                    <div class="input-group mb-3" style="width: 100%">\
                            <div class="input-group-prepend">\
                                <span class="input-group-text" onclick="" data-img-url-loaded="0">Paste an '+cmsAssetFileTypeCaption+' URL here:</span>\
                            </div>\
                            <input onkeyup="cmsAssetUpload(this, \''+pId+'\', 12)" onkeyblur="cmsAssetUpload(this, \''+pId+'\', 12)" id="cmsAssetWebURLInput" type="text" class="form-control">\
                            '+((cmsAssetUploadFileTypeIncluded.includes('image')) ? '\
                            <span style="display: inline-block; width: 100%; margin-top: 5px; margin-bottom: 10px">If your URL is correct, you\'ll see an image preview here. Large images may take a few minutes to appear.</span>\
                            ' : '')+'\
                    </div>\
                    '+((cmsAssetUploadFileTypeIncluded.includes('image')) ? '\
                    <div class="cmsAssetWebURLPreview" style="position: relative; display: inline-block; width: 100%; height: 380px; background-color: #ddd; background-repeat: no-repeat; background-size: contain; background-position: center center; -webkit-border-radius: 5px 5px 5px 5px; border-radius: 5px 5px 5px 5px;"></div>\
                    ' : '')+'\
                </div>\
            </div>\
                        ',
            onshown: function () {
                cmsAssetEditHistory = [];
                cmsAssetEditHistoryPointer = 0;

                var tOpenFile = $('#'+pId).val().trim();

                if (cmsAssetIsTinyMCE) {
                    if ($(pExt.selection.getNode()).filter('img')[0])
                        tOpenFile = $(pExt.selection.getNode()).filter('img').attr('src');
                    else
                        tOpenFile = '';
                }

                if (cmsAssetIsInput) {
                    if ($('#'+pId).val()!='')
                        tOpenFile = $('#'+pId).val();
                    else
                        tOpenFile = '';
                }

                if (cmsAssetIsCustom) {
                    tOpenFile = pExt;
                }

                if (tOpenFile!='') {
                    if (tOpenFile.indexOf('http')>=0) {
                        $('#cmsAssetWebURLInput').val(tOpenFile);

                        var tIndex = 2;

                        $('#cmsAssetDialogContainer .nav-tabs li').removeClass('active');
                        $('#cmsAssetDialogContainer .nav-tabs li:eq('+tIndex+')').addClass('active');

                        $('#cmsAssetDialogContainer .cmsAssetDialogGroup').hide();
                        $('#cmsAssetDialogContainer .cmsAssetDialogGroup[data-option="'+tIndex+'"]').css('display', 'inline-block');

                        $('#cmsAssetFilePreview').hide();

                        $('#cmsAssetWebURL .cmsAssetWebURLPreview').css('background-image', 'url(\''+$('#cmsAssetWebURLInput').val()+'\')');

                        cmsAssetUpload(this, pId, 12);

                        cmsAssetDialog.getButton('btn-save').show();
                        cmsAssetDialog.getButton('btn-save').html('Insert');
                    } else {
                        if (cmsAssetUploadFileType[cmsFnFileExtension(tOpenFile)]) {
                            if (cmsAssetUploadFileType[cmsFnFileExtension(tOpenFile)]['image']) {
                                $('#cmsAssetUploadBody .cmsAssetUploadMessage span').hide();
                                $('#cmsAssetUploadBody .cmsAssetUploadMessage button').hide();
                                $('#cmsAssetUploadBody .cmsAssetUploadMessageLoading').html('Loading...');
                                $('#cmsAssetUploadBody .cmsAssetUploadMessageLoading').show();
                                $.ajax({
                                    url: tOpenFile,
                                    /*type:'HEAD',*/
                                    error: function()
                                    {
                                        //file not exists
                                        $('#cmsAssetUploadBody .cmsAssetUploadMessage span').show();
                                        $('#cmsAssetUploadBody .cmsAssetUploadMessage button').show();
                                        $('#cmsAssetUploadBody .cmsAssetUploadMessageLoading').hide();
                                        BootstrapDialog.alert(
                                            {
                                                type: BootstrapDialog.TYPE_WARNING,
                                                message: 'File: <strong>'+tOpenFile+'</strong> not found.'
                                            }
                                        );
                                    },
                                    success: function(data)
                                    {
                                        if (data == '') {
                                            //file not exists
                                            $('#cmsAssetUploadBody .cmsAssetUploadMessage span').show();
                                            $('#cmsAssetUploadBody .cmsAssetUploadMessage button').show();
                                            $('#cmsAssetUploadBody .cmsAssetUploadMessageLoading').hide();
                                            BootstrapDialog.alert(
                                                {
                                                    type: BootstrapDialog.TYPE_WARNING,
                                                    message: 'File: <strong>'+tOpenFile+'</strong> not found.'
                                                }
                                            );
                                        } else {
                                            cmsAssetUpload(pObj, pId, 8, tOpenFile);
                                        }
                                    }
                                });
                            } else {
                                cmsAssetUpload(pObj, pId, 8, tOpenFile);
                            }
                        } else {
                            cmsAssetUpload(pObj, pId, 8, tOpenFile);
                        }
                    }
                }

            },
            buttons: [
                {
                    id: 'btn-save',
                    label: 'Insert',
                    action: function(dialog) {
                        var tFuncInsert = function () {
                            if (cmsAssetIsTinyMCE) {
                                if ($(pExt.selection.getNode()).filter('img').attr('cms-data')) {
                                    if ($(pExt.selection.getNode()).filter('img').attr('cms-data') == '1') {
                                        var img = pExt.selection.getNode();
                                        tinymce.activeEditor.dom.setAttrib(img, 'src', $('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path') + '/' + cmsAssetFileInfo['upload_file']);
                                        tinyMCE.activeEditor.undoManager.add();
                                    }
                                } else {
                                    pExt.insertContent('<img src="'+$('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path') + '/' + cmsAssetFileInfo['upload_file']+'" cms-data="1" style="margin-left: 5px; margin-right: 5px">');
                                    //pExt.insertContent('<div style="float: left; margin: 0 10px 0 10px; width: 300px" cms-data="1"><img src="'+$('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path') + '/' + cmsAssetFileInfo['upload_file']+'" width="100%" cms-data="1"></div>');
                                    tinyMCE.activeEditor.undoManager.add();
                                }
                            } else if (cmsAssetIsInput) {
                                $('#' + pId).val($('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path') + '/' + cmsAssetFileInfo['upload_file']);
                                if (typeof(pExt) == 'string') eval(pExt);
                            } else if (cmsAssetIsCustom) {
                                pExt2($('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path') + '/' + cmsAssetFileInfo['upload_file']);
                            } else {
                                $('#' + pId + '_display').val($('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path') + '/' + cmsAssetFileInfo['upload_file']);
                                eval('' + pId + '_add_file();');
                                $('#' + pId).val($('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path') + '/' + cmsAssetFileInfo['upload_file']);
                                $('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path-ini', $('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path'));
                            }

                            cmsAssetDialog.close();
                            $cmsAssetImage = null;
                            $(window).unbind('paste');
                        };

                        if ($('#cmsAssetDialogContainer .nav-tabs li.active').index() == 0) {
                            //UPLOAD

                            if (dialog.getButton('btn-save').html() == 'Save') {

                                var $button = this;
                                $button.disable();
                                $button.spin();

                                var tFunc = function (pDialog) {
                                    var tCmsAssetFileInfo = cmsAssetFileInfo; //JSON.parse(JSON.stringify(cmsAssetFileInfo));

                                    if ($('#cmsAssetUploadBody .cmsAssetUploadImagePreview')[0]) {
                                        var tFuncSaveImage = function () {
                                            $cmsAssetImage.cropper('getCroppedCanvas').toBlob(
                                                function (blob) {
                                                    var form_data = new FormData();
                                                    form_data.append('cmsAssetUploadFile', blob);
                                                    form_data.append('cmsAssetFileInfo', json_encode(tCmsAssetFileInfo));
                                                    form_data.append('cmsAssetSavePath', $('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path'));
                                                    form_data.append('CMS_POST_REQ', $('#' + pId).attr('cms-control-settings'));
                                                    $.ajax(
                                                        {
                                                            xhr: function () {
                                                                var xhr = new window.XMLHttpRequest();
                                                                xhr.upload.addEventListener("progress", function (evt) {
                                                                    if (evt.lengthComputable) {
                                                                        var percentComplete = evt.loaded / evt.total;
                                                                        //Do something with upload progress here
                                                                        var progressCounter = parseInt(percentComplete * 100, 10);
                                                                    }
                                                                }, false);

                                                                xhr.addEventListener("progress", function (evt) {
                                                                    if (evt.lengthComputable) {
                                                                        var percentComplete = evt.loaded / evt.total;
                                                                        //Do something with download progress
                                                                        var progressCounter = parseInt(percentComplete * 100, 10);
                                                                    }
                                                                }, false);

                                                                return xhr;
                                                            },
                                                            url: "",
                                                            cache: false,
                                                            contentType: false,
                                                            processData: false,
                                                            data: form_data,
                                                            type: 'post',
                                                            success: function () {
                                                                //100%
                                                            }
                                                        }
                                                    ).done(
                                                        function (data) {
                                                            //JSON.parse(data);
                                                            cmsAssetFileInfo = tCmsAssetFileInfo;
                                                            //$('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-owner', 1);
                                                            cmsAssetFileInfo['upload_file'] = data;
                                                            $('#cmsAssetUploadBody .cmsAssetUploadSavePath .cmsAssetUploadSavePathFile').html(cmsAssetFileInfo['upload_file']);
                                                            if (pDialog) pDialog.close();

                                                            cmsAssetDialog.getButton('btn-save').enable();
                                                            cmsAssetDialog.getButton('btn-save').stopSpin();

                                                            tFuncInsert();
                                                        }
                                                    );

                                                },
                                                cmsAssetFileInfo['image_info'][2]
                                            );
                                        }

                                        var tArr = cmsControlSettings['img_aspect_ratio'].split(':');
                                        var tAspectRatio = $cmsAssetImage.cropper('getImageData').aspectRatio.toFixed(2); //parseFloat(cmsAssetFileInfo['image_info'][0]/cmsAssetFileInfo['image_info'][1]).toFixed(2);
                                        if (tArr.length == 2) {
                                            tAspectRatioDefault = parseInt(tArr[0],10) / parseInt(tArr[1],10);
                                            //console.log(cmsControlSettings['img_aspect_ratio'].split(':'), tAspectRatio, $cmsAssetImage.cropper('getCropBoxData').left);
                                            if (tAspectRatio != tAspectRatioDefault) {
                                                BootstrapDialog.confirm('The image you want to insert does not match the required aspect ratio. You can crop the image to get the right size.<br><br>Are you sure you want to ignore the image aspect ratio?', function(result){
                                                    if(result) {
                                                        tFuncSaveImage();
                                                    } else {
                                                        cmsAssetDialog.getButton('btn-save').stopSpin();
                                                        cmsAssetDialog.getButton('btn-save').enable();
                                                    }
                                                });
                                            } else {
                                                tFuncSaveImage();
                                            }
                                        } else {
                                            tFuncSaveImage();
                                        }
                                    } else {
                                        var files_data = $('#cmsAssetUploadBody input[type="file"]'); //.prop("files");
                                        var form_data = new FormData();

                                        var tFormUpload = function() {
                                            $.ajax(
                                                {
                                                    xhr: function () {
                                                        var xhr = new window.XMLHttpRequest();
                                                        xhr.upload.addEventListener("progress", function (evt) {
                                                            if (evt.lengthComputable) {
                                                                var percentComplete = evt.loaded / evt.total;
                                                                //Do something with upload progress here
                                                                var progressCounter = parseInt(percentComplete * 100, 10);
                                                            }
                                                        }, false);

                                                        xhr.addEventListener("progress", function (evt) {
                                                            if (evt.lengthComputable) {
                                                                var percentComplete = evt.loaded / evt.total;
                                                                //Do something with download progress
                                                                var progressCounter = parseInt(percentComplete * 100, 10);
                                                            }
                                                        }, false);

                                                        return xhr;
                                                    },
                                                    url: "",
                                                    cache: false,
                                                    contentType: false,
                                                    processData: false,
                                                    data: form_data,
                                                    type: 'post',
                                                    success: function () {
                                                        //100%
                                                    }
                                                }
                                            ).done(
                                                function (data) {
                                                    //JSON.parse(data);
                                                    cmsAssetFileInfo = tCmsAssetFileInfo;
                                                    //$('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-owner', 1);
                                                    cmsAssetFileInfo['upload_file'] = data;
                                                    $('#cmsAssetUploadBody .cmsAssetUploadSavePath .cmsAssetUploadSavePathFile').html(cmsAssetFileInfo['upload_file']);
                                                    if (pDialog) pDialog.close();

                                                    cmsAssetDialog.getButton('btn-save').enable();
                                                    cmsAssetDialog.getButton('btn-save').stopSpin();

                                                    tFuncInsert();
                                                    /*$('#' + pId + '_display').val($('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path') + '/' + cmsAssetFileInfo['upload_file']);
                                                     eval('' + pId + '_add_file();');
                                                     $('#' + pId).val($('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path') + '/' + cmsAssetFileInfo['upload_file']);

                                                     $('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path-ini', $('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path'));

                                                     cmsAssetDialog.close();
                                                     $(window).unbind('paste');*/
                                                }
                                            );
                                        };

                                        form_data.append('cmsAssetFileInfo', json_encode(tCmsAssetFileInfo));
                                        form_data.append('cmsAssetSavePath', $('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path'));
                                        form_data.append('cmsAssetSavePathIni', $('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path-ini'));
                                        form_data.append('CMS_POST_REQ', $('#' + pId).attr('cms-control-settings'));

                                        if (!$('#cmsAssetUploadBody .cmsAssetUploadSavePath .cmsAssetUploadSavePathFile').attr('data-blob-url')) {
                                            $('#cmsAssetUploadBody .cmsAssetUploadSavePath .cmsAssetUploadSavePathFile').attr('data-blob-url', '');
                                        }

                                        if ($('#cmsAssetUploadBody .cmsAssetUploadSavePath .cmsAssetUploadSavePathFile').attr('data-blob-url')!='') {
                                            var xhr = new XMLHttpRequest();
                                            xhr.open('GET', $('#cmsAssetUploadBody .cmsAssetUploadSavePath .cmsAssetUploadSavePathFile').attr('data-blob-url'), true);
                                            xhr.responseType = 'blob';
                                            xhr.onload = function(e) {
                                                if (this.status == 200) {
                                                    form_data.append('cmsAssetUploadFile', this.response);
                                                    tFormUpload();
                                                }
                                            };
                                            xhr.send();
                                        } else {
                                            $(files_data).each(
                                                function (pIndex, pObj) {
                                                    var file_data = $(pObj).prop("files");
                                                    if (file_data && file_data.length) {
                                                        var blob = file_data[0];
                                                        form_data.append('cmsAssetUploadFile', blob);
                                                    }
                                                }
                                            );
                                            tFormUpload();
                                        }
                                    }
                                };

                                var tFuncIni = function () {
                                    if ($('.cmsAssetUploadSavePath').attr('data-save-owner') == '0') {
                                        var form_data = new FormData();
                                        form_data.append('cmsAssetFileCheck', json_encode(cmsAssetFileInfo));
                                        form_data.append('cmsAssetSavePath', $('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path'));
                                        form_data.append('CMS_POST_REQ', $('#' + pId).attr('cms-control-settings'));

                                        $.ajax(
                                            {
                                                type: 'POST',
                                                url: '',
                                                data: form_data,
                                                cache: false,
                                                contentType: false,
                                                processData: false
                                            }
                                        ).done(
                                            function (data) {
                                                var postRet = parseInt(data, 10);

                                                if (postRet == 1) {
                                                    BootstrapDialog.show({
                                                        type: BootstrapDialog.TYPE_INFO,
                                                        title: 'CMS : Assets',
                                                        message: '<strong id="cmsAssetUploadFilenameLabel">' + cmsAssetFileInfo['upload_file'] + '</strong> already exist in ' + $('.cmsAssetUploadSavePath').attr('data-save-path') + '?<br>Enter new filename or click continue to overwrite.<input id="cmsAssetUploadFilename" type="text" class="form-control" placeholder="' + cmsAssetFileInfo['upload_file'] + '">',
                                                        onshown: function () {

                                                        },
                                                        buttons: [
                                                            {
                                                                label: 'Continue',
                                                                action: function (dialog) {
                                                                    if ($('#cmsAssetUploadFilename').val().trim() != '') {
                                                                        var form_data = new FormData();
                                                                        var tCmsAssetFileInfo = cmsAssetFileInfo; //JSON.parse(JSON.stringify(cmsAssetFileInfo));
                                                                        tCmsAssetFileInfo['upload_file'] = $('#cmsAssetUploadFilename').val().trim();

                                                                        form_data.append('cmsAssetFileCheck', json_encode(tCmsAssetFileInfo));
                                                                        form_data.append('CMS_POST_REQ', $('#' + pId).attr('cms-control-settings'));

                                                                        $.ajax(
                                                                            {
                                                                                type: 'POST',
                                                                                url: '',
                                                                                data: form_data,
                                                                                cache: false,
                                                                                contentType: false,
                                                                                processData: false
                                                                            }
                                                                        ).done(
                                                                            function (data) {
                                                                                var postRet = parseInt(data, 10);

                                                                                if (postRet == 1) {
                                                                                    $('#cmsAssetUploadFilenameLabel').html(tCmsAssetFileInfo['upload_file']);
                                                                                    $('#cmsAssetUploadFilename').attr('placeholder', tCmsAssetFileInfo['upload_file']);
                                                                                    $('#cmsAssetUploadFilename').val('');
                                                                                } else {
                                                                                    tFunc(dialog);
                                                                                }
                                                                            }
                                                                        );
                                                                    } else {
                                                                        tFunc(dialog);
                                                                    }
                                                                }
                                                            },
                                                            {
                                                                label: 'Cancel',
                                                                action: function (dialog) {
                                                                    cmsAssetDialog.getButton('btn-save').enable();
                                                                    cmsAssetDialog.getButton('btn-save').stopSpin();
                                                                    dialog.close();
                                                                }
                                                            }
                                                        ]
                                                    });
                                                } else {
                                                    tFunc(null);
                                                }
                                            }
                                        );
                                    } else {
                                        tFunc();
                                    }
                                };

                                if ($('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-new-file')!='') {
                                    BootstrapDialog.show({
                                        type: BootstrapDialog.TYPE_INFO,
                                        title: 'New '+cmsAssetFileTypeCaption,
                                        message: '\
                                                    <div>\
                                                        <label>Filename:</label>\
                                                        <input id="cmsAssetNewItem" type="text" class="form-control">\
                                                    </div>\
                                                ',
                                        onshown: function () {
                                            $('#cmsAssetNewItem').focus();
                                        },
                                        buttons: [{
                                            label: 'Ok',
                                            action: function(dialog) {
                                                if ($('#cmsAssetNewItem').val().trim()=='') {
                                                    $('#cmsAssetNewItem').focus();
                                                    return false;
                                                }

                                                if (!cmsFnValidateFileName($('#cmsAssetNewItem').val().trim())) {
                                                    $('#cmsAssetNewItem').focus();
                                                    if ($('#cmsAssetNewItemInvalid')[0]) $('#cmsAssetNewItemInvalid').remove();
                                                    $('#cmsAssetNewItem').after('<span id="cmsAssetNewItemInvalid" style="display: inline-block; width: 100%; margin-top: 5px">Invalid filename.</span>');
                                                    return false;
                                                }

                                                var tFileType = $('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-new-file');
                                                var tFileName = $('#cmsAssetNewItem').val().trim();
                                                if (!cmsFnFileExtension(tFileName)) {
                                                    tFileName += '.'+cmsAssetUploadMimeType[tFileType];
                                                } else {
                                                    if (tFileName.indexOf(cmsAssetUploadMimeType[tFileType]) == -1) {
                                                        tFileName += '.'+cmsAssetUploadMimeType[tFileType];
                                                    }
                                                }
                                                cmsAssetFileInfo['upload_file'] = tFileName;
                                                $('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-new-file', '');
                                                $('#cmsAssetUploadBody .cmsAssetUploadSavePathFile').html(tFileName);

                                                tFuncIni();

                                                dialog.close();
                                            }
                                        }, {
                                            label: 'Cancel',
                                            action: function(dialog) {
                                                cmsAssetDialog.getButton('btn-save').stopSpin();
                                                cmsAssetDialog.getButton('btn-save').enable();
                                                dialog.close();
                                            }
                                        }],
                                        closable: false
                                    });
                                } else {
                                    tFuncIni();
                                }

                            } else if (dialog.getButton('btn-save').html() == 'Insert') {
                                if ($cmsAssetImage) {
                                    var tArr = cmsControlSettings['img_aspect_ratio'].split(':');
                                    var tAspectRatio = $cmsAssetImage.cropper('getImageData').aspectRatio.toFixed(2); //parseFloat(cmsAssetFileInfo['image_info'][0]/cmsAssetFileInfo['image_info'][1]).toFixed(2);
                                    if (tArr.length == 2) {
                                        tAspectRatioDefault = parseInt(tArr[0],10) / parseInt(tArr[1],10);
                                        if (tAspectRatio != tAspectRatioDefault) {
                                            BootstrapDialog.confirm('The image you want to insert does not match the required aspect ratio. You can crop the image to get the right size.<br><br>Are you sure you want to ignore the image aspect ratio?', function(result){
                                                if(result) {
                                                    tFuncInsert();
                                                }
                                            });
                                        } else {
                                            tFuncInsert();
                                        }
                                    } else {
                                        tFuncInsert();
                                    }
                                } else {
                                    tFuncInsert();
                                }
                            }
                        } else {
                            //WEB ADDRESS URL
                            if ($('#cmsAssetWebURLInput').val().trim()=='') {
                                $('#cmsAssetWebURLInput').focus();
                                return false;
                            }

                            tFuncInsert();

                            $('#' + pId + '_display').val($('#cmsAssetWebURLInput').val().trim());
                            eval('' + pId + '_add_file();');
                            $('#' + pId).val($('#cmsAssetWebURLInput').val().trim());
                            cmsAssetDialog.close();
                            $(window).unbind('paste');
                            $cmsAssetImage = null;
                        }
                    }
                },
                {
                    id: 'btn-cancel',
                    label: 'Cancel',
                    action: function(dialog) {
                        dialog.close();
                        $(window).unbind('paste');
                        $cmsAssetImage = null;
                    }
                }
            ],
            size: BootstrapDialog.SIZE_WIDE,
            closable: false
        });
        cmsAssetDialog.getModalHeader().hide();
        cmsAssetDialog.getButton('btn-save').hide();
    } else if (pMode == 1) {
        //UPLOAD FILE
        var file_data = $(pObj).prop("files");

        if (file_data && file_data.length) {
            var file = file_data[0];

            $('#cmsAssetUploadBody span').hide();
            $('#cmsAssetUploadBody .cmsAssetUploadMessage button').hide();
            $('#cmsAssetUploadBody .cmsAssetUploadFile').hide();
            $('#cmsAssetUploadBody .cmsAssetUploadMessageLoading').html('Uploading...');
            $('#cmsAssetUploadBody .cmsAssetUploadMessageLoading').show();

            if ($('#cmsAssetUploadBody .cmsAssetUploadContainer .cmsAssetUploadImage')[0]) {
                $('#cmsAssetUploadBody .cmsAssetUploadContainer .cmsAssetUploadImage').remove();
            }

            cmsAssetDialog.getButton('btn-save').html('Save');

            if (/^image\/\w+$/.test(file.type)) {
                var uploadedImageURL = URL.createObjectURL(file);
                cmsAssetLoadImage(uploadedImageURL, file.name, file.type);
            } else {
                cmsAssetLoadFile('', file.name, file.type);
            }
        }
    } else if (pMode == 2) {
        //UPLOAD CROPPED IMAGE
        if (pOption == 0) {
            //SHOW CROPPER

            if ($(pObj).attr('data-mode') == '-1') {
                $cmsAssetImage.cropper('crop');
                $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="crop-ok"]').show();
                $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="crop-cancel"]').show();
                $(pObj).blur();
                cmsAssetKeyPress();
                $(pObj).attr('data-mode', '1');
            } else {
                cmsAssetUpload(pObj, pId, 2, 2);
            }
        } else if (pOption == 1) {
            //CROP
            var result = $cmsAssetImage.cropper('getCroppedCanvas', {maxWidth: 4096, maxHeight: 4096, fillColor: "#fff"}); //.toDataURL('image/jpeg')
            result.toBlob(function (blob) {
                var url = URL.createObjectURL(blob);

                $cmsAssetImage.cropper('destroy');
                $('#cmsAssetUploadBody .cmsAssetUploadImagePreview').attr('src', url);
                cmsAssetLoadCropper();

                cmsAssetEditHistory[cmsAssetEditHistory.length] = $('#cmsAssetUploadBody .cmsAssetUploadImagePreview').attr('src');
                cmsAssetEditHistoryPointer = cmsAssetEditHistory.length-1;

                $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="undo"]').removeAttr('disabled');
                $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="redo"]').attr('disabled', 'disabled');

                cmsAssetDialog.getButton('btn-save').html('Save');

            });
            $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="crop"]').attr('data-mode', '-1');
            $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="crop-ok"]').hide();
            $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="crop-cancel"]').hide();
        } else if (pOption == 2) {
            //CLEAR CROP
            $cmsAssetImage.cropper('clear');
            $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="crop"]').attr('data-mode', '-1');
            $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="crop-ok"]').hide();
            $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="crop-cancel"]').hide();
        } else if (pOption == 3) {
            //FLIP HORIZONTAL
            $cmsAssetImage.cropper('scaleX', parseInt($(pObj).attr('data-mode'),10));
            if ($(pObj).attr('data-mode') == '-1')
                $(pObj).attr('data-mode', '1');
            else
                $(pObj).attr('data-mode', '-1');
            $cmsAssetImage.cropper('getCroppedCanvas').toBlob(function (blob) {
                var url = URL.createObjectURL(blob);
                cmsAssetEditHistory[cmsAssetEditHistory.length] = url;
                cmsAssetEditHistoryPointer = cmsAssetEditHistory.length-1;
            });
            $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="undo"]').removeAttr('disabled');
            $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="redo"]').attr('disabled', 'disabled');
            cmsAssetDialog.getButton('btn-save').html('Save');
        } else if (pOption == 4) {
            //FLIP HORIZONTAL
            $cmsAssetImage.cropper('scaleY', parseInt($(pObj).attr('data-mode'),10));
            if ($(pObj).attr('data-mode') == '-1')
                $(pObj).attr('data-mode', '1');
            else
                $(pObj).attr('data-mode', '-1');
            $cmsAssetImage.cropper('getCroppedCanvas').toBlob(function (blob) {
                var url = URL.createObjectURL(blob);
                cmsAssetEditHistory[cmsAssetEditHistory.length] = url;
                cmsAssetEditHistoryPointer = cmsAssetEditHistory.length-1;
            });
            $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="undo"]').removeAttr('disabled');
            $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="redo"]').attr('disabled', 'disabled');
            cmsAssetDialog.getButton('btn-save').html('Save');
        } else if (pOption == 5) {
            //ROTATE 90
            $cmsAssetImage.cropper('rotate', 90);
            $cmsAssetImage.cropper('getCroppedCanvas').toBlob(function (blob) {
                var url = URL.createObjectURL(blob);
                cmsAssetEditHistory[cmsAssetEditHistory.length] = url;
                cmsAssetEditHistoryPointer = cmsAssetEditHistory.length-1;
            });
            $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="undo"]').removeAttr('disabled');
            $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="redo"]').attr('disabled', 'disabled');
            cmsAssetDialog.getButton('btn-save').html('Save');
        } else if (pOption == 6) {
            //ROTATE -90
            $cmsAssetImage.cropper('rotate', -90);
            $cmsAssetImage.cropper('getCroppedCanvas').toBlob(function (blob) {
                var url = URL.createObjectURL(blob);
                cmsAssetEditHistory[cmsAssetEditHistory.length] = url;
                cmsAssetEditHistoryPointer = cmsAssetEditHistory.length-1;
            });
            $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="undo"]').removeAttr('disabled');
            $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="redo"]').attr('disabled', 'disabled');
            cmsAssetDialog.getButton('btn-save').html('Save');
        }
    } else if (pMode == 3) {
        //UNDO

        cmsAssetEditHistoryPointer--;

        $cmsAssetImage.cropper('destroy');
        $('#cmsAssetUploadBody .cmsAssetUploadImagePreview').attr('src', cmsAssetEditHistory[cmsAssetEditHistoryPointer]);
        cmsAssetLoadCropper();

        if (cmsAssetEditHistoryPointer == 0) {
            $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="undo"]').attr('disabled', 'disabled');
        }
        if (cmsAssetEditHistory.length > 0) {
            $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="redo"]').removeAttr('disabled');
        }

        if (cmsAssetEditHistoryPointer == 0) {
            cmsAssetDialog.getButton('btn-save').html('Insert');
        } else if (cmsAssetEditHistory.length > 0) {
            cmsAssetDialog.getButton('btn-save').html('Save');
            $('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-owner', 0);
        }

        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="crop"]').attr('data-mode', '-1');
        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="flip-horizontal"]').attr('data-mode', '-1');
        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="flip-vertical"]').attr('data-mode', '-1');
        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="crop-ok"]').hide();
        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="crop-cancel"]').hide();
    } else if (pMode == 4) {
        //REDO

        cmsAssetEditHistoryPointer++;

        $cmsAssetImage.cropper('destroy');
        $('#cmsAssetUploadBody .cmsAssetUploadImagePreview').attr('src', cmsAssetEditHistory[cmsAssetEditHistoryPointer]);
        cmsAssetLoadCropper();

        if (cmsAssetEditHistoryPointer >= (cmsAssetEditHistory.length-1)) {
            $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="redo"]').attr('disabled', 'disabled');
        }
        if (cmsAssetEditHistory.length > 0) {
            $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="undo"]').removeAttr('disabled');
        }

        if (cmsAssetEditHistoryPointer >= (cmsAssetEditHistory.length-1)) {
            cmsAssetDialog.getButton('btn-save').html('Save');
            $('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-owner', 0);
        } else if (cmsAssetEditHistory.length > 0) {
            cmsAssetDialog.getButton('btn-save').html('Insert');
        }

        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="crop"]').attr('data-mode', '-1');
        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="flip-horizontal"]').attr('data-mode', '-1');
        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="flip-vertical"]').attr('data-mode', '-1');
        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="crop-ok"]').hide();
        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="crop-cancel"]').hide();
    } else if (pMode == 5) {
        //IMPORT FILE

        $('#cmsAssetUploadBody .cmsAssetUploadImagePreviewContainer').hide();
        if ($cmsAssetImage) $cmsAssetImage.cropper('destroy');
        $('#cmsAssetUploadBody .cmsAssetUploadImagePreviewContainer').remove();
        $('#cmsAssetUploadBody .cmsAssetUploadMessageLoading').html('Loading...');
        $('#cmsAssetUploadBody .cmsAssetUploadMessageLoading').show();

        cmsAssetEditHistory = [];
        cmsAssetEditHistoryPointer = 0;

        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="crop"]').attr('data-mode', '-1');
        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="flip-horizontal"]').attr('data-mode', '-1');
        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="flip-vertical"]').attr('data-mode', '-1');
        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="crop-ok"]').hide();
        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="crop-cancel"]').hide();
        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="undo"]').attr('disabled', 'disabled');
        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="redo"]').attr('disabled', 'disabled');

        $('#cmsAssetUploadBody .cmsAssetUploadFile').remove();

        $('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-owner', 0);

        cmsAssetDialog.getButton('btn-save').html('Save');

        cmsAssetUpload(pObj, pId, 1);
    } else if (pMode == 6) {
        //MENU
        var tIndex = $(pObj).parent().index();

        $('#cmsAssetDialogContainer .nav-tabs li a').removeClass('active');
        $('#cmsAssetDialogContainer .nav-tabs li:eq('+tIndex+') a').addClass('active');

        $('#cmsAssetDialogContainer .nav-tabs li').removeClass('active');
        $('#cmsAssetDialogContainer .nav-tabs li:eq('+tIndex+')').addClass('active');

        $('#cmsAssetDialogContainer .cmsAssetDialogGroup').hide();
        $('#cmsAssetDialogContainer .cmsAssetDialogGroup[data-option="'+tIndex+'"]').css('display', 'inline-block');

        $('#cmsAssetFilePreview').hide();

        if (tIndex == 0) {
            //UPLOAD

            //COPY & PASTE SUPPORT
            cmsAssetPaste();

            cmsAssetDialog.getButton('btn-save').show();
        } else if (tIndex == 1) {
            //ASSETS

            $(window).unbind('paste');

            $('.cmsAssetTable.dt-header').css('width', $('.cmsAssetTable.dt-body').width()+'px');

            var form_data = new FormData();
            form_data.append('cmsAssetListDirFiles', cmsControlSettings['asset_default_dir']);
            form_data.append('CMS_POST_REQ', $('#'+pId).attr('cms-control-settings'));
            $.ajax(
                {
                    type: 'POST',
                    url: '',
                    data: form_data,
                    cache: false,
                    contentType: false,
                    processData: false
                }
            ).done(
                function (data) {
                    $('#cmsAssetFilesBody').empty();
                    $('#cmsAssetFilesBody').append($(data).filter('.cmsAssetListDirFiles').val());
                    $('#cmsAssetsBrowser .cmsAssetListDirPath').html('Path: '+$(data).filter('.cmsAssetListDirPath').val());
                }
            );

            cmsAssetDialog.getButton('btn-save').hide();
        } else if (tIndex == 2) {
            //WEB URL

            $(window).unbind('paste');

            cmsAssetDialog.getButton('btn-save').show();
        }
    } else if (pMode == 7) {
        //ASSET BROWSE

        if (pOption == 0) {
            //DIR CHANGE
            var form_data = new FormData();
            form_data.append('cmsAssetListDirFiles', pExt);
            form_data.append('CMS_POST_REQ', $('#'+pId).attr('cms-control-settings'));
            $.ajax(
                {
                    type: 'POST',
                    url: '',
                    data: form_data,
                    cache: false,
                    contentType: false,
                    processData: false
                }
            ).done(
                function (data) {
                    $('#cmsAssetFilesBody').empty();
                    $('#cmsAssetFilesBody').append($(data).filter('.cmsAssetListDirFiles').val());
                    $('#cmsAssetsBrowser .cmsAssetListDirPath').html('Path: '+$(data).filter('.cmsAssetListDirPath').val());
                }
            );
        } else if (pOption == 1) {
            //SELECT
            var tDir = cmsFnDirName($(pObj).parents('tr').attr('data-path'));
            var tExt = cmsFnFileExtension($(pObj).parents('tr').attr('data-name'));
            if ($(pObj).parents('tr').attr('data-image') == '1') {
                $('#cmsAssetFilePreview .cmsAssetFilePreviewImg').css('background-image', 'url(\''+cmsInfo["config"]["website"]["path"]+'assets/'+cmsFnDirName($(pObj).parents('tr').attr('data-path'))+'/.cms.'+cmsFnBaseName($(pObj).parents('tr').attr('data-path'))+'\')');
                $('#cmsAssetFilePreview .cmsAssetFilePreviewPath').html(cmsInfo["config"]["website"]["path"]+'assets'+((tDir!='') ? '/'+tDir : tDir)+'/'+cmsFnBaseName($(pObj).parents('tr').attr('data-path')));
                $('#cmsAssetFilePreview').show();
            } else {
                $('#cmsAssetFilePreview .cmsAssetFilePreviewImg').empty();
                $('#cmsAssetFilePreview .cmsAssetFilePreviewImg').append('<i class="'+cmsAssetUploadFileType[tExt]['icon']+'" aria-hidden="true" style="position: absolute; width: 54px; height: 54px; top: 0; left: 0; right: 0; bottom: 0px; margin: auto; font-size: 54px"></i>');
                $('#cmsAssetFilePreview .cmsAssetFilePreviewPath').html(cmsInfo["config"]["website"]["path"]+'assets'+((tDir!='') ? '/'+tDir : tDir)+'/'+cmsFnBaseName($(pObj).parents('tr').attr('data-path')));
                $('#cmsAssetFilePreview').show();
            }
        } else if (pOption == 2) {
            //RENAME
            BootstrapDialog.show({
                type: BootstrapDialog.TYPE_INFO,
                title: 'CMS : Assets',
                message: '\
                            <div>\
                                <label>Rename '+$(pObj).parents('tr').attr('data-type')+'</label>\
                                <input id="cmsAssetNewItem" type="text" class="form-control">\
                            </div>\
                        ',
                onshown: function () {
                    $('#cmsAssetNewItem').val($(pObj).parents('tr').attr('data-name'));
                },
                buttons: [{
                    label: 'Ok',
                    action: function(dialog) {
                        if ($('#cmsAssetNewItem').val().trim() == '') {
                            $('#cmsAssetNewItem').focus();
                            return false;
                        }

                        if (!cmsFnValidateFileName($('#cmsAssetNewItem').val().trim())) {
                            $('#cmsAssetNewItem').focus();
                            if ($('#cmsAssetNewItemInvalid')[0]) $('#cmsAssetNewItemInvalid').remove();
                            $('#cmsAssetNewItem').after('<span id="cmsAssetNewItemInvalid" style="display: inline-block; width: 100%; margin-top: 5px">Invalid filename.</span>');
                            return false;
                        }

                        var form_data = new FormData();
                        form_data.append('cmsAssetListDirFiles', $(pObj).attr('data-dir'));

                        var tArr = [$(pObj).parents('tr').attr('data-name'), $('#cmsAssetNewItem').val().trim()];

                        form_data.append('cmsAssetRename', base64_encode(json_encode(tArr)));
                        form_data.append('CMS_POST_REQ', $('#'+pId).attr('cms-control-settings'));
                        $.ajax(
                            {
                                type: 'POST',
                                url: '',
                                data: form_data,
                                cache: false,
                                contentType: false,
                                processData: false
                            }
                        ).done(
                            function (data) {
                                $('#cmsAssetFilesBody').empty();
                                $('#cmsAssetFilesBody').append($(data).filter('.cmsAssetListDirFiles').val());
                                $('#cmsAssetsBrowser .cmsAssetListDirPath').html('Path: '+$(data).filter('.cmsAssetListDirPath').val());

                                if ($(data).filter('.cmsAssetError')[0]) {
                                    BootstrapDialog.alert($(data).filter('.cmsAssetError').val());
                                } else {
                                    dialog.close();
                                }
                            }
                        );
                    }
                }, {
                    label: 'Cancel',
                    action: function(dialog) {
                        dialog.close();
                    }
                }],
                closable: false
            });

        } else if (pOption == 3) {
            //DELETE
            BootstrapDialog.confirm('Are you sure you want to delete this <strong>'+$(pObj).attr('data-name')+'</strong> '+$(pObj).attr('data-type')+'?'+(($(pObj).attr('data-type')=='folder') ? '<br>WARNING: All files will be deleted from the folder <strong>'+$(pObj).attr('data-name')+'</strong>' : ''),
                function(result){
                    if(result) {
                        var form_data = new FormData();
                        form_data.append('cmsAssetListDirFiles', $(pObj).attr('data-dir'));
                        form_data.append('cmsAssetDelete', pExt);
                        form_data.append('CMS_POST_REQ', $('#'+pId).attr('cms-control-settings'));
                        $.ajax(
                            {
                                type: 'POST',
                                url: '',
                                data: form_data,
                                cache: false,
                                contentType: false,
                                processData: false
                            }
                        ).done(
                            function (data) {
                                $('#cmsAssetFilesBody').empty();
                                $('#cmsAssetFilesBody').append($(data).filter('.cmsAssetListDirFiles').val());
                                $('#cmsAssetsBrowser .cmsAssetListDirPath').html('Path: '+$(data).filter('.cmsAssetListDirPath').val());
                            }
                        );
                    }
                }
            );
        }
    } else if (pMode == 8) {
        //LOAD URL
        $('#cmsAssetUploadBody .cmsAssetUploadImagePreviewContainer').hide();
        if ($cmsAssetImage) $cmsAssetImage.cropper('destroy');
        $('#cmsAssetUploadBody .cmsAssetUploadImagePreviewContainer').remove();
        $('#cmsAssetUploadBody .cmsAssetUploadMessageLoading').html('Loading...');
        $('#cmsAssetUploadBody .cmsAssetUploadMessageLoading').show();

        cmsAssetEditHistory = [];
        cmsAssetEditHistoryPointer = 0;

        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="crop-ok"]').hide();
        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="crop-cancel"]').hide();
        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="undo"]').attr('disabled', 'disabled');
        $('#cmsAssetUploadBody .cmsAssetUploadToolbar button[data-type="redo"]').attr('disabled', 'disabled');

        $('#cmsAssetUploadBody span').hide();
        $('#cmsAssetUploadBody .cmsAssetUploadMessage button').hide();
        $('#cmsAssetUploadBody .cmsAssetUploadFile').hide();
        $('#cmsAssetUploadBody .cmsAssetUploadMessageLoading').html('Loading...');
        $('#cmsAssetUploadBody .cmsAssetUploadMessageLoading').show();

        if ($('#cmsAssetUploadBody .cmsAssetUploadContainer .cmsAssetUploadImage')[0]) {
            $('#cmsAssetUploadBody .cmsAssetUploadContainer .cmsAssetUploadImage').remove();
        }

        var tGetKeyByValue = function (paramObj, paramVal) {
            for( var prop in paramObj ) {
                if( paramObj.hasOwnProperty( prop ) ) {
                    if( paramObj[ prop ] === paramVal )
                        return prop;
                }
            }
        }

        if (cmsAssetUploadFileType[cmsFnFileExtension(pOption)]) {
            if (cmsAssetUploadFileType[cmsFnFileExtension(pOption)]['type'] == 'image') {
                var xhr = new XMLHttpRequest();
                xhr.open("GET", pOption);
                xhr.responseType = "blob";
                xhr.onload = function (e) {
                    var urlCreator = window.URL || window.webkitURL;
                    var xhrURL = urlCreator.createObjectURL(this.response);
                    var tFileName = (!pExt) ? cmsFnBaseName(pOption) : pExt[1];
                    var tSavePath = (!pExt) ? cmsFnDirName(pOption) : pExt[0];

                    cmsAssetLoadImage(xhrURL, tFileName, e.currentTarget.response.type);

                    $('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path', tSavePath);
                    $('#cmsAssetUploadBody .cmsAssetUploadSavePath .cmsAssetUploadSavePathDir').html('Save path: '+tSavePath);
                    $('#cmsAssetUploadBody .cmsAssetUploadSavePath .cmsAssetUploadSavePathFile').attr('data-blob-url', xhrURL);

                    cmsAssetDialog.getButton('btn-save').html('Insert');

                    if (pExt) {
                        pExt[2].close();
                        cmsAssetDialog.getButton('btn-save').html('Save');
                    }
                };
                xhr.send();
            } else {
                var tFileName = (!pExt) ? cmsFnBaseName(pOption) : pExt[1];
                var tSavePath = (!pExt) ? cmsFnDirName(pOption) : pExt[0];
                var tFileType = (tGetKeyByValue(cmsAssetUploadMimeType, cmsFnFileExtension(pOption)));
                cmsAssetLoadFile(pOption, tFileName, tFileType);

                $('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path', tSavePath);
                $('#cmsAssetUploadBody .cmsAssetUploadSavePath .cmsAssetUploadSavePathDir').html('Save path: '+tSavePath);
                $('#cmsAssetUploadBody .cmsAssetUploadSavePath .cmsAssetUploadSavePathFile').attr('data-blob-url', '');

                cmsAssetDialog.getButton('btn-save').html('Insert');

                if (pExt) {
                    pExt[2].close();
                    cmsAssetDialog.getButton('btn-save').html('Save');
                }
            }
        } else {
            var tFileName = (!pExt) ? cmsFnBaseName(pOption) : pExt[1];
            var tSavePath = (!pExt) ? cmsFnDirName(pOption) : pExt[0];
            var tFileType = (tGetKeyByValue(cmsAssetUploadMimeType, cmsFnFileExtension(pOption)));
            cmsAssetLoadFile(pOption, tFileName, tFileType);

            $('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path', tSavePath);
            $('#cmsAssetUploadBody .cmsAssetUploadSavePath .cmsAssetUploadSavePathDir').html('Save path: '+tSavePath);
            $('#cmsAssetUploadBody .cmsAssetUploadSavePath .cmsAssetUploadSavePathFile').attr('data-blob-url', '');

            cmsAssetDialog.getButton('btn-save').html('Insert');

            if (pExt) {
                pExt[2].close();
                cmsAssetDialog.getButton('btn-save').html('Save');
            }
        }
    } else if (pMode == 9) {
        //SELECT AND LOAD URL
        var tIndex = 0;

        $('#cmsAssetDialogContainer .nav-tabs li').removeClass('active');
        $('#cmsAssetDialogContainer .nav-tabs li:eq('+tIndex+')').addClass('active');

        $('#cmsAssetDialogContainer .cmsAssetDialogGroup').hide();
        $('#cmsAssetDialogContainer .cmsAssetDialogGroup[data-option="'+tIndex+'"]').css('display', 'inline-block');

        cmsAssetUpload(pObj, pId, 8, $('#cmsAssetFilePreview .cmsAssetFilePreviewPath').html());
    } else if (pMode == 10) {
        //CHANGE SAVE LOCATION

        BootstrapDialog.show({
            type: BootstrapDialog.TYPE_INFO,
            title: 'CMS : Assets',
            message: '\
                        <div>\
                            <div>Save path: <a href="javascript:void(0)" data-path="" onclick="cmsAssetUpload(this, \''+pId+'\', 11)">'+cmsInfo["config"]["website"]["path"]+'assets</a><span id="cmsAssetBrowseFoldersPath"></span></div>\
                            <hr>\
                            <div id="cmsAssetBrowseFolders">\
                                <div class="dvFoldersLoading">Loading, please wait.</div>\
                            </div>\
                        </div>\
                            ',
            onshown: function () {
                var tSavePath = $('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path');
                if (tSavePath.substr(0, (cmsInfo["config"]["website"]["path"]+'assets').length) == cmsInfo["config"]["website"]["path"]+'assets') {
                    tSavePath = tSavePath.substr((cmsInfo["config"]["website"]["path"]+'assets').length);
                    if (tSavePath.substr(0, 1) == '/') {
                        tSavePath = tSavePath.substr(1);
                    }
                }

                var tArr = tSavePath.split('/');

                var tDir = [];
                $.each(tArr,
                    function (pIndex, pData) {
                        var tArrSlice = tSavePath.split('/');
                        tArrSlice = tArrSlice.slice(0, pIndex);
                        var tPath = ((tArrSlice.length>0) ? tArrSlice.join('/')+'/' : '')+pData;

                        tDir[tDir.length] = '<a href="javascript:void(0)" data-path="'+tPath+'" onclick="cmsAssetUpload(this, \''+pId+'\', 11)">'+pData+'</a>'
                    }
                );

                $('#cmsAssetBrowseFoldersPath').html('/'+tDir.join('/'));

                var form_data = new FormData();
                form_data.append('cmsAssetBrowseFolder', tSavePath);
                form_data.append('CMS_POST_REQ', $('#'+pId).attr('cms-control-settings'));
                $.ajax(
                    {
                        type: 'POST',
                        url: '',
                        data: form_data,
                        cache: false,
                        contentType: false,
                        processData: false
                    }
                ).done(
                    function (data) {
                        $('#cmsAssetBrowseFolders').empty();
                        $('#cmsAssetBrowseFolders').append(data);
                    }
                );
            },
            buttons: [
                {
                    id: 'button-ok',
                    label: 'Ok',
                    action: function(dialog) {
                        var tDes = $('#cmsAssetBrowseFoldersPath').text();
                        if ($('#cmsAssetBrowseFoldersPath').text().substring(0,1)=='/') {
                            tDes = $('#cmsAssetBrowseFoldersPath').text().substring(1,$('#cmsAssetBrowseFoldersPath').text().length);
                        }

                        var tPath = cmsInfo["config"]["website"]["path"]+'assets'+((tDes!='') ? '/'+tDes : '');
                        $('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path', tPath);
                        $('#cmsAssetUploadBody .cmsAssetUploadSavePathDir').html('Save path: '+tPath);
                        $('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-owner', 0);
                        cmsAssetDialog.getButton('btn-save').html('Save');

                        dialog.close();
                    }
                },
                {
                    id: 'button-cancel',
                    label: 'Cancel',
                    action: function(dialog) {
                        dialog.close();
                    }
                }
            ],
            closable: false
        });
    } else if (pMode == 11) {
        //BROWSE FOLDER

        var tArr = $(pObj).attr('data-path').split('/');

        var tDir = [];
        $.each(tArr,
            function (pIndex, pData) {
                var tArrSlice = $(pObj).attr('data-path').split('/');
                tArrSlice = tArrSlice.slice(0, pIndex);
                var tPath = ((tArrSlice.length>0) ? tArrSlice.join('/')+'/' : '')+pData;

                tDir[tDir.length] = '<a href="javascript:void(0)" data-path="'+tPath+'" onclick="cmsAssetUpload(this, \''+pId+'\', 11)">'+pData+'</a>'
            }
        );

        $('#cmsAssetBrowseFoldersPath').html('/'+tDir.join('/'));

        var form_data = new FormData();
        form_data.append('cmsAssetBrowseFolder', $(pObj).attr('data-path'));
        form_data.append('CMS_POST_REQ', $('#'+pId).attr('cms-control-settings'));
        $.ajax(
            {
                type: 'POST',
                url: '',
                data: form_data,
                cache: false,
                contentType: false,
                processData: false
            }
        ).done(
            function (data) {
                $('#cmsAssetBrowseFolders').empty();
                $('#cmsAssetBrowseFolders').append(data);
            }
        );
    } else if (pMode == 12) {
        //WEB URL ON KEY CHANGE
        $('#cmsAssetWebURL .input-group-text').html('Loading...');
        $('#cmsAssetWebURL .input-group-text').attr('data-img-url-loaded', 0);
        $('#cmsAssetWebURL .input-group-text').unbind('click');

        var tImg = new Image();
        tImg.onload = function (e) {
            $('#cmsAssetWebURL .input-group-text').html('<i class="fa fa-upload" aria-hidden="true"></i>');
            $('#cmsAssetWebURL .input-group-text').css('cursor', 'pointer');
            $('#cmsAssetWebURL .input-group-text').unbind('click');
            $('#cmsAssetWebURL .input-group-text').on('click',
                function () {
                    cmsAssetUpload(this, pId, 14);
                }
            );
            $('#cmsAssetWebURL .input-group-text').attr('data-img-url-loaded', 1);
        }
        tImg.onerror = function (e) {
            //$('#cmsAssetWebURL .input-group-text').html('Paste an '+cmsAssetFileTypeCaption+' URL here:');
            $('#cmsAssetWebURL .input-group-text').html('<i class="fa fa-upload" aria-hidden="true"></i>');
            $('#cmsAssetWebURL .input-group-text').css('cursor', 'pointer');
            $('#cmsAssetWebURL .input-group-text').unbind('click');
            $('#cmsAssetWebURL .input-group-text').on('click',
                function () {
                    cmsAssetUpload(this, pId, 14);
                }
            );
            $('#cmsAssetWebURL .input-group-text').attr('data-img-url-loaded', 1);
        }
        tImg.src = $('#cmsAssetWebURLInput').val();

        $('#cmsAssetWebURL .cmsAssetWebURLPreview').css('background-image', 'url(\''+$('#cmsAssetWebURLInput').val()+'\')');
    } else if (pMode == 14) {
        //IMPORT FROM WEB URL
        var tName = cmsFnBaseName($('#cmsAssetWebURLInput').val());

        BootstrapDialog.show({
            type: BootstrapDialog.TYPE_INFO,
            title: 'Import '+cmsAssetFileTypeCaption,
            message: '\
                            <div>\
                                <label>Filename:</label>\
                                <input id="cmsAssetNewItem" type="text" class="form-control">\
                            </div>\
                        ',
            onshown: function () {
                $('#cmsAssetNewItem').val(decodeURIComponent(tName));
            },
            buttons: [{
                label: 'Ok',
                action: function(dialog) {
                    if ($('#cmsAssetNewItem').val().trim() == '') {
                        $('#cmsAssetNewItem').focus();
                        return false;
                    }

                    if (!cmsFnValidateFileName($('#cmsAssetNewItem').val().trim())) {
                        $('#cmsAssetNewItem').focus();
                        if ($('#cmsAssetNewItemInvalid')[0]) $('#cmsAssetNewItemInvalid').remove();
                        $('#cmsAssetNewItem').after('<span id="cmsAssetNewItemInvalid" style="display: inline-block; width: 100%; margin-top: 5px">Invalid filename.</span>');
                        return false;
                    }

                    var $button = this;
                    $button.disable();
                    $button.spin();

                    var form_data = new FormData();
                    form_data.append('cmsAssetWebURL', $('#cmsAssetWebURLInput').val());
                    form_data.append('CMS_POST_REQ', $('#'+pId).attr('cms-control-settings'));
                    $.ajax(
                        {
                            type: 'POST',
                            url: '',
                            data: form_data,
                            cache: false,
                            contentType: false,
                            processData: false
                        }
                    ).done(
                        function (data) {
                            var tIndex = 0;
                            $('#cmsAssetDialogContainer .nav-tabs li').removeClass('active');
                            $('#cmsAssetDialogContainer .nav-tabs li:eq('+tIndex+')').addClass('active');
                            $('#cmsAssetDialogContainer .cmsAssetDialogGroup').hide();
                            $('#cmsAssetDialogContainer .cmsAssetDialogGroup[data-option="'+tIndex+'"]').css('display', 'inline-block');

                            var tFileExt = cmsFnFileExtension(data);
                            var tFileName = $('#cmsAssetNewItem').val().trim();
                            if (!cmsFnFileExtension(tFileName)) {
                                tFileName += '.'+tFileExt;
                            } else {
                                if (tFileName.indexOf(tFileExt) == -1) {
                                    tFileName += '.'+tFileExt;
                                }
                            }

                            cmsAssetUpload(pObj, pId, 8, data,
                                [
                                    cmsInfo["config"]["website"]["path"]+'assets'+((cmsControlSettings['asset_default_dir']!='') ? '/'+cmsControlSettings['asset_default_dir'] : ''),
                                    tFileName,
                                    dialog,
                                    data
                                ]
                            );
                        }
                    );
                }
            }, {
                label: 'Cancel',
                action: function(dialog) {
                    dialog.close();
                }
            }],
            closable: false
        });
    }
}


var $cmsUploadImage = null;
var cmsUploadEditHistory = [];
var cmsUploadEditHistoryPointer = 0;
var cmsUploadFileInfo = {};
var cmsUploadDialog = null;
function cmsUploadUpload(pObj, pId, pMode, pOption, pExt, pExt2) {
    pOption = (typeof(pOption) != 'undefined') ? pOption : null;
    pExt = (typeof(pExt) != 'undefined') ? pExt : null;
    pExt2 = (typeof(pExt2) != 'undefined') ? pExt2 : null;

    var cmsControlSettings = json_decode(base64_decode($('#'+pId).attr('cms-control-settings'))); //console.log(cmsControlSettings);
    if (cmsControlSettings['repeaterId']) {
        if (cmsControlSettings['repeaterId']!='') {
            cmsControlSettings['id'] = pId;
            $('#'+pId).attr('cms-control-settings', base64_encode(json_encode(cmsControlSettings)));
        }
    }

    var cmsUploadUploadFileType = {
        'jpg': {type: 'image', name: '', icon: ''},
        'gif': {type: 'image', name: '', icon: ''},
        'jpeg': {type: 'image', name: '', icon: ''},
        'png': {type: 'image', name: '', icon: ''},
        'bmp': {type: 'image', name: '', icon: ''},
        'tiff': {type: 'image', name: '', icon: ''},
        'pdf': {type: 'document', name: 'PDF File', icon: 'fa fa-file-pdf-o'},
        'xls': {type: 'document', name: 'Excel File', icon: 'fa fa-file-excel-o'},
        'xlsx': {type: 'document', name: 'Excel File', icon: 'fa fa-file-excel-o'},
        'xlsm': {type: 'document', name: 'Excel File', icon: 'fa fa-file-excel-o'},
        'doc': {type: 'document', name: 'Word Doc File', icon: 'fa fa-file-word-o'},
        'docx': {type: 'document', name: 'Word Doc File', icon: 'fa fa-file-word-o'},
        'ppt': {type: 'document', name: 'Power Point File', icon: 'fa-file-powerpoint-o'},
        'pptx': {type: 'document', name: 'Power Point File', icon: 'fa-file-powerpoint-o'}
    }

    var cmsUploadUploadMimeType = {
        'image/jpeg': 'jpg',
        'image/gif': 'gif',
        'image/png': 'png',
        'image/tiff': 'tif',
        'image/x-icon': 'ico',
        'application/pdf': 'pdf',
        'application/msword': 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'docx',
        'application/vnd.ms-excel': 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'xlxs',
        'application/vnd.ms-excel.sheet.macroEnabled.12': 'xlsm',
        'application/vnd.ms-powerpoint': 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation': 'pptx',
        'text/plain': 'txt',
        'video/mpeg': 'mpeg',
        'audio/x-wav': 'wav',
        'video/webm': 'webm',
        'audio/webm': 'weba',
        'video/3gpp': '3gp',
        'audio/3gpp': '3gp',
        'video/3gpp2': '3g2',
        'audio/3gpp2': '3g2',
        'audio/ogg': 'oga',
        'video/ogg': 'ogv',
        'audio/midi': 'mid',
        'audio/aac': 'aac',
        'video/x-msvideo': 'avi'
    };

    var cmsUploadUploadAcceptFile = cmsControlSettings['accept'].replace(/\./g,'').split(',');

    var cmsUploadUploadFileTypeIncluded = [];
    $.each(cmsUploadUploadAcceptFile,
        function (pIndex, pExt) {
            if (cmsUploadUploadFileType[pExt]) {
                if (!cmsUploadUploadFileTypeIncluded.includes(cmsUploadUploadFileType[pExt]['type'])) {
                    if (cmsUploadUploadAcceptFile.length > 1) {
                        cmsUploadUploadFileTypeIncluded[cmsUploadUploadFileTypeIncluded.length] = cmsUploadUploadFileType[pExt]['type'];
                    } else {
                        cmsUploadUploadFileTypeIncluded[cmsUploadUploadFileTypeIncluded.length] = cmsUploadUploadFileType[pExt]['name'];
                    }
                }
            }
        }
    );

    var cmsUploadLoadCropper = function () {
        var tArr = cmsControlSettings['img_aspect_ratio'].split(':');

        var tAspectRatio = cmsUploadFileInfo['image_info'][0]/cmsUploadFileInfo['image_info'][1];
        if (tArr.length == 2) {
            tAspectRatio = parseInt(tArr[0],10) / parseInt(tArr[1],10);
        } else {
            if (tArr[0].toLowerCase() == 'free') {
                tAspectRatio = NaN;
            }
        }

        $cmsUploadImage.cropper({
            aspectRatio: tAspectRatio,
            crop: function(event) {
            }
        });
        // Get the Cropper.js instance after initialized
        var cropper = $cmsUploadImage.data('cropper');
    };

    var cmsUploadLoadImage = function (pImageURL, pFileName, pFileType) {
        $('#cmsUploadUploadBody .cmsUploadUploadContainer').append('<img class="cmsUploadUploadImage" xsrc="'+pImageURL+'" style="position: absolute; top: -10000px; left: -10000px;">');
        $('#cmsUploadUploadBody .cmsUploadUploadImage').unbind('load');
        $('#cmsUploadUploadBody .cmsUploadUploadImage').on('load',
            function () {
                cmsUploadFileInfo['upload_file'] = pFileName;
                cmsUploadFileInfo['upload_file_type'] = pFileType;
                cmsUploadFileInfo['image_info'] = [$('#cmsUploadUploadBody .cmsUploadUploadImage')[0].width, $('#cmsUploadUploadBody .cmsUploadUploadImage')[0].height, pFileType];

                $('#cmsUploadUploadBody .cmsUploadUploadMessageLoading').hide();
                $('#cmsUploadUploadBody .cmsUploadUploadContainer').append(
                    '\
                    <div class="cmsUploadUploadImagePreviewContainer" style="position: absolute; width: 100%; height: 380px; top: 0; left: 0; right: 0; bottom: 0; margin: auto;">\
                        <img class="cmsUploadUploadImagePreview" src="'+pImageURL+'" style="max-width: 100%;">\
                    </div>\
                    '
                );
                $('#cmsUploadUploadBody .cmsUploadUploadImagePreviewContainer').width($('#cmsUploadUploadBody .cmsUploadUploadImagePreview').width());

                $cmsUploadImage = $('#cmsUploadUploadBody .cmsUploadUploadImagePreview');

                cmsUploadLoadCropper();

                $('#cmsUploadUploadBody .cmsUploadUploadImagePreview')[0].addEventListener('ready', function () {
                        /*var tCanvasData = $cmsUploadImage.cropper('getCanvasData');
                         var tContainerData =  $cmsUploadImage.cropper('getContainerData');
                         $cmsUploadImage.cropper('setCropBoxData', {"width":tContainerData['width'],"height":tContainerData['height']});
                         $cmsUploadImage.cropper('setCropBoxData', {"left":tCanvasData['left'],"top":tCanvasData['top']});*/
                        $cmsUploadImage.cropper('clear');
                    }
                );

                $('#cmsUploadUploadBody .cmsUploadUploadImagePreview')[0].addEventListener('cropstart', function (event) {
                        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="crop-ok"]').show();
                        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="crop-cancel"]').show();
                        cmsUploadKeyPress();
                    }
                );

                $('#cmsUploadUploadBody .cmsUploadUploadToolbar').show();
                $('#cmsUploadUploadBody .cmsUploadUploadSavePath').show();
                $('#cmsUploadUploadBody .cmsUploadUploadSavePath .cmsUploadUploadSavePathFile').html(cmsUploadFileInfo['upload_file']);

                if (pImageURL!='') {
                    if (!pOption) {
                        cmsUploadDialog.getButton('btn-save').show();
                    } else {
                        if (pOption.indexOf('/temp/') < 0) {
                            cmsUploadDialog.getButton('btn-save').hide();
                        } else {
                            cmsUploadDialog.getButton('btn-save').show();
                        }
                    }
                } else {
                    cmsUploadDialog.getButton('btn-save').hide();
                }

                cmsUploadEditHistory[cmsUploadEditHistory.length] = $('#cmsUploadUploadBody .cmsUploadUploadImagePreview').attr('src');
            }
        );
        $('#cmsUploadUploadBody .cmsUploadUploadImage').attr('src', $('#cmsUploadUploadBody .cmsUploadUploadImage').attr('xsrc'));
    }

    var cmsUploadLoadFile = function (pXhrURL, pFileName, pFileType) {
        cmsUploadFileInfo['upload_file'] = pFileName;
        cmsUploadFileInfo['upload_file_type'] = pFileType;
        cmsUploadFileInfo['image_info'] = [0, 0, pFileType];

        //console.log(pFileName);
        //console.log(cmsFnFileExtension(pFileName).toLowerCase());
        var tExtension = cmsUploadUploadFileType[cmsFnFileExtension(pFileName).toLowerCase()]['icon'];

        $('#cmsUploadUploadBody .cmsUploadUploadMessageLoading').hide();
        $('#cmsUploadUploadBody .cmsUploadUploadContainer').append(
            '\
            <div class="cmsUploadUploadImagePreviewContainer" style="position: absolute; width: auto; height: 20px; top: 0; left: 0; right: 0; bottom: 0; margin: auto; text-align: center">\
                '+((tExtension!='') ? '<i class="'+tExtension+'" aria-hidden="true" style="font-size: 22px"></i> ' : '')+'<strong>'+pFileName+'</strong>\
            </div>\
            '
        );

        $('#cmsUploadUploadBody .cmsUploadUploadToolbar').show();
        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="crop"]').css('visibility', 'hidden');
        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="flip-horizontal"]').css('visibility', 'hidden');
        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="flip-vertical"]').css('visibility', 'hidden');
        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="rotate-left"]').css('visibility', 'hidden');
        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="rotate-right"]').css('visibility', 'hidden');
        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="redo"]').css('visibility', 'hidden');
        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="undo"]').css('visibility', 'hidden');
        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="redo"]').css('visibility', 'hidden');
        $('#cmsUploadUploadBody .cmsUploadUploadSavePath').show();
        $('#cmsUploadUploadBody .cmsUploadUploadSavePath .cmsUploadUploadSavePathFile').html(cmsUploadFileInfo['upload_file']);
        cmsUploadDialog.getButton('btn-save').show();
    }

    var cmsUploadPaste = function() {
        $(window).unbind('paste');
        $(window).on('paste',
            function (event) {
                var items = (event.clipboardData || event.originalEvent.clipboardData).items;
                for (index in items) {
                    var item = items[index];
                    if (item.kind === 'file') {
                        var blob = item.getAsFile();
                        var reader = new FileReader();
                        reader.onload = function(event){
                            var tArr = event.target.result.split(';');
                            var tFileType = tArr[0].substring(5);

                            if (/^image\/\w+$/.test(tFileType)) {

                                if (cmsUploadUploadAcceptFile.includes(cmsUploadUploadMimeType[tFileType])) {
                                    $('#cmsUploadUploadBody span').hide();
                                    $('#cmsUploadUploadBody .cmsUploadUploadMessage button').hide();
                                    $('#cmsUploadUploadBody .cmsUploadUploadFile').hide();
                                    $('#cmsUploadUploadBody .cmsUploadUploadMessageLoading').html('Uploading...');
                                    $('#cmsUploadUploadBody .cmsUploadUploadMessageLoading').show();

                                    if ($('#cmsUploadUploadBody .cmsUploadUploadContainer .cmsUploadUploadImage')[0]) {
                                        $('#cmsUploadUploadBody .cmsUploadUploadContainer .cmsUploadUploadImage').remove();
                                    }

                                    cmsUploadDialog.getButton('btn-save').html('Save');

                                    var tFileName = 'New Image';

                                    $('#cmsUploadUploadBody .cmsUploadUploadSavePath').attr('data-new-file', tFileType);

                                    if ($cmsUploadImage) {
                                        $cmsUploadImage.cropper('destroy');
                                        $('#cmsUploadUploadBody .cmsUploadUploadImagePreviewContainer').remove();
                                    }
                                    var uploadedImageURL = event.target.result; //URL.createObjectURL(blob);
                                    cmsUploadLoadImage(uploadedImageURL, tFileName, tFileType);
                                }
                            }

                        };
                        reader.readAsDataURL(blob);
                    }
                }
            }
        );
    };

    var cmsUploadKeyPress = function () {
        $(window).on('keypress',
            function (e) {
                var keyCode = (e.keyCode ? e.keyCode : e.which);
                if (keyCode == '13') {
                    if ($('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="crop-ok"]').css('display') == 'inline-block') {
                        cmsUploadUpload(pObj, pId, 2, 1);
                    }
                }
            }
        );
    }

    var cmsUploadFileTypeCaption = (((cmsUploadUploadFileTypeIncluded.length > 2) ? cmsUploadUploadFileTypeIncluded.join(', ') : cmsUploadUploadFileTypeIncluded.join(' and '))).toLowerCase().replace(/\b[a-z]/g, function(letter) {
        return letter.toUpperCase();
    });

    if (pMode == 0) {
        //OPEN ASSET BROWSER

        var tDialogHeader = cmsUploadFileTypeCaption;
        tDialogHeader = tDialogHeader.replace(/And/g, 'and');

        cmsUploadPaste();
        cmsUploadKeyPress();

        var cmsUploadIsTinyMCE = (pOption) ? ((pOption=='tinymce') ? true : false) : false;

        var cmsUploadIsInput = (pOption) ? ((pOption=='input') ? true : false) : false;

        var cmsUploadIsCustom = (pOption) ? ((pOption=='custom') ? true : false) : false;

        cmsUploadDialog = BootstrapDialog.show({
            type: BootstrapDialog.TYPE_INFO,
            title: 'CMS : Assets',
            message: '\
            <div id="cmsUploadDialogContainer">\
                <h3 style="margin-top: 0px">Upload '+tDialogHeader+'</h3>\
                <ul class="nav nav-tabs" style="margin-bottom: 10px">\
                    <li class="nav-item active"><a href="javascript:void(0)" class="nav-link active" onclick="cmsUploadUpload(this, \''+pId+'\', 6)">Upload via File</a></li>\
                    <li class="nav-item"><a href="javascript:void(0)" class="nav-link" onclick="cmsUploadUpload(this, \''+pId+'\', 6)">Upload via URL</a></li>\
                </ul>\
                <div id="cmsUploadUploadBody" class="cmsUploadDialogGroup" data-option="0" style="display: inline-block; width: 100%; height: 400px; margin-bottom: 10px">\
                    <div class="cmsUploadUploadToolbar" style="display: none; margin-bottom: 10px">\
                        <button data-type="crop" data-mode="-1" class="btn btn-default btn-sm" onclick="cmsUploadUpload(this, \''+pId+'\', 2, 0)" alt="Crop"><i class="fa fa-crop" aria-hidden="true"></i></button>\
                        <button data-type="crop-ok" style="display: none" class="btn btn-default btn-sm" onclick="cmsUploadUpload(this, \''+pId+'\', 2, 1)" alt="Crop"><i class="fa fa-check" aria-hidden="true"></i></button>\
                        <button data-type="crop-cancel" data-mode="-1" style="display: none" class="btn btn-default btn-sm" onclick="cmsUploadUpload(this, \''+pId+'\', 2, 2)" alt="Crop"><i class="fa fa-ban" aria-hidden="true"></i></button>\
                        <button data-type="flip-horizontal" data-mode="-1" class="btn btn-default btn-sm" onclick="cmsUploadUpload(this, \''+pId+'\', 2, 3)" alt="Crop"><img src="'+cmsInfo["config"]["website"]["path"]+'application/resources/cms/images/icon-flip-horizontal.png"></button>\
                        <button data-type="flip-vertical" data-mode="-1" class="btn btn-default btn-sm" onclick="cmsUploadUpload(this, \''+pId+'\', 2, 4)" alt="Crop"><img src="'+cmsInfo["config"]["website"]["path"]+'application/resources/cms/images/icon-flip-vertical.png"></button>\
                        <button data-type="rotate-left" class="btn btn-default btn-sm" onclick="cmsUploadUpload(this, \''+pId+'\', 2, 6)" alt="Rotate Left"><i class="fa fa-undo" aria-hidden="true"></i></button>\
                        <button data-type="rotate-right" class="btn btn-default btn-sm" onclick="cmsUploadUpload(this, \''+pId+'\', 2, 5)" alt="Rotate Right"><i class="fa fa-repeat" aria-hidden="true"></i></button>\
                        <button data-type="undo" disabled class="btn btn-default btn-sm" onclick="cmsUploadUpload(this, \''+pId+'\', 3)" alt="Undo"><i class="fa fa-reply" aria-hidden="true"></i></button>\
                        <button data-type="redo" disabled class="btn btn-default btn-sm" onclick="cmsUploadUpload(this, \''+pId+'\', 4)" alt="Redo"><i class="fa fa-share" aria-hidden="true"></i></button>\
                        <label data-type="import" class="btn btn-default btn-sm pull-right" for="cmsUploadUploadImport" title="Upload image file" style="margin-bottom: 0px; padding: 5px 10px;">\
                            <input type="file" '+((cmsControlSettings['accept']!='') ? 'accept="'+cmsControlSettings['accept']+'"' : '')+' onchange="cmsUploadUpload(this, \''+pId+'\', 5)" id="cmsUploadUploadImport" style="position: absolute; width: 1px; height: 1px; padding: 0; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0;">\
                            <i class="fa fa-upload"></i>\
                        </label>\
                    </div>\
                    <div class="cmsUploadUploadContainer" style="position: relative; display: inline-block; width: 100%; height: 400px; border: 3px dotted #ddd; overflow: hidden">\
                        <input class="cmsUploadUploadFile" onchange="cmsUploadUpload(this, \''+pId+'\', 1)" type="file" '+((cmsControlSettings['accept']!='') ? 'accept="'+cmsControlSettings['accept']+'"' : '')+' style="position: absolute; width: 140%; height: 450px; top: -50px; left: -310px; right: 0; bottom: 0; margin: auto; z-index: 100; cursor: pointer; overflow: hidden; white-space: nowrap;">\
                        <div class="cmsUploadUploadMessage" style="position: absolute; width: 200px; height: 100px; top: 0; left: 0; right: 0; bottom: 0; margin: auto; text-align: center; color: #999">\
                            <span style="display: inline-block; font-size: 18px; font-weight: bold; width: 100%; margin-bottom: 10px">Drag '+((cmsUploadUploadFileTypeIncluded.length > 2) ? cmsUploadUploadFileTypeIncluded.join(', ') : cmsUploadUploadFileTypeIncluded.join(' and '))+' here</span>\
                            <span style="display: inline-block; font-size: 14px; font-weight: bold; width: 100%; margin-bottom: 5px">Or, if you prefer...</span>\
                            <button class="btn btn-sm btn-primary">Choose '+((cmsUploadUploadFileTypeIncluded.length > 2) ? cmsUploadUploadFileTypeIncluded.join(', ') : cmsUploadUploadFileTypeIncluded.join(' and '))+' to upload</button>\
                            <span class="cmsUploadUploadMessageLoading" style="display: none; font-size: 14px; font-weight: bold; width: 100%;">Loading...</span>\
                        </div>\
                        \
                    </div>\
                    <div class="cmsUploadUploadSavePath" style="display: none;" data-new-file="" data-save-owner="0" data-save-path="'+cmsInfo["config"]["website"]["path"]+'uploads'+((cmsControlSettings['upload_parent_dir']!='') ? '/'+cmsControlSettings['upload_parent_dir'] : '')+'" data-save-path-ini="'+cmsInfo["config"]["website"]["path"]+'uploads'+((cmsControlSettings['upload_parent_dir']!='') ? '/'+cmsControlSettings['upload_parent_dir'] : '')+'">\
                        <div class="cmsUploadUploadSavePathDir" style="display: inline-block; width: 50%; cursor: pointer" onclick="cmsUploadUpload(this, \''+pId+'\', 10)">Save path: '+cmsInfo["config"]["website"]["path"]+'uploads'+((cmsControlSettings['upload_parent_dir']!='') ? '/'+cmsControlSettings['upload_parent_dir'] : '')+'</div><div class="cmsUploadUploadSavePathFile" style="display: inline-block; width: 50%; text-align: right"></div>\
                    </div>\
                </div>\
                <div id="cmsUploadWebURL" class="cmsUploadDialogGroup" data-option="1" style="display: none; width: 100%; height: 400px; margin-bottom: 10px">\
                    <div class="input-group mb-3" style="width: 100%">\
                            <div class="input-group-prepend">\
                                <span class="input-group-text" onclick="" data-img-url-loaded="0">Paste an '+cmsUploadFileTypeCaption+' URL here:</span>\
                            </div>\
                            <input onkeyup="cmsUploadUpload(this, \''+pId+'\', 12)" onkeyblur="cmsUploadUpload(this, \''+pId+'\', 12)" id="cmsUploadWebURLInput" type="text" class="form-control">\
                            '+((cmsUploadUploadFileTypeIncluded.includes('image')) ? '\
                            <span style="display: inline-block; width: 100%; margin-top: 5px; margin-bottom: 10px">If your URL is correct, you\'ll see an image preview here. Large images may take a few minutes to appear.</span>\
                            ' : '')+'\
                    </div>\
                    '+((cmsUploadUploadFileTypeIncluded.includes('image')) ? '\
                    <div class="cmsUploadWebURLPreview" style="position: relative; display: inline-block; width: 100%; height: 380px; background-color: #ddd; background-repeat: no-repeat; background-size: contain; background-position: center center; -webkit-border-radius: 5px 5px 5px 5px; border-radius: 5px 5px 5px 5px;"></div>\
                    ' : '')+'\
                </div>\
            </div>\
                        ',
            onshown: function () {
                cmsUploadEditHistory = [];
                cmsUploadEditHistoryPointer = 0;

                var tOpenFile = $('#'+pId).val().trim();

                if (cmsUploadIsTinyMCE) {
                    if ($(pExt.selection.getNode()).filter('img')[0])
                        tOpenFile = $(pExt.selection.getNode()).filter('img').attr('src');
                    else
                        tOpenFile = '';
                }

                if (cmsUploadIsInput) {
                    if ($('#'+pId).val()!='')
                        tOpenFile = $('#'+pId).val();
                    else
                        tOpenFile = '';
                }

                if (cmsUploadIsCustom) {
                    tOpenFile = pExt;
                }

                if (tOpenFile!='') {
                    if (tOpenFile.indexOf('http')>=0) {
                        $('#cmsUploadWebURLInput').val(tOpenFile);

                        var tIndex = 2;

                        $('#cmsUploadDialogContainer .nav-tabs li').removeClass('active');
                        $('#cmsUploadDialogContainer .nav-tabs li:eq('+tIndex+')').addClass('active');

                        $('#cmsUploadDialogContainer .cmsUploadDialogGroup').hide();
                        $('#cmsUploadDialogContainer .cmsUploadDialogGroup[data-option="'+tIndex+'"]').css('display', 'inline-block');

                        $('#cmsUploadFilePreview').hide();

                        $('#cmsUploadWebURL .cmsUploadWebURLPreview').css('background-image', 'url(\''+$('#cmsUploadWebURLInput').val()+'\')');

                        cmsUploadUpload(this, pId, 12);

                        cmsUploadDialog.getButton('btn-save').show();
                        cmsUploadDialog.getButton('btn-save').html('Insert');
                    } else {
                        tOpenFile = cmsInfo['global']['UPLOADS_URL']+tOpenFile;
                        cmsUploadUpload(pObj, pId, 8, tOpenFile);
                    }
                }

            },
            buttons: [
                {
                    id: 'btn-save',
                    label: 'Save',
                    action: function(dialog) {
                        var tFuncInsert = function () {
                            if (cmsUploadIsTinyMCE) {
                                if ($(pExt.selection.getNode()).filter('img').attr('cms-data')) {
                                    if ($(pExt.selection.getNode()).filter('img').attr('cms-data') == '1') {
                                        var img = pExt.selection.getNode();
                                        tinymce.activeEditor.dom.setAttrib(img, 'src', $('#cmsUploadUploadBody .cmsUploadUploadSavePath').attr('data-save-path') + '/' + cmsUploadFileInfo['upload_file']);
                                        tinyMCE.activeEditor.undoManager.add();
                                    }
                                } else {
                                    pExt.insertContent('<img src="'+$('#cmsUploadUploadBody .cmsUploadUploadSavePath').attr('data-save-path') + '/' + cmsUploadFileInfo['upload_file']+'" cms-data="1" style="margin-left: 5px; margin-right: 5px">');
                                    //pExt.insertContent('<div style="float: left; margin: 0 10px 0 10px; width: 300px" cms-data="1"><img src="'+$('#cmsUploadUploadBody .cmsUploadUploadSavePath').attr('data-save-path') + '/' + cmsUploadFileInfo['upload_file']+'" width="100%" cms-data="1"></div>');
                                    tinyMCE.activeEditor.undoManager.add();
                                }
                            } else if (cmsUploadIsInput) {
                                //$('#' + pId).val(cmsUploadFileInfo['upload_file']);
                                $('#' + pId).val($('#cmsUploadUploadBody .cmsUploadUploadSavePath').attr('data-save-path') + '/' + cmsUploadFileInfo['upload_file']);
                                if (typeof(pExt) == 'string') eval(pExt);
                            } else if (cmsUploadIsCustom) {
                                pExt2($('#cmsUploadUploadBody .cmsUploadUploadSavePath').attr('data-save-path') + '/' + cmsUploadFileInfo['upload_file']);
                            } else {
                                //$('#' + pId + '_display').val(cmsUploadFileInfo['upload_file']);
                                //#eval('' + pId + '_add_file();');
                                //$('#' + pId).val(cmsUploadFileInfo['upload_file']);

                                $('#' + pId + '_display').val(cmsUploadFileInfo['upload_file']);
                                eval('' + pId + '_add_file();');
                                $('#' + pId).val(cmsUploadFileInfo['upload_short_file']);
                            }

                            cmsUploadDialog.close();
                            $cmsUploadImage = null;
                            $(window).unbind('paste');
                        };

                        if ($('#cmsUploadDialogContainer .nav-tabs li.active').index() == 0) {
                            //UPLOAD

                            if (dialog.getButton('btn-save').html() == 'Save') {

                                var $button = this;
                                $button.disable();
                                $button.spin();

                                var tFunc = function (pDialog) {
                                    var tCmsUploadFileInfo = cmsUploadFileInfo; //JSON.parse(JSON.stringify(cmsUploadFileInfo));

                                    if ($('#cmsUploadUploadBody .cmsUploadUploadImagePreview')[0]) {
                                        var tFuncSaveImage = function () {
                                            $cmsUploadImage.cropper('getCroppedCanvas', { imageSmoothingEnabled: false, imageSmoothingQuality: 'high' }).toBlob(
                                                function (blob) {
                                                    var form_data = new FormData();
                                                    form_data.append('cmsUploadUploadFile', blob);
                                                    form_data.append('cmsUploadFileInfo', json_encode(tCmsUploadFileInfo));
                                                    form_data.append('cmsUploadSavePath', $('#cmsUploadUploadBody .cmsUploadUploadSavePath').attr('data-save-path'));
                                                    form_data.append('CMS_POST_REQ', $('#' + pId).attr('cms-control-settings'));
                                                    form_data.append('cmsUploadTableId', $('.cms-form-primary-id').val());
                                                    $.ajax(
                                                        {
                                                            xhr: function () {
                                                                var xhr = new window.XMLHttpRequest();
                                                                xhr.upload.addEventListener("progress", function (evt) {
                                                                    if (evt.lengthComputable) {
                                                                        var percentComplete = evt.loaded / evt.total;
                                                                        //Do something with upload progress here
                                                                        var progressCounter = parseInt(percentComplete * 100, 10);
                                                                    }
                                                                }, false);

                                                                xhr.addEventListener("progress", function (evt) {
                                                                    if (evt.lengthComputable) {
                                                                        var percentComplete = evt.loaded / evt.total;
                                                                        //Do something with download progress
                                                                        var progressCounter = parseInt(percentComplete * 100, 10);
                                                                    }
                                                                }, false);

                                                                return xhr;
                                                            },
                                                            url: "",
                                                            cache: false,
                                                            contentType: false,
                                                            processData: false,
                                                            data: form_data,
                                                            type: 'post',
                                                            success: function () {
                                                                //100%
                                                            }
                                                        }
                                                    ).done(
                                                        function (data) {
                                                            var arrRet = JSON.parse(data);

                                                            cmsUploadFileInfo = tCmsUploadFileInfo;

                                                            cmsUploadFileInfo['upload_file'] = arrRet['temp_full_url'];
                                                            cmsUploadFileInfo['upload_short_file'] = arrRet['temp_short_url'];

                                                            $('#cmsUploadUploadBody .cmsUploadUploadSavePath .cmsUploadUploadSavePathFile').html(cmsInfo["config"]['website']['path']+cmsUploadFileInfo['upload_file']);
                                                            if (pDialog) pDialog.close();

                                                            cmsUploadDialog.getButton('btn-save').enable();
                                                            cmsUploadDialog.getButton('btn-save').stopSpin();

                                                            tFuncInsert();
                                                        }
                                                    );

                                                },
                                                cmsUploadFileInfo['image_info'][2]
                                            );
                                        }

                                        var tArr = cmsControlSettings['img_aspect_ratio'].split(':');
                                        var tAspectRatio = ($cmsUploadImage.cropper('getData').width/$cmsUploadImage.cropper('getData').height).toFixed(2); //$cmsUploadImage.cropper('getImageData').aspectRatio.toFixed(2); //parseFloat(cmsUploadFileInfo['image_info'][0]/cmsUploadFileInfo['image_info'][1]).toFixed(2);
                                        if (tArr.length == 2) {
                                            tAspectRatioDefault = (parseInt(tArr[0],10) / parseInt(tArr[1],10)).toFixed(2);

                                            if ($cmsUploadImage.cropper('getImageData').aspectRatio.toFixed(2) != tAspectRatioDefault) {
                                                if (!$cmsUploadImage.cropper('getCropBoxData').left) {
                                                    BootstrapDialog.alert('Please crop the image');
                                                    cmsUploadDialog.getButton('btn-save').stopSpin();
                                                    cmsUploadDialog.getButton('btn-save').enable();
                                                } else {
                                                    tFuncSaveImage();
                                                }
                                            } else {
                                                tFuncSaveImage();
                                            }
                                        } else {
                                            tFuncSaveImage();
                                        }
                                    } else {

                                        var files_data = $('#cmsUploadUploadBody input[type="file"]'); //.prop("files");
                                        var form_data = new FormData();

                                        var tFormUpload = function() {
                                            $.ajax(
                                                {
                                                    xhr: function () {
                                                        var xhr = new window.XMLHttpRequest();
                                                        xhr.upload.addEventListener("progress", function (evt) {
                                                            if (evt.lengthComputable) {
                                                                var percentComplete = evt.loaded / evt.total;
                                                                //Do something with upload progress here
                                                                var progressCounter = parseInt(percentComplete * 100, 10);
                                                            }
                                                        }, false);

                                                        xhr.addEventListener("progress", function (evt) {
                                                            if (evt.lengthComputable) {
                                                                var percentComplete = evt.loaded / evt.total;
                                                                //Do something with download progress
                                                                var progressCounter = parseInt(percentComplete * 100, 10);
                                                            }
                                                        }, false);

                                                        return xhr;
                                                    },
                                                    url: "",
                                                    cache: false,
                                                    contentType: false,
                                                    processData: false,
                                                    data: form_data,
                                                    type: 'post',
                                                    success: function () {
                                                        //100%
                                                    }
                                                }
                                            ).done(
                                                function (data) {
                                                    var arrRet = JSON.parse(data);

                                                    cmsUploadFileInfo = tCmsUploadFileInfo;
                                                    //$('#cmsUploadUploadBody .cmsUploadUploadSavePath').attr('data-save-owner', 1);

                                                    cmsUploadFileInfo['upload_file'] = arrRet['temp_full_url'];
                                                    cmsUploadFileInfo['upload_short_file'] = arrRet['temp_short_url'];

                                                    $('#cmsUploadUploadBody .cmsUploadUploadSavePath .cmsUploadUploadSavePathFile').html(cmsUploadFileInfo['upload_file']);
                                                    if (pDialog) pDialog.close();

                                                    cmsUploadDialog.getButton('btn-save').enable();
                                                    cmsUploadDialog.getButton('btn-save').stopSpin();

                                                    tFuncInsert();
                                                    /*$('#' + pId + '_display').val($('#cmsUploadUploadBody .cmsUploadUploadSavePath').attr('data-save-path') + '/' + cmsUploadFileInfo['upload_file']);
                                                     eval('' + pId + '_add_file();');
                                                     $('#' + pId).val($('#cmsUploadUploadBody .cmsUploadUploadSavePath').attr('data-save-path') + '/' + cmsUploadFileInfo['upload_file']);

                                                     $('#cmsUploadUploadBody .cmsUploadUploadSavePath').attr('data-save-path-ini', $('#cmsUploadUploadBody .cmsUploadUploadSavePath').attr('data-save-path'));

                                                     cmsUploadDialog.close();
                                                     $(window).unbind('paste');*/
                                                }
                                            );
                                        };

                                        form_data.append('cmsUploadFileInfo', json_encode(tCmsUploadFileInfo));
                                        form_data.append('cmsUploadSavePath', $('#cmsUploadUploadBody .cmsUploadUploadSavePath').attr('data-save-path'));
                                        form_data.append('cmsUploadSavePathIni', $('#cmsUploadUploadBody .cmsUploadUploadSavePath').attr('data-save-path-ini'));
                                        form_data.append('CMS_POST_REQ', $('#' + pId).attr('cms-control-settings'));

                                        if (!$('#cmsUploadUploadBody .cmsUploadUploadSavePath .cmsUploadUploadSavePathFile').attr('data-blob-url')) {
                                            $('#cmsUploadUploadBody .cmsUploadUploadSavePath .cmsUploadUploadSavePathFile').attr('data-blob-url', '');
                                        }

                                        if ($('#cmsUploadUploadBody .cmsUploadUploadSavePath .cmsUploadUploadSavePathFile').attr('data-blob-url')!='') {
                                            var xhr = new XMLHttpRequest();
                                            xhr.open('GET', $('#cmsUploadUploadBody .cmsUploadUploadSavePath .cmsUploadUploadSavePathFile').attr('data-blob-url'), true);
                                            xhr.responseType = 'blob';
                                            xhr.onload = function(e) {
                                                if (this.status == 200) {
                                                    form_data.append('cmsUploadUploadFile', this.response);
                                                    tFormUpload();
                                                }
                                            };
                                            xhr.send();
                                        } else {
                                            $(files_data).each(
                                                function (pIndex, pObj) {
                                                    var file_data = $(pObj).prop("files");
                                                    if (file_data && file_data.length) {
                                                        var blob = file_data[0];
                                                        form_data.append('cmsUploadUploadFile', blob);
                                                    }
                                                }
                                            );
                                            tFormUpload();
                                        }
                                    }
                                };

                                var tFuncIni = function () {
                                    if ($('.cmsUploadUploadSavePath').attr('data-save-owner') == '0') {
                                        var form_data = new FormData();
                                        form_data.append('cmsUploadFileCheck', json_encode(cmsUploadFileInfo));
                                        form_data.append('cmsUploadSavePath', $('#cmsUploadUploadBody .cmsUploadUploadSavePath').attr('data-save-path'));
                                        form_data.append('CMS_POST_REQ', $('#' + pId).attr('cms-control-settings'));

                                        $.ajax(
                                            {
                                                type: 'POST',
                                                url: '',
                                                data: form_data,
                                                cache: false,
                                                contentType: false,
                                                processData: false
                                            }
                                        ).done(
                                            function (data) {
                                                var postRet = parseInt(data, 10);

                                                if (postRet == 1) {
                                                    BootstrapDialog.show({
                                                        type: BootstrapDialog.TYPE_INFO,
                                                        title: 'CMS : Assets',
                                                        message: '<strong id="cmsUploadUploadFilenameLabel">' + cmsUploadFileInfo['upload_file'] + '</strong> already exist in ' + $('.cmsUploadUploadSavePath').attr('data-save-path') + '?<br>Enter new filename or click continue to overwrite.<input id="cmsUploadUploadFilename" type="text" class="form-control" placeholder="' + cmsUploadFileInfo['upload_file'] + '">',
                                                        onshown: function () {

                                                        },
                                                        buttons: [
                                                            {
                                                                label: 'Continue',
                                                                action: function (dialog) {
                                                                    if ($('#cmsUploadUploadFilename').val().trim() != '') {
                                                                        var form_data = new FormData();
                                                                        var tCmsUploadFileInfo = cmsUploadFileInfo; //JSON.parse(JSON.stringify(cmsUploadFileInfo));
                                                                        tCmsUploadFileInfo['upload_file'] = $('#cmsUploadUploadFilename').val().trim();

                                                                        form_data.append('cmsUploadFileCheck', json_encode(tCmsUploadFileInfo));
                                                                        form_data.append('CMS_POST_REQ', $('#' + pId).attr('cms-control-settings'));

                                                                        $.ajax(
                                                                            {
                                                                                type: 'POST',
                                                                                url: '',
                                                                                data: form_data,
                                                                                cache: false,
                                                                                contentType: false,
                                                                                processData: false
                                                                            }
                                                                        ).done(
                                                                            function (data) {
                                                                                var postRet = parseInt(data, 10);

                                                                                if (postRet == 1) {
                                                                                    $('#cmsUploadUploadFilenameLabel').html(tCmsUploadFileInfo['upload_file']);
                                                                                    $('#cmsUploadUploadFilename').attr('placeholder', tCmsUploadFileInfo['upload_file']);
                                                                                    $('#cmsUploadUploadFilename').val('');
                                                                                } else {
                                                                                    tFunc(dialog);
                                                                                }
                                                                            }
                                                                        );
                                                                    } else {
                                                                        tFunc(dialog);
                                                                    }
                                                                }
                                                            },
                                                            {
                                                                label: 'Cancel',
                                                                action: function (dialog) {
                                                                    cmsUploadDialog.getButton('btn-save').enable();
                                                                    cmsUploadDialog.getButton('btn-save').stopSpin();
                                                                    dialog.close();
                                                                }
                                                            }
                                                        ]
                                                    });
                                                } else {
                                                    tFunc(null);
                                                }
                                            }
                                        );
                                    } else {
                                        tFunc();
                                    }
                                };

                                if ($('#cmsUploadUploadBody .cmsUploadUploadSavePath').attr('data-new-file')!='') {
                                    BootstrapDialog.show({
                                        type: BootstrapDialog.TYPE_INFO,
                                        title: 'New '+cmsUploadFileTypeCaption,
                                        message: '\
                                                    <div>\
                                                        <label>Filename:</label>\
                                                        <input id="cmsUploadNewItem" type="text" class="form-control">\
                                                    </div>\
                                                ',
                                        onshown: function () {
                                            $('#cmsUploadNewItem').focus();
                                        },
                                        buttons: [{
                                            label: 'Ok',
                                            action: function(dialog) {
                                                if ($('#cmsUploadNewItem').val().trim()=='') {
                                                    $('#cmsUploadNewItem').focus();
                                                    return false;
                                                }

                                                if (!cmsFnValidateFileName($('#cmsUploadNewItem').val().trim())) {
                                                    $('#cmsUploadNewItem').focus();
                                                    if ($('#cmsUploadNewItemInvalid')[0]) $('#cmsUploadNewItemInvalid').remove();
                                                    $('#cmsUploadNewItem').after('<span id="cmsUploadNewItemInvalid" style="display: inline-block; width: 100%; margin-top: 5px">Invalid filename.</span>');
                                                    return false;
                                                }

                                                var tFileType = $('#cmsUploadUploadBody .cmsUploadUploadSavePath').attr('data-new-file');
                                                var tFileName = $('#cmsUploadNewItem').val().trim();
                                                if (!cmsFnFileExtension(tFileName)) {
                                                    tFileName += '.'+cmsUploadUploadMimeType[tFileType];
                                                } else {
                                                    if (tFileName.indexOf(cmsUploadUploadMimeType[tFileType]) == -1) {
                                                        tFileName += '.'+cmsUploadUploadMimeType[tFileType];
                                                    }
                                                }
                                                cmsUploadFileInfo['upload_file'] = tFileName;
                                                $('#cmsUploadUploadBody .cmsUploadUploadSavePath').attr('data-new-file', '');
                                                $('#cmsUploadUploadBody .cmsUploadUploadSavePathFile').html(tFileName);

                                                tFuncIni();

                                                dialog.close();
                                            }
                                        }, {
                                            label: 'Cancel',
                                            action: function(dialog) {
                                                cmsUploadDialog.getButton('btn-save').stopSpin();
                                                cmsUploadDialog.getButton('btn-save').enable();
                                                dialog.close();
                                            }
                                        }],
                                        closable: false
                                    });
                                } else {
                                    tFuncIni();
                                }

                            } else if (dialog.getButton('btn-save').html() == 'Insert') {
                                if ($cmsUploadImage) {
                                    var tArr = cmsControlSettings['img_aspect_ratio'].split(':');
                                    var tAspectRatio = $cmsUploadImage.cropper('getImageData').aspectRatio.toFixed(2); //parseFloat(cmsUploadFileInfo['image_info'][0]/cmsUploadFileInfo['image_info'][1]).toFixed(2);
                                    if (tArr.length == 2) {
                                        tAspectRatioDefault = parseInt(tArr[0],10) / parseInt(tArr[1],10);
                                        if (tAspectRatio != tAspectRatioDefault) {
                                            BootstrapDialog.confirm('The image you want to insert does not match the required aspect ratio. You can crop the image to get the right size.<br><br>Are you sure you want to ignore the image aspect ratio?', function(result){
                                                if(result) {
                                                    tFuncInsert();
                                                }
                                            });
                                        } else {
                                            tFuncInsert();
                                        }
                                    } else {
                                        tFuncInsert();
                                    }
                                } else {
                                    tFuncInsert();
                                }
                            }
                        } else {
                            //WEB ADDRESS URL
                            if ($('#cmsUploadWebURLInput').val().trim()=='') {
                                $('#cmsUploadWebURLInput').focus();
                                return false;
                            }

                            tFuncInsert();

                            $('#' + pId + '_display').val($('#cmsUploadWebURLInput').val().trim());
                            eval('' + pId + '_add_file();');
                            $('#' + pId).val($('#cmsUploadWebURLInput').val().trim());
                            cmsUploadDialog.close();
                            $(window).unbind('paste');
                            $cmsUploadImage = null;
                        }
                    }
                },
                {
                    id: 'btn-cancel',
                    label: 'Cancel',
                    action: function(dialog) {
                        dialog.close();
                        $(window).unbind('paste');
                        $cmsUploadImage = null;
                    }
                }
            ],
            size: BootstrapDialog.SIZE_WIDE,
            closable: false
        });
        cmsUploadDialog.getModalHeader().hide();
        cmsUploadDialog.getButton('btn-save').hide();
    } else if (pMode == 1) {
        //UPLOAD FILE
        var file_data = $(pObj).prop("files");

        if (file_data && file_data.length) {
            var file = file_data[0];

            $('#cmsUploadUploadBody span').hide();
            $('#cmsUploadUploadBody .cmsUploadUploadMessage button').hide();
            $('#cmsUploadUploadBody .cmsUploadUploadFile').hide();
            $('#cmsUploadUploadBody .cmsUploadUploadMessageLoading').html('Uploading...');
            $('#cmsUploadUploadBody .cmsUploadUploadMessageLoading').show();

            if ($('#cmsUploadUploadBody .cmsUploadUploadContainer .cmsUploadUploadImage')[0]) {
                $('#cmsUploadUploadBody .cmsUploadUploadContainer .cmsUploadUploadImage').remove();
            }

            cmsUploadDialog.getButton('btn-save').html('Save');

            if (/^image\/\w+$/.test(file.type)) {
                var uploadedImageURL = URL.createObjectURL(file);
                cmsUploadLoadImage(uploadedImageURL, file.name, file.type);
            } else {
                cmsUploadLoadFile('', file.name, file.type);
            }
        }
    } else if (pMode == 2) {
        //UPLOAD CROPPED IMAGE
        if (pOption == 0) {
            //SHOW CROPPER

            if ($(pObj).attr('data-mode') == '-1') {
                $cmsUploadImage.cropper('crop');
                $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="crop-ok"]').show();
                $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="crop-cancel"]').show();
                $(pObj).blur();
                cmsUploadKeyPress();
                $(pObj).attr('data-mode', '1');
            } else {
                cmsUploadUpload(pObj, pId, 2, 2);
            }
        } else if (pOption == 1) {
            //CROP
            var result = $cmsUploadImage.cropper('getCroppedCanvas', {maxWidth: 4096, maxHeight: 4096, fillColor: "#fff"}); //.toDataURL('image/jpeg')
            result.toBlob(function (blob) {
                var url = URL.createObjectURL(blob);

                $cmsUploadImage.cropper('destroy');
                $('#cmsUploadUploadBody .cmsUploadUploadImagePreview').attr('src', url);
                cmsUploadLoadCropper();

                cmsUploadEditHistory[cmsUploadEditHistory.length] = $('#cmsUploadUploadBody .cmsUploadUploadImagePreview').attr('src');
                cmsUploadEditHistoryPointer = cmsUploadEditHistory.length-1;

                $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="undo"]').removeAttr('disabled');
                $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="redo"]').attr('disabled', 'disabled');

                cmsUploadDialog.getButton('btn-save').html('Save');
                cmsUploadDialog.getButton('btn-save').show();

            });
            $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="crop"]').attr('data-mode', '-1');
            $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="crop-ok"]').hide();
            $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="crop-cancel"]').hide();
        } else if (pOption == 2) {
            //CLEAR CROP
            $cmsUploadImage.cropper('clear');
            $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="crop"]').attr('data-mode', '-1');
            $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="crop-ok"]').hide();
            $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="crop-cancel"]').hide();
        } else if (pOption == 3) {
            //FLIP HORIZONTAL
            $cmsUploadImage.cropper('scaleX', parseInt($(pObj).attr('data-mode'),10));
            if ($(pObj).attr('data-mode') == '-1')
                $(pObj).attr('data-mode', '1');
            else
                $(pObj).attr('data-mode', '-1');
            $cmsUploadImage.cropper('getCroppedCanvas').toBlob(function (blob) {
                var url = URL.createObjectURL(blob);
                cmsUploadEditHistory[cmsUploadEditHistory.length] = url;
                cmsUploadEditHistoryPointer = cmsUploadEditHistory.length-1;
            });
            $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="undo"]').removeAttr('disabled');
            $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="redo"]').attr('disabled', 'disabled');
            cmsUploadDialog.getButton('btn-save').html('Save');
            cmsUploadDialog.getButton('btn-save').show();
        } else if (pOption == 4) {
            //FLIP HORIZONTAL
            $cmsUploadImage.cropper('scaleY', parseInt($(pObj).attr('data-mode'),10));
            if ($(pObj).attr('data-mode') == '-1')
                $(pObj).attr('data-mode', '1');
            else
                $(pObj).attr('data-mode', '-1');
            $cmsUploadImage.cropper('getCroppedCanvas').toBlob(function (blob) {
                var url = URL.createObjectURL(blob);
                cmsUploadEditHistory[cmsUploadEditHistory.length] = url;
                cmsUploadEditHistoryPointer = cmsUploadEditHistory.length-1;
            });
            $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="undo"]').removeAttr('disabled');
            $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="redo"]').attr('disabled', 'disabled');
            cmsUploadDialog.getButton('btn-save').html('Save');
            cmsUploadDialog.getButton('btn-save').show();
        } else if (pOption == 5) {
            //ROTATE 90
            $cmsUploadImage.cropper('rotate', 90);
            $cmsUploadImage.cropper('getCroppedCanvas').toBlob(function (blob) {
                var url = URL.createObjectURL(blob);
                cmsUploadEditHistory[cmsUploadEditHistory.length] = url;
                cmsUploadEditHistoryPointer = cmsUploadEditHistory.length-1;
            });
            $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="undo"]').removeAttr('disabled');
            $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="redo"]').attr('disabled', 'disabled');
            cmsUploadDialog.getButton('btn-save').html('Save');
            cmsUploadDialog.getButton('btn-save').show();
        } else if (pOption == 6) {
            //ROTATE -90
            $cmsUploadImage.cropper('rotate', -90);
            $cmsUploadImage.cropper('getCroppedCanvas').toBlob(function (blob) {
                var url = URL.createObjectURL(blob);
                cmsUploadEditHistory[cmsUploadEditHistory.length] = url;
                cmsUploadEditHistoryPointer = cmsUploadEditHistory.length-1;
            });
            $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="undo"]').removeAttr('disabled');
            $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="redo"]').attr('disabled', 'disabled');
            cmsUploadDialog.getButton('btn-save').html('Save');
            cmsUploadDialog.getButton('btn-save').show();
        }
    } else if (pMode == 3) {
        //UNDO

        cmsUploadEditHistoryPointer--;

        $cmsUploadImage.cropper('destroy');
        $('#cmsUploadUploadBody .cmsUploadUploadImagePreview').attr('src', cmsUploadEditHistory[cmsUploadEditHistoryPointer]);
        cmsUploadLoadCropper();

        if (cmsUploadEditHistoryPointer == 0) {
            $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="undo"]').attr('disabled', 'disabled');
        }
        if (cmsUploadEditHistory.length > 0) {
            $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="redo"]').removeAttr('disabled');
        }

        if (cmsUploadEditHistoryPointer == 0) {

            //cmsUploadDialog.getButton('btn-save').html('Insert');
            cmsUploadDialog.getButton('btn-save').hide();

        } else if (cmsUploadEditHistory.length > 0) {
            cmsUploadDialog.getButton('btn-save').html('Save');
            cmsUploadDialog.getButton('btn-save').show();
            $('#cmsUploadUploadBody .cmsUploadUploadSavePath').attr('data-save-owner', 0);
        }

        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="crop"]').attr('data-mode', '-1');
        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="flip-horizontal"]').attr('data-mode', '-1');
        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="flip-vertical"]').attr('data-mode', '-1');
        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="crop-ok"]').hide();
        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="crop-cancel"]').hide();
    } else if (pMode == 4) {
        //REDO

        cmsUploadEditHistoryPointer++;

        $cmsUploadImage.cropper('destroy');
        $('#cmsUploadUploadBody .cmsUploadUploadImagePreview').attr('src', cmsUploadEditHistory[cmsUploadEditHistoryPointer]);
        cmsUploadLoadCropper();

        if (cmsUploadEditHistoryPointer >= (cmsUploadEditHistory.length-1)) {
            $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="redo"]').attr('disabled', 'disabled');
        }
        if (cmsUploadEditHistory.length > 0) {
            $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="undo"]').removeAttr('disabled');
        }

        if (cmsUploadEditHistoryPointer >= (cmsUploadEditHistory.length-1)) {
            cmsUploadDialog.getButton('btn-save').html('Save');
            cmsUploadDialog.getButton('btn-save').show();
            $('#cmsUploadUploadBody .cmsUploadUploadSavePath').attr('data-save-owner', 0);
        } else if (cmsUploadEditHistory.length > 0) {
            cmsUploadDialog.getButton('btn-save').html('Insert');
        }

        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="crop"]').attr('data-mode', '-1');
        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="flip-horizontal"]').attr('data-mode', '-1');
        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="flip-vertical"]').attr('data-mode', '-1');
        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="crop-ok"]').hide();
        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="crop-cancel"]').hide();
    } else if (pMode == 5) {
        //IMPORT FILE

        $('#cmsUploadUploadBody .cmsUploadUploadImagePreviewContainer').hide();
        if ($cmsUploadImage) $cmsUploadImage.cropper('destroy');
        $('#cmsUploadUploadBody .cmsUploadUploadImagePreviewContainer').remove();
        $('#cmsUploadUploadBody .cmsUploadUploadMessageLoading').html('Loading...');
        $('#cmsUploadUploadBody .cmsUploadUploadMessageLoading').show();

        cmsUploadEditHistory = [];
        cmsUploadEditHistoryPointer = 0;

        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="crop"]').attr('data-mode', '-1');
        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="flip-horizontal"]').attr('data-mode', '-1');
        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="flip-vertical"]').attr('data-mode', '-1');
        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="crop-ok"]').hide();
        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="crop-cancel"]').hide();
        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="undo"]').attr('disabled', 'disabled');
        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="redo"]').attr('disabled', 'disabled');

        $('#cmsUploadUploadBody .cmsUploadUploadFile').remove();

        $('#cmsUploadUploadBody .cmsUploadUploadSavePath').attr('data-save-owner', 0);

        cmsUploadDialog.getButton('btn-save').html('Save');

        cmsUploadUpload(pObj, pId, 1);
    } else if (pMode == 6) {
        //MENU
        var tIndex = $(pObj).parent().index();

        $('#cmsUploadDialogContainer .nav-tabs li a').removeClass('active');
        $('#cmsUploadDialogContainer .nav-tabs li:eq('+tIndex+') a').addClass('active');

        $('#cmsUploadDialogContainer .nav-tabs li').removeClass('active');
        $('#cmsUploadDialogContainer .nav-tabs li:eq('+tIndex+')').addClass('active');

        $('#cmsUploadDialogContainer .cmsUploadDialogGroup').hide();
        $('#cmsUploadDialogContainer .cmsUploadDialogGroup[data-option="'+tIndex+'"]').css('display', 'inline-block');

        $('#cmsUploadFilePreview').hide();

        if (tIndex == 0) {
            //UPLOAD

            //COPY & PASTE SUPPORT
            cmsUploadPaste();

            //cmsUploadDialog.getButton('btn-save').show();
        } else if (tIndex == 1) {
            //WEB URL

            $(window).unbind('paste');

            //cmsUploadDialog.getButton('btn-save').show();
        }
    } else if (pMode == 7) {
    } else if (pMode == 8) {
        //LOAD URL
        $('#cmsUploadUploadBody .cmsUploadUploadImagePreviewContainer').hide();
        if ($cmsUploadImage) $cmsUploadImage.cropper('destroy');
        $('#cmsUploadUploadBody .cmsUploadUploadImagePreviewContainer').remove();
        $('#cmsUploadUploadBody .cmsUploadUploadMessageLoading').html('Loading...');
        $('#cmsUploadUploadBody .cmsUploadUploadMessageLoading').show();

        cmsUploadEditHistory = [];
        cmsUploadEditHistoryPointer = 0;

        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="crop-ok"]').hide();
        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="crop-cancel"]').hide();
        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="undo"]').attr('disabled', 'disabled');
        $('#cmsUploadUploadBody .cmsUploadUploadToolbar button[data-type="redo"]').attr('disabled', 'disabled');

        $('#cmsUploadUploadBody span').hide();
        $('#cmsUploadUploadBody .cmsUploadUploadMessage button').hide();
        $('#cmsUploadUploadBody .cmsUploadUploadFile').hide();
        $('#cmsUploadUploadBody .cmsUploadUploadMessageLoading').html('Loading...');
        $('#cmsUploadUploadBody .cmsUploadUploadMessageLoading').show();

        if ($('#cmsUploadUploadBody .cmsUploadUploadContainer .cmsUploadUploadImage')[0]) {
            $('#cmsUploadUploadBody .cmsUploadUploadContainer .cmsUploadUploadImage').remove();
        }

        var tGetKeyByValue = function (paramObj, paramVal) {
            for( var prop in paramObj ) {
                if( paramObj.hasOwnProperty( prop ) ) {
                    if( paramObj[ prop ] === paramVal )
                        return prop;
                }
            }
        }

        if (cmsUploadUploadFileType[cmsFnFileExtension(pOption)]) {
            if (cmsUploadUploadFileType[cmsFnFileExtension(pOption)]['type'] == 'image') {
                var xhr = new XMLHttpRequest();
                console.log(pOption);
                xhr.open("GET", pOption);
                xhr.responseType = "blob";
                xhr.onload = function (e) {
                    var urlCreator = window.URL || window.webkitURL;
                    var xhrURL = urlCreator.createObjectURL(this.response);
                    var tFileName = (!pExt) ? cmsFnBaseName(pOption) : pExt[1];
                    var tSavePath = (!pExt) ? cmsFnDirName(pOption) : pExt[0]; //console.log(pOption, cmsFnDirName(pOption), pExt);

                    cmsUploadLoadImage(xhrURL, tFileName, e.currentTarget.response.type);

                    $('#cmsUploadUploadBody .cmsUploadUploadSavePath').attr('data-save-path', tSavePath);
                    $('#cmsUploadUploadBody .cmsUploadUploadSavePath .cmsUploadUploadSavePathDir').html('Save path: '+tSavePath);
                    $('#cmsUploadUploadBody .cmsUploadUploadSavePath .cmsUploadUploadSavePathFile').attr('data-blob-url', xhrURL);

                    //cmsUploadDialog.getButton('btn-save').html('Insert');

                    if (pExt) {
                        pExt[2].close();
                        cmsUploadDialog.getButton('btn-save').html('Save');
                    }
                };
                xhr.send();
            } else {
                var tFileName = (!pExt) ? cmsFnBaseName(pOption) : pExt[1];
                var tSavePath = (!pExt) ? cmsFnDirName(pOption) : pExt[0];
                var tFileType = (tGetKeyByValue(cmsUploadUploadMimeType, cmsFnFileExtension(pOption)));
                cmsUploadLoadFile(pOption, tFileName, tFileType);

                $('#cmsUploadUploadBody .cmsUploadUploadSavePath').attr('data-save-path', tSavePath);
                $('#cmsUploadUploadBody .cmsUploadUploadSavePath .cmsUploadUploadSavePathDir').html('Save path: '+tSavePath);
                $('#cmsUploadUploadBody .cmsUploadUploadSavePath .cmsUploadUploadSavePathFile').attr('data-blob-url', '');

                //cmsUploadDialog.getButton('btn-save').html('Insert');

                if (pExt) {
                    pExt[2].close();
                    cmsUploadDialog.getButton('btn-save').html('Save');
                }
            }
        } else {
            var tFileName = (!pExt) ? cmsFnBaseName(pOption) : pExt[1];
            var tSavePath = (!pExt) ? cmsFnDirName(pOption) : pExt[0];
            var tFileType = (tGetKeyByValue(cmsUploadUploadMimeType, cmsFnFileExtension(pOption)));
            cmsUploadLoadFile(pOption, tFileName, tFileType);

            $('#cmsUploadUploadBody .cmsUploadUploadSavePath').attr('data-save-path', tSavePath);
            $('#cmsUploadUploadBody .cmsUploadUploadSavePath .cmsUploadUploadSavePathDir').html('Save path: '+tSavePath);
            $('#cmsUploadUploadBody .cmsUploadUploadSavePath .cmsUploadUploadSavePathFile').attr('data-blob-url', '');

            cmsUploadDialog.getButton('btn-save').html('Insert');

            if (pExt) {
                pExt[2].close();
                cmsUploadDialog.getButton('btn-save').html('Save');
            }
        }
    } else if (pMode == 9) {
        //SELECT AND LOAD URL
        var tIndex = 0;

        $('#cmsUploadDialogContainer .nav-tabs li').removeClass('active');
        $('#cmsUploadDialogContainer .nav-tabs li:eq('+tIndex+')').addClass('active');

        $('#cmsUploadDialogContainer .cmsUploadDialogGroup').hide();
        $('#cmsUploadDialogContainer .cmsUploadDialogGroup[data-option="'+tIndex+'"]').css('display', 'inline-block');

        cmsUploadUpload(pObj, pId, 8, $('#cmsUploadFilePreview .cmsUploadFilePreviewPath').html());
    } else if (pMode == 10) {
    } else if (pMode == 11) {
    } else if (pMode == 12) {
        //WEB URL ON KEY CHANGE
        $('#cmsUploadWebURL .input-group-text').html('Loading...');
        $('#cmsUploadWebURL .input-group-text').attr('data-img-url-loaded', 0);
        $('#cmsUploadWebURL .input-group-text').unbind('click');

        var tImg = new Image();
        tImg.onload = function (e) {
            $('#cmsUploadWebURL .input-group-text').html('<i class="fa fa-upload" aria-hidden="true"></i>');
            $('#cmsUploadWebURL .input-group-text').css('cursor', 'pointer');
            $('#cmsUploadWebURL .input-group-text').unbind('click');
            $('#cmsUploadWebURL .input-group-text').on('click',
                function () {
                    cmsUploadUpload(this, pId, 14);
                }
            );
            $('#cmsUploadWebURL .input-group-text').attr('data-img-url-loaded', 1);
        }
        tImg.onerror = function (e) {
            //$('#cmsUploadWebURL .input-group-text').html('Paste an '+cmsUploadFileTypeCaption+' URL here:');
            $('#cmsUploadWebURL .input-group-text').html('<i class="fa fa-upload" aria-hidden="true"></i>');
            $('#cmsUploadWebURL .input-group-text').css('cursor', 'pointer');
            $('#cmsUploadWebURL .input-group-text').unbind('click');
            $('#cmsUploadWebURL .input-group-text').on('click',
                function () {
                    cmsUploadUpload(this, pId, 14);
                }
            );
            $('#cmsUploadWebURL .input-group-text').attr('data-img-url-loaded', 1);
        }
        tImg.src = $('#cmsUploadWebURLInput').val();

        $('#cmsUploadWebURL .cmsUploadWebURLPreview').css('background-image', 'url(\''+$('#cmsUploadWebURLInput').val()+'\')');
    } else if (pMode == 14) {
        //IMPORT FROM WEB URL
        var tName = cmsFnBaseName($('#cmsUploadWebURLInput').val());

        BootstrapDialog.show({
            type: BootstrapDialog.TYPE_INFO,
            title: 'Import '+cmsUploadFileTypeCaption,
            message: '\
                            <div>\
                                <label>Filename:</label>\
                                <input id="cmsUploadNewItem" type="text" class="form-control">\
                            </div>\
                        ',
            onshown: function () {
                $('#cmsUploadNewItem').val(decodeURIComponent(tName));
            },
            buttons: [{
                label: 'Ok',
                action: function(dialog) {
                    if ($('#cmsUploadNewItem').val().trim() == '') {
                        $('#cmsUploadNewItem').focus();
                        return false;
                    }

                    if (!cmsFnValidateFileName($('#cmsUploadNewItem').val().trim())) {
                        $('#cmsUploadNewItem').focus();
                        if ($('#cmsUploadNewItemInvalid')[0]) $('#cmsUploadNewItemInvalid').remove();
                        $('#cmsUploadNewItem').after('<span id="cmsUploadNewItemInvalid" style="display: inline-block; width: 100%; margin-top: 5px">Invalid filename.</span>');
                        return false;
                    }

                    var $button = this;
                    $button.disable();
                    $button.spin();

                    var form_data = new FormData();
                    form_data.append('cmsUploadWebURL', $('#cmsUploadWebURLInput').val());
                    form_data.append('CMS_POST_REQ', $('#'+pId).attr('cms-control-settings'));
                    form_data.append('cmsUploadTableId', $('.cms-form-primary-id').val());

                    $.ajax(
                        {
                            type: 'POST',
                            url: '',
                            data: form_data,
                            cache: false,
                            contentType: false,
                            processData: false
                        }
                    ).done(
                        function (data) {
                            var tIndex = 0;

                            $('#cmsUploadDialogContainer .nav-tabs li').removeClass('active');
                            $('#cmsUploadDialogContainer .nav.nav-tabs .nav-item:eq(1) a').removeClass('active');
                            $('#cmsUploadDialogContainer .nav.nav-tabs .nav-item:eq(0)').addClass('active');
                            $('#cmsUploadDialogContainer .nav.nav-tabs .nav-item:eq(0) a').addClass('active');
                            //$('#cmsUploadDialogContainer .nav-tabs li').removeClass('active');
                            //$('#cmsUploadDialogContainer .nav-tabs li:eq('+tIndex+')').addClass('active');

                            $('#cmsUploadDialogContainer .cmsUploadDialogGroup').hide();
                            $('#cmsUploadDialogContainer .cmsUploadDialogGroup[data-option="'+tIndex+'"]').css('display', 'inline-block');

                            var tFileExt = cmsFnFileExtension(data);
                            var tFileName = $('#cmsUploadNewItem').val().trim();
                            if (!cmsFnFileExtension(tFileName)) {
                                tFileName += '.'+tFileExt;
                            } else {
                                if (tFileName.indexOf(tFileExt) == -1) {
                                    tFileName += '.'+tFileExt;
                                }
                            }

                            cmsUploadUpload(pObj, pId, 8, cmsInfo['global']['UPLOADS_URL']+data /*cmsInfo["config"]['website']['path']+data*/,
                                [
                                    cmsInfo['global']['UPLOADS_URL']+((cmsControlSettings['upload_parent_dir']!='') ? cmsControlSettings['upload_parent_dir'] : ''), /*cmsInfo["config"]["website"]["path"]+'uploads'+((cmsControlSettings['upload_parent_dir']!='') ? '/'+cmsControlSettings['upload_parent_dir'] : '')*/
                                    tFileName,
                                    dialog,
                                    data
                                ]
                            );
                        }
                    );
                }
            }, {
                label: 'Cancel',
                action: function(dialog) {
                    dialog.close();
                }
            }],
            closable: false
        });
    }
}