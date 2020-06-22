<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=$CONFIG['cms']['title']?></title>
    <!-- Bootstrap -->
    <link href="<?=VENDORS_URL?>bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Fonts -->
    <link href="<?=VENDORS_URL?>font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="<?=VENDORS_URL?>jquery/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="<?=VENDORS_URL?>bootstrap/js/bootstrap.min.js"></script>

    <link href="<?=VENDORS_URL?>jquery.ui/jquery-ui.theme.css" rel="stylesheet">
    <script src="<?=VENDORS_URL?>jquery.ui/jquery-ui.js"></script>

    <script src="<?=VENDORS_URL?>jquery.ui.touch/jquery.ui.touch-punch.min.js"></script>

    <script src="<?=VENDORS_URL?>muuri/web-animations-2.3.1.min.js"></script>
    <script src="<?=VENDORS_URL?>muuri/hammer-2.0.8.min.js"></script>
    <script src="<?=VENDORS_URL?>muuri/muuri-0.5.4.js"></script>

    <script src="<?=RES_CMS_URL?>js/common.js"></script>

    <script src="<?=VENDORS_URL?>moment.js/moment.js"></script>

    <style type="text/css">
        .container {
            margin-top: 20px;
        }
        .grid {
            position: relative;
            margin-top: 20px;
        }
        .item {
            display: block;
            
            position: relative;
            float: left;
            
            z-index: 1;
            background: #c0c0c0;
            color: #fff;

            background-repeat: no-repeat;
            background-size: cover;
            background-position: center;
        }
        .item.muuri-item-dragging {
            z-index: 3;
        }
        .item.muuri-item-releasing {
            z-index: 2;
        }
        .item.muuri-item-hidden {
            z-index: 0;
        }
        .item-content {
            position: absolute;
            width: 100%;
            /*height: 100%;*/
            left: 0px;
            bottom: 5%;
            z-index: 100;
            background-color: rgba(0, 0, 0, .5);
            padding: 2%;
            font-size: 11px;
        }

        #cmsFileClear, #cmsFileUpload, .cms-btn-back-2 {
            display: none;
            margin-right: 10px;
        }

        #cmsFile {
            display: inline-block;
            width: 94%;
            height: 200px;
            margin-right: 1%;
            background-color: transparent;
            border: 2px dashed #c0c0c0;
        }

        .footer {
            position: relative;
            margin-top: 20px;
        }

        #cmsFileContainer {
            position: relative;
        }

        #cmsFileContainer .caption {
            position: absolute;
            color: #c0c0c0;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 320px;
            height: 20px;
            margin: auto;
        }
    </style>


</head>
<body>
    <div class="container">
        <div id="cmsFileContainer"><div class="caption">You can drag and drop photos here to add them</div><input type="file" id="cmsFile" class="form-control" accept=".jpg" multiple><button class="btn btn-default cms-btn-back-1" style="display: inline-block; width: 5%" onclick="window.location = window.location.pathname">Back</button></div>
        <button id="cmsFileClear" class="btn btn-default">Clear</button><button class="btn btn-default cms-btn-back-2" onclick="window.location = window.location.pathname">Back</button>
        <div class="grid"></div>
        <div class="footer"></div>
    </div>

    <script>
        var uploadedFileCounter = 0;
        var uploadedFileIndex = 0;
        var uploadedFileLength = 0;

        var uploadedConfirmed = 0;

        function handleFileSelect(evt) {

            var files = evt.target.files; // FileList object
            //console.log(files);

            // Loop through the FileList and render image files as thumbnails.

            var containerLength = parseInt($('.grid').width()/8, 10);

            uploadedFileLength = files.length;

            for (var i = 0, f; f = files[i]; i++) {
                files[i]['cms_index'] = i;

                // Only process image files.
                if (!f.type.match('image.*')) {
                    continue;
                }

                var reader = new FileReader();

                var tThumb = '<div class="item cms-'+i+'" cms-data-index="'+i+'" style="width: '+containerLength+'px; height: '+containerLength+'px"><div class="item-content">Uploading <span></span></div></div>';
                $('.grid').append(tThumb);

                // Closure to capture the file information.
                reader.onload = (
                    function(theFile) {
                        return function(e) {
                            // Render thumbnail.
                            var tThumb = '';

                            var imgTitle = escape(theFile.name);

                            $('.item.cms-'+theFile.cms_index).css(
                                {
                                    'background-image': 'url(\''+e.target.result+'\')'
                                }
                            );
                            $('.item.cms-'+theFile.cms_index).attr('title', imgTitle);

                            if (uploadedFileCounter == (uploadedFileLength-1)) {
                                $.event.trigger({
                                    type: "CMS_BATCH_UPLOAD_POPULATED",
                                    data: {}
                                });
                            }

                            //console.log(theFile.cms_index);
                            //console.log('Index:'+uploadedFileIndex+' '+uploadedFileCounter+'=='+uploadedFileLength);

                            uploadedFileCounter++;
                        };
                    }
                )(f);

                uploadedFileIndex = i;

                // Read in the image file as a data URL.
                reader.readAsDataURL(f);
            }


            $('#cmsFile').hide();
            $('#cmsFileContainer .caption').hide();
            $('#cmsFileClear').show();
            $('.cms-btn-back-1').hide();
            $('.cms-btn-back-2').show();

            $('h5').hide();

            $('.cms-btn-back-2').after('\
                    <div id="batchProgress" style="display: inline-block; width: 100%; height: 5px; background-color: #c0c0c0"><div style="display: block; width: 0%; height: 5px; background-color: #000"></div></div>\
            ');
        }

        document.getElementById('cmsFile').addEventListener('change', handleFileSelect, false);

        var grid = null;

        $(document).on('CMS_BATCH_UPLOAD_POPULATED',
            function () {

                setTimeout(
                    function () {
                        var file_data = $('#cmsFile').prop("files");

                        $.each(file_data,
                            function (pIndex, pObj) {

                                //console.log(pObj);

                                var arrDataOrder = [];
                                arrDataOrder[arrDataOrder.length] = 0;

                                var form_data = new FormData();
                                form_data.append('cmsFile', pObj);
                                form_data.append('cmsBatchUpload', 'true');
                                form_data.append('cmsBatchUploadOrder', json_encode(arrDataOrder));

                                $.ajax(
                                    {
                                        xhr: function() {
                                            var xhr = new window.XMLHttpRequest();
                                            xhr.upload.addEventListener("progress", function(evt) {
                                                if (evt.lengthComputable) {
                                                    var percentComplete = evt.loaded / evt.total;
                                                    //Do something with upload progress here
                                                    var progressCounter = parseInt(percentComplete*100, 10);
                                                    //$('.spUploadProgress[data-field="'+targetField+'"]').html('Uploading '+progressCounter+'%. Please wait...');

                                                    $('.item.cms-'+pIndex).find('.item-content span').html(progressCounter+'%');
                                                    //console.log(pIndex+' : '+progressCounter);
                                                }
                                            }, false);

                                            xhr.addEventListener("progress", function(evt) {
                                                if (evt.lengthComputable) {
                                                    var percentComplete = evt.loaded / evt.total;
                                                    //Do something with download progress
                                                    var progressCounter = parseInt(percentComplete*100, 10);
                                                    //$('.spUploadProgress[data-field="'+targetField+'"]').html('Uploading '+progressCounter+'%. Please wait...');

                                                    $('.item.cms-'+pIndex).find('.item-content span').html(progressCounter+'%');
                                                    //console.log(pIndex+' : '+progressCounter);
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
                                        success: function(){
                                            $('.item.cms-'+pIndex).find('.item-content').html('Uploaded');

                                            $('#batchProgress div').css('width', (((uploadedConfirmed+1)/uploadedFileLength)*100)+'%');
                                        }
                                    }
                                ).done(
                                    function (data) {
                                        //var postReturn = JSON.parse(data);

                                        console.log(data);

                                        $('.item.cms-'+pIndex).attr('cms-data-id', data);

                                        if (uploadedConfirmed == (uploadedFileLength-1)) {
                                            cmsBatchUploadSortOrder();
                                        }

                                        uploadedConfirmed++;
                                    }
                                );
                            }
                        );

                    }, 1000
                );

                $('.grid .item').css(
                    {
                        'position': 'absolute',
                        'float': 'none'
                    }
                );

                grid = new Muuri('.grid',
                    {
                        dragEnabled: true
                    }
                );

                grid.on('dragEnd', function (item, event) {
                    cmsBatchUploadSortOrder();
                });

            }
        );

        function cmsBatchUploadSortOrder() {
            var arrDataOrder = [];

            var file_data = $('#cmsFile').prop("files");

            $.each(file_data,
                function (pIndex, pObj) {
                    arrDataOrder[arrDataOrder.length] = [$(grid.getItems()[pIndex].getElement()).attr('cms-data-index'), $(grid.getItems()[pIndex].getElement()).attr('cms-data-id')];
                }
            );

            console.log(arrDataOrder);

            $.ajax(
                {
                    type: 'POST',
                    url: '',
                    data: 'cmsBatchUploadOrder='+json_encode(arrDataOrder)
                }
            ).done(
                function (data) {

                }
            );
        }

        $('#cmsFileClear').on('click',
            function () {
                $('#cmsFileContainer').empty();
                $('.grid').empty();
                $('#cmsFileContainer').append('<div class="caption">You can drag and drop photos here to add them</div><input type="file" id="cmsFile" class="form-control" multiple><button class="btn btn-default cms-btn-back-1" style="display: inline-block; width: 5%" onclick="window.location = window.location.pathname">Back</button>');
                document.getElementById('cmsFile').addEventListener('change', handleFileSelect, false);
                $('#cmsFileClear').hide();
                $('.cms-btn-back-2').hide();
                $('#batchProgress').remove();
                $('#cmsFileContainer .caption').show();
            }
        );
    </script>
</body>
</html>