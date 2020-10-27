var $cmsAssetImage = null;
var cmsAssetEditHistory = [];
var cmsAssetEditHistoryPointer = 0;
var cmsAssetFileInfo = {};
var cmsAssetDialog = null;
function cmsAssetUpload(pObj, pId, pMode, pOption, pExt, pExt2) {
    pOption = (typeof(pOption) != 'undefined') ? pOption : null;
    pExt = (typeof(pExt) != 'undefined') ? pExt : null;
    pExt2 = (typeof(pExt2) != 'undefined') ? pExt2 : null;

    var cmsControlSettings = json_decode(base64_decode($('#'+pId).attr('cms-control-settings')));
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
        'svg': {type: 'image', name: '', icon: ''},
        'pdf': {type: 'document', name: 'PDF File', icon: 'fa fa-file-pdf-o'},
        'xls': {type: 'document', name: 'Excel File', icon: 'fa fa-file-excel-o'},
        'xlsx': {type: 'document', name: 'Excel File', icon: 'fa fa-file-excel-o'},
        'xlsm': {type: 'document', name: 'Excel File', icon: 'fa fa-file-excel-o'},
        'doc': {type: 'document', name: 'Word Doc File', icon: 'fa fa-file-word-o'},
        'docx': {type: 'document', name: 'Word Doc File', icon: 'fa fa-file-word-o'},
        'ppt': {type: 'document', name: 'Power Point File', icon: 'fa-file-powerpoint-o'},
        'pptx': {type: 'document', name: 'Power Point File', icon: 'fa-file-powerpoint-o'},
        'mp4': {type: 'document', name: 'MP4', icon: 'fas fa-file-video'}
    }

    var cmsAssetUploadMimeType = {
        'image/jpeg': 'jpg',
        'image/gif': 'gif',
        'image/png': 'png',
        'image/tiff': 'tif',
        'image/x-icon': 'ico',
        'image/svg+xml': 'svg',
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
        $('#cmsAssetUploadBody .cmsAssetUploadContainer').empty();
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
                    tOpenFile = cmsControlSettings['asset_url']+tOpenFile;

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
                                        var tAssetFile = cmsFnAssetLocation(cmsControlSettings['asset_url'], $('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path') + '/' + cmsAssetFileInfo['upload_file']);
                                        tinymce.activeEditor.dom.setAttrib(img, 'src', $('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path') + '/' + cmsAssetFileInfo['upload_file']);
                                        tinyMCE.activeEditor.undoManager.add();
                                    }
                                } else {
                                    pExt.insertContent('<img src="'+$('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path') + '/' + cmsAssetFileInfo['upload_file']+'" cms-data="1" style="margin-left: 5px; margin-right: 5px">');
                                    //pExt.insertContent('<div style="float: left; margin: 0 10px 0 10px; width: 300px" cms-data="1"><img src="'+$('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path') + '/' + cmsAssetFileInfo['upload_file']+'" width="100%" cms-data="1"></div>');
                                    tinyMCE.activeEditor.undoManager.add();
                                }
                            } else if (cmsAssetIsInput) {
                                var tAssetFile = cmsFnAssetLocation(cmsControlSettings['asset_url'], $('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path') + '/' + cmsAssetFileInfo['upload_file']);
                                $('#' + pId).val(tAssetFile);
                                if (typeof(pExt) == 'string') eval(pExt);
                            } else if (cmsAssetIsCustom) {
                                pExt2($('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path') + '/' + cmsAssetFileInfo['upload_file']);
                            } else {
                                $('#' + pId + '_display').val($('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path') + '/' + cmsAssetFileInfo['upload_file']);
                                eval('' + pId + '_add_file();');

                                var tAssetFile = cmsFnAssetLocation(cmsControlSettings['asset_url'], $('#cmsAssetUploadBody .cmsAssetUploadSavePath').attr('data-save-path') + '/' + cmsAssetFileInfo['upload_file']);
                                $('#' + pId).val(tAssetFile);

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
                                            //imageSmoothingEnabled: false, imageSmoothingQuality: 'high'
                                            var isOdd = function (num) { return num % 2;}
                                            var arrDataImg = $cmsAssetImage.cropper('getImageData');
                                            console.log(arrDataImg);
                                            console.log((isOdd(arrDataImg.naturalWidth)) ? arrDataImg.naturalWidth-1 : arrDataImg.naturalWidth, (isOdd(arrDataImg.naturalHeight)) ? arrDataImg.naturalHeight-1 : arrDataImg.naturalHeight);

                                            arrDataImg.naturalWidth = (isOdd(arrDataImg.naturalWidth)) ? arrDataImg.naturalWidth-1 : arrDataImg.naturalWidth;
                                            arrDataImg.naturalHeight = (isOdd(arrDataImg.naturalHeight)) ? arrDataImg.naturalHeight-1 : arrDataImg.naturalHeight;

                                            $cmsAssetImage.cropper('setAspectRatio', arrDataImg.naturalWidth/arrDataImg.naturalHeight);

                                            $cmsAssetImage.cropper('getCroppedCanvas',
                                                    {
                                                        width: arrDataImg.naturalWidth,
                                                        height: arrDataImg.naturalHeight,
                                                        minWidth: arrDataImg.naturalWidth,
                                                        minHeight: arrDataImg.naturalHeight,
                                                        maxWidth: arrDataImg.naturalWidth,
                                                        maxHeight: arrDataImg.naturalHeight,
                                                        /*fillColor: '#fff',*/
                                                        imageSmoothingEnabled: false,
                                                        imageSmoothingQuality: 'high',
                                                    }
                                                ).toBlob(
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
                                        var tAspectRatio = ($cmsAssetImage.cropper('getData').width/$cmsAssetImage.cropper('getData').height).toFixed(2); //$cmsAssetImage.cropper('getImageData').aspectRatio.toFixed(2); //parseFloat(cmsAssetFileInfo['image_info'][0]/cmsAssetFileInfo['image_info'][1]).toFixed(2);
                                        if (tArr.length == 2) {
                                            tAspectRatioDefault = (parseInt(tArr[0],10) / parseInt(tArr[1],10)).toFixed(2); //tAspectRatioDefault = parseInt(tArr[0],10) / parseInt(tArr[1],10);

                                            if ($cmsAssetImage.cropper('getImageData').aspectRatio.toFixed(2) != tAspectRatioDefault) {
                                                if (!$cmsAssetImage.cropper('getCropBoxData').left) {
                                                    BootstrapDialog.alert('Please crop the image');
                                                    cmsAssetDialog.getButton('btn-save').stopSpin();
                                                    cmsAssetDialog.getButton('btn-save').enable();
                                                } else {
                                                    tFuncSaveImage();
                                                }
                                            } else {
                                                tFuncSaveImage();
                                            }
                                            /*if (tAspectRatio != tAspectRatioDefault) {
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
                                             }*/
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
                                    var tAspectRatio = ($cmsAssetImage.cropper('getData').width/$cmsAssetImage.cropper('getData').height).toFixed(2); //$cmsAssetImage.cropper('getImageData').aspectRatio.toFixed(2); //parseFloat(cmsAssetFileInfo['image_info'][0]/cmsAssetFileInfo['image_info'][1]).toFixed(2);
                                    if (tArr.length == 2) {
                                        tAspectRatioDefault = (parseInt(tArr[0],10) / parseInt(tArr[1],10)).toFixed(2); //tAspectRatioDefault = parseInt(tArr[0],10) / parseInt(tArr[1],10);

                                        if ($cmsAssetImage.cropper('getImageData').aspectRatio.toFixed(2) != tAspectRatioDefault) {
                                            if (!$cmsAssetImage.cropper('getCropBoxData').left) {
                                                BootstrapDialog.alert('Please crop the image');
                                                cmsAssetDialog.getButton('btn-save').stopSpin();
                                                cmsAssetDialog.getButton('btn-save').enable();
                                            } else {
                                                tFuncInsert();
                                            }
                                        } else {
                                            tFuncInsert();
                                        }
                                        /*if (tAspectRatio != tAspectRatioDefault) {
                                         BootstrapDialog.confirm('The image you want to insert does not match the required aspect ratio. You can crop the image to get the right size.<br><br>Are you sure you want to ignore the image aspect ratio?', function(result){
                                         if(result) {
                                         tFuncInsert();
                                         }
                                         });
                                         } else {
                                         tFuncInsert();
                                         }*/
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

            var reader = new FileReader();
            reader.addEventListener("load", function () {
                // convert image file to base64 string
                var arr = new Uint8Array(reader.result), i, len, length = arr.length, frames = 0;

                // make sure it's a gif (GIF8)
                if (arr[0] !== 0x47 || arr[1] !== 0x49 ||
                    arr[2] !== 0x46 || arr[3] !== 0x38)
                {
                    $.event.trigger({
                        type: "CMS_ASSET_CHECK_FILE",
                        data: null,
                        isGifAni: false
                    });
                    return;
                }

                //ported from php http://www.php.net/manual/en/function.imagecreatefromgif.php#104473
                //an animated gif contains multiple "frames", with each frame having a
                //header made up of:
                // * a static 4-byte sequence (\x00\x21\xF9\x04)
                // * 4 variable bytes
                // * a static 2-byte sequence (\x00\x2C) (some variants may use \x00\x21 ?)
                // We read through the file til we reach the end of the file, or we've found
                // at least 2 frame headers
                for (i=0, len = length - 9; i < len && frames < 2; ++i) {
                    if (arr[i] === 0x00 && arr[i+1] === 0x21 &&
                        arr[i+2] === 0xF9 && arr[i+3] === 0x04 &&
                        arr[i+8] === 0x00 &&
                        (arr[i+9] === 0x2C || arr[i+9] === 0x21))
                    {
                        frames++;
                    }
                }

                $.event.trigger({
                    type: "CMS_ASSET_CHECK_FILE",
                    data: null,
                    isGifAni: (frames > 0) ? true : false
                });
            }, false);
            reader.readAsArrayBuffer(file);

            $(document).on('CMS_ASSET_CHECK_FILE',
                function (pObj) {
                    if (pObj.isGifAni) {
                        cmsAssetLoadFile('', file.name, file.type);
                    } else {
                        if (/^image\/\w+$/.test(file.type)) {
                            var uploadedImageURL = URL.createObjectURL(file);
                            cmsAssetLoadImage(uploadedImageURL, file.name, file.type);
                        } else {
                            cmsAssetLoadFile('', file.name, file.type);
                        }
                    }
                }
            );
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