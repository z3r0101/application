<?php
header("Content-Type: text/javascript")
?>
    $(document).ready(
        function () {
            $('.panel-collapse').on('shown.bs.collapse',
                function (e) {
                    if ($(e.target.parentElement).attr('class') == 'panel panel-default') {
                        var $panel = $(this).closest('.panel');
                        $('html,body').animate({
                            scrollTop: $panel.offset().top
                        }, 0);
                    }
                }
            );
            $('#cms-content-loader').hide();
            $('#cms-content .cms-container').show();

            $( function() {
                $('.primary-panel.panel-heading a').click( function() {
                    var tId = $(this).parents('.primary-panel.panel-heading').attr('id');
                    //console.log(tId);
                    $('.primary-panel.panel-heading:not([id="'+tId+'"])').removeClass('on');

                    if ($(this).parents('.primary-panel.panel-heading').hasClass('on')) {
                        $(this).parents('.primary-panel.panel-heading').removeClass('on');
                        //console.log('on removed');
                    } else {
                        $(this).parents('.primary-panel.panel-heading').addClass('on');
                        //console.log('on added');
                    }

                    setTimeout(
                        function () {
                            $('#cms-side-menu').height($(document).height()-($('#cms-nav').outerHeight()-15));
                        }, 600
                    )
                } )
            });
        }
    );

    var CMS_REPEATER_DELETE = {};

    function fnCreatePostVal() {
        var cmsPost = {'primary': {}, 'datatable': {}, 'repeater': {}};

        $('.cms-alert-message').removeClass('show');

        $('.cms-form-control').each(
            function (pIndex, pObj) {
                //console.log(pObj);
                cmsPost['primary'][$(pObj).attr('id')] = (!CMS_CONTROLS[$(pObj).attr('id')]) ? $(pObj).val() : CMS_CONTROLS[$(pObj).attr('id')];
            }
        );

        $('.cms-form-control-datatable').each(
            function (pIndex, pObj) {

                var arrData = [];
                var dataTableSet = $('#'+$(pObj).attr('id')+'_table').DataTable().rows().data();

                for(var i=0; i<dataTableSet.length; i++) {
                    arrData[i] = dataTableSet[i];
                }

                cmsPost['datatable'][$(pObj).attr('id')] = arrData;
            }
        );

        return cmsPost;
    }

    var cmsIsFormChanges = false;
    $(document).ready(
        function () {
            $('#postTemp').val( base64_encode(json_encode(fnCreatePostVal())) );

            $('input').change(function() {
                cmsIsFormChanges = true;
            });
            $('select').change(function() {
                cmsIsFormChanges = true;
            });
            $('textarea').change(function() {
                cmsIsFormChanges = true;
            });

            window.onbeforeunload = function(){
                if(cmsIsFormChanges) {
                    return 'You haven\'t saved your changes.';
                }
            };
        }
    );

    function cmsFnUnloadPage() {
    }

    var tDialog = null;
    function cmsSave(pOnSaveMessage, pSaveCallBack, pIfSuccessRedirect) {
        pOnSaveMessage = (pOnSaveMessage || 'Saving form. Please wait.');
        pSaveCallBack = (pSaveCallBack || null);
        pIfSuccessRedirect = (pIfSuccessRedirect || null)

        $.event.trigger({
            type: "CMS_POST_BEFORE"
        });

        var cmsPost = fnCreatePostVal();

        //console.log(cmsPost); return false;
        //return false;
        //console.log(base64_encode(json_encode(cmsPost)));
        //console.log(base64_encode(json_encode(cmsPost))); return false;

        tDialog = BootstrapDialog.show({
            type: BootstrapDialog.TYPE_INFO,
            title: `<?=$CONFIG['cms']['title']?>`,
            message: pOnSaveMessage,
            closable: false
        });

        //return false;

        <?php
        $save_message = (isset($self->formLayoutData->body["save_message"])) ? $self->formLayoutData->body["save_message"] : 'Form saved.';

        $save_message_action = (isset($self->formLayoutData->body["save_message_action"])) ? intval($self->formLayoutData->body["save_message_action"]) : 0;

        $save_message_button_new_caption = (isset($self->formLayoutData->body["save_message_button_new_caption"])) ? $self->formLayoutData->body["save_message_button_new_caption"] : 'Create new';
        $save_message_autoclose = (isset($self->formLayoutData->body["save_message_autoclose"])) ? filter_var(strval($self->formLayoutData->body["save_message_autoclose"]), FILTER_VALIDATE_BOOLEAN) : true;
        $save_message_saveclose = strval($self->formLayoutData->body["saveclose"]);

        $save_redirect = strval($self->formLayoutData->body["save_redirect"]);
        ?>

        $.ajax(
            {
                type: 'POST',
                url: '',
                cache: false,
                data: 'cmsPost='+encodeURIComponent(base64_encode(json_encode(cmsPost)))+'&id='+$('.cms-form-primary-id').first().val()+'&postTemp='+encodeURIComponent($('#postTemp').val())
            }
        ).done(
            function (data) {
                console.log(data);
                //return false;

                var retData = JSON.parse(data);
                retData['dialog'] = tDialog;

                cmsIsFormChanges = false;

                if (!pSaveCallBack) {
                    if (retData['error']) {
                        setTimeout(
                            function () {
                                tDialog.close();
                            }, 800
                        );
                        $('.cms-alert-message div[role="alert"]').html('<ul>'+retData['error']+'</ul>');
                        $('.cms-alert-message div[role="alert"]').removeClass().addClass('alert alert-danger');
                        $('.cms-alert-message').addClass('show');
                        $(window).scrollTop(0);
                        return false;
                    }

                    var primaryId = JSON.parse(data)['primaryId'];
                    var selectedUrlPath = JSON.parse(data)['selectedUrlPath'];
                    var redirect = JSON.parse(data)['redirect'];

                    $('.cms-form-primary-id').first().val(primaryId['value'])


                    if (JSON.parse(data)['alert'].length == 0) {

                        if (pIfSuccessRedirect) {
                            window.location = pIfSuccessRedirect;
                            return false;
                        }

                        if ('<?=$save_message_saveclose?>'=='false') {

                            setTimeout(
                                function () {
                                    tDialog.close();
                                }, 800
                            );

                            $('.cms-alert-message div[role="alert"]').html('<?=$save_message?>');
                            $('.cms-alert-message div[role="alert"]').removeClass().addClass('alert alert-success');
                            $('.cms-alert-message').addClass('show');
                            $(window).scrollTop(0);

                            if ('<?=$save_redirect?>'=='true') {
                                setTimeout(
                                    function () {
                                        window.location = (redirect=='') ? selectedUrlPath+'/list' : redirect;
                                    }, 1000
                                );
                            }

                        } else {
                            var counter = 10;
                            var closeLabel = 'Exit';

                            //console.log(tDialog);
                            tDialog.setMessage('<?=$save_message?>'.replace(/\[id\]/g, retData['primaryId']['value']));
                            tDialog.setType(BootstrapDialog.TYPE_SUCCESS);
                            tDialog.setButtons(
                                [
                                    <?php
                                    if ($save_message_action == 0) {
                                    ?>
                                    {
                                        id: 'cms-dialog-new',
                                        label: '<?=$save_message_button_new_caption?>',
                                        action: function(dialog) {
                                            <?php
                                                $tQueryString = '';
                                                if (isset($_SERVER['QUERY_STRING'])) {
                                                    $tArr = explode('&', $_SERVER['QUERY_STRING']);
                                                    if (isset($tArr[0])) unset($tArr[0]);
                                                    if (isset($tArr[1])) unset($tArr[1]);
                                                    if (isset($tArr[2])) unset($tArr[2]);
                                                    $tQueryString = implode('&', $tArr);
                                                }
                                            ?>
                                            window.location = selectedUrlPath+'/post<?=($tQueryString!='') ? '?'.$tQueryString : ''?>';
                                        }
                                    }
                                    <?php
                                    } else if ($save_message_action == 1) {
                                    ?>
                                    {
                                        id: 'cms-dialog-continue-edit',
                                        label: 'Continue editing',
                                        action: function(dialog) {
                                            $('#'+retData['primaryId']['name']).val(retData['primaryId']['value']);

                                            dialog.close();
                                        }
                                    },
                                    {
                                        id: 'cms-dialog-new',
                                        label: '<?=$save_message_button_new_caption?>',
                                        action: function(dialog) {
                                            <?php
                                                $tQueryString = '';
                                                if (isset($_SERVER['QUERY_STRING'])) {
                                                    $tArr = explode('&', $_SERVER['QUERY_STRING']);
                                                    if (isset($tArr[0])) unset($tArr[0]);
                                                    if (isset($tArr[1])) unset($tArr[1]);
                                                    if (isset($tArr[2])) unset($tArr[2]);
                                                    $tQueryString = implode('&', $tArr);
                                                }
                                            ?>
                                            window.location = selectedUrlPath+'/post<?=($tQueryString!='') ? '?'.$tQueryString : ''?>';
                                        }
                                    }
                                    <?php } ?>
                                    ,
                                    {
                                        id: 'cms-dialog-close',
                                        label: <?=($save_message_autoclose) ? "closeLabel+' ('+counter+')'" : "closeLabel"?>,
                                        action: function(dialog) {
                                            window.location = (redirect=='') ? selectedUrlPath+'/list' : redirect;
                                        }
                                    }
                                ]
                            );

                            <?php if ($save_message_autoclose): ?>
                            setTimeout(
                                function () {
                                    var interval = setInterval(function() {
                                        counter--;
                                        $('#cms-dialog-close').html(closeLabel+' ('+counter+')');
                                        if (counter == 0) {
                                            clearInterval(interval);
                                            window.location = (redirect=='') ? selectedUrlPath+'/list' : redirect;
                                        }
                                    }, 1000);

                                }, 200
                            );
                            <?php endif ?>

                        }

                        $.event.trigger({
                            type: "CMS_POST_SAVED",
                            data: retData,
                            postReturn: retData
                        });

                    } else {
                        setTimeout(
                            function () {
                                tDialog.close();
                            }, 800
                        );

                        var tArr = [];
                        $.each(JSON.parse(data)['alert'],
                            function (pIndex, pAlert) {
                                tArr[tArr.length] = '<li>'+pAlert+'</li>';
                            }
                        );

                        $('.cms-alert-message div[role="alert"]').html('<ul>'+tArr.join('')+'</ul>');
                        $('.cms-alert-message div[role="alert"]').removeClass().addClass('alert alert-danger');
                        $('.cms-alert-message').addClass('show');
                        $(window).scrollTop(0);

                        $.event.trigger({
                        type: "CMS_POST_ERROR",
                            data: tArr,
                            postReturn: tArr
                        });
                    }
                } else {
                    pSaveCallBack(retData);
                }

            }
        );
    }
    $('.cms-button.save').on('click', function () { cmsSave(); });

    function cmsCancel() {
        window.location = '<?=($self->postRedirect=='') ? $self->selectedUrlPath.'/list' : $self->postRedirect?>';
    }
    $('.cms-button.cancel').on('click', cmsCancel);