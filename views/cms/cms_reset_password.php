<?php
$dbConnection = new cmsDatabaseClass();
?>
<!doctype html>
<html lang="en">
<head>
    @include('cms.cms_inc_head')
    <title>{!!$CONFIG['cms']['title']!!}</title>
    <style type="text/css">
    </style>
</head>
<body>
@include('cms.cms_inc_body_end')
<script>
    recaptchaLoaded();

    function recaptchaLoaded() {
        BootstrapDialog.show({
            title: '<i class="fas fa-user"></i><span class="cms-form-title"> {!!$CONFIG['cms']['title']!!} : Password Reset</span>',
            @if (!isset($arrValidation["error"]))
            message: '\
            <div class="row cms-alert-message">\
                <div class="col-12">\
                    <div class="alert alert-danger">\
                        A simple success alert—check it out!\
                    </div>\
                </div>\
            </div>\
            <div class="form-group cms-login-form">\
                <label for="cmsPassword">Password</label>\
                <input type="password" class="form-control" id="cms-password" aria-describedby="emailHelp2" placeholder="Enter your password">\
            </div>\
            <div class="form-group cms-login-form">\
                <label for="cmsPassword">Confirm Password</label>\
                <input type="password" class="form-control" id="cms-password-confirm" aria-describedby="emailHelp2" placeholder="Enter your confirm password">\
            </div>\
            <div class="form-group cms-login-form">\
                Password Rules:<br>\
                At least 12 characters<br>\
                At least one capital and one lower case letter\
            </div>\
            ',
            onshown: function () {
                $('#cms-password').on('keypress',
                    function (e) {
                        var key = e.which;
                        if(key == 13)  // the enter key code
                        {
                            $('#cms-password-confirm').focus();
                            return false;
                        }
                    }
                );

                $('#cms-password-confirm').on('keypress',
                    function (e) {
                        var key = e.which;
                        if(key == 13)  // the enter key code
                        {
                            $('.btn.btn-primary').focus();
                            $('.btn.btn-primary').click();
                            return false;
                        }
                    }
                );
            },
            buttons: [
                {
                    label: 'Submit',
                    cssClass: 'btn-primary',
                    action: function (pDialog) {
                        var postData = {
                            password: $('#cms-password').val(),
                            password_confirm: $('#cms-password-confirm').val(),
                            data: '<?=$_GET["data"]?>'
                        }
                        $.ajax(
                            {
                                type: 'POST',
                                data: 'cmsPostData='+encodeURIComponent(base64_encode(json_encode(postData)))
                            }
                        ).done(
                            function (data) {
                                var arrRet = JSON.parse(data);
                                console.log(arrRet)

                                if (arrRet['error']) {
                                    $('.alert').html(arrRet['message']);
                                    $('.alert').removeClass('alert-success');
                                    $('.alert').addClass('alert-danger');
                                    $('.cms-alert-message').show();
                                } else {
                                    $('.alert').html(arrRet['message']);
                                    $('.alert').removeClass('alert-danger');
                                    $('.alert').addClass('alert-success');
                                    $('.cms-alert-message').show();
                                    setTimeout(
                                        function () {
                                            window.location = arrRet['url'];
                                        }, 3000
                                    )
                                }
                            }
                        );
                    }
                }
            ],
            @endif
            @if (isset($arrValidation["error"]))
            message: '<?=$arrValidation["message"]?>',
            @endif
            closable: false,
        });
    }
</script>
</body>
</html>