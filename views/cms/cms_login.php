<?php
$dbConnection = new cmsDatabaseClass();

$hasGoogleCaptcha = (isset($CONFIG['website']['google']['g-captcha']['key']) && isset($CONFIG['website']['google']['g-captcha']['secret'])) ? (($CONFIG['website']['google']['g-captcha']['key']!="" && $CONFIG['website']['google']['g-captcha']['secret']!="") ? true : false) : false;

#$pathSSOLogin = (isset($CONFIG['cms']['sso']['login_path'])) ? $CONFIG['cms']['sso']['login_path'] : '';
#$captionSSOLogin = (isset($CONFIG['cms']['sso']['button_caption'])) ? $CONFIG['cms']['sso']['button_caption'] : 'SSO';

$strSSOSelection = '';
if (isset($CONFIG['cms']['sso'])) {
    if (count($CONFIG['cms']['sso']) == 1) {
        $strSSOSelection = '\
            <div class="form-group">\
                <a href="'.(isset($CONFIG['cms']['sso'][0]['oauth']['authorize_url']) ? $CONFIG['cms']['sso'][0]['oauth']['authorize_url'] : '#').'">Log in with your '.(isset($CONFIG['cms']['sso'][0]['name']) ? $CONFIG['cms']['sso'][0]['name'] : 'SSO').' account</a>\
            </div>\
        ';
    } else {
        $strSSOSelection = '\
            <div class="form-group">\
            Log in with:\
            <ul class="cms-sso-list">\
        ';
        foreach($CONFIG['cms']['sso'] as $Index => $Data) {
            $strSSOSelection .= '<li><a href="'.(isset($Data['oauth']['authorize_url']) ? $Data['oauth']['authorize_url'] : '#').'">'.(isset($Data['name']) ? $Data['name'] : 'SSO').'</a></li>';
         }
        $strSSOSelection .= '</ul></div>\
        ';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    @include('cms.cms_inc_head')
    <title>{!!$CONFIG['cms']['title']!!}</title>

    @if ($hasGoogleCaptcha)
    <script type="text/javascript" src="https://www.google.com/recaptcha/api.js?onload=recaptchaLoaded" async defer></script>
    @endif

    <style type="text/css">
        .cms-reset-form {
            display: none;
        }

        .cms-sso-list {
            list-style-type: none;
            padding-left: 3%;
        }
    </style>
</head>
<body>

@include('cms.cms_inc_body_end')

<script>
    @if (!$hasGoogleCaptcha)
        recaptchaLoaded();
    @endif

    function cmsFnLoginOpt(pObj) {
        var tOpt = $(pObj).attr('cms-data');
        $('.cms-alert-message').hide();
        if (tOpt == 0) {
            $('.cms-login-form').show();
            $('.cms-reset-form').hide();
            $(pObj).attr('cms-data', 1);
            $(pObj).html('Reset password');
            $('.cms-form-title').html(' {!!$CONFIG['cms']['title']!!} : Log in');
            $('.btn-primary').html('Login');
            $('#cms-login-option').val(0);
        } if (tOpt == 1) {
            $('.cms-login-form').hide();
            $('.cms-reset-form').show();
            $(pObj).attr('cms-data', 0);
            $(pObj).html('Back to login');
            $('.cms-form-title').html(' {!!$CONFIG['cms']['title']!!} : Reset password');
            $('.btn-primary').html('Reset');
            $('#cms-login-option').val(1);
        }
    }

    function recaptchaLoaded() {
        BootstrapDialog.show({
            title: '<i class="fas fa-user"></i><span class="cms-form-title"> {!!$CONFIG['cms']['title']!!} : Log in</span>',
            message: '\
            <div class="row cms-alert-message">\
                <div class="col-12">\
                    <div class="alert alert-danger">\
                        A simple success alert—check it out!\
                    </div>\
                </div>\
            </div>\
            <div class="form-group cms-login-form">\
                <label for="cmsUsername">Email</label>\
                <input type="email" class="form-control" id="cms-username" aria-describedby="emailHelp" placeholder="Enter your email" autocomplete="off">\
            </div>\
            <div class="form-group cms-login-form">\
                <label for="cmsPassword">Password</label>\
                <input type="password" class="form-control" id="cms-password" aria-describedby="emailHelp2" placeholder="Enter your password" autocomplete="off">\
            </div>\
            <div class="form-group cms-reset-form">\
                <label for="cmsUsername">Email</label>\
                <input type="email" class="form-control" id="cms-email-reset" aria-describedby="emailHelp" placeholder="Enter your email">\
            </div>\
            <div class="form-group captcha" data-key="{{(isset($CONFIG['website']['google']['g-captcha']['key']) ? $CONFIG['website']['google']['g-captcha']['key'] : '')}}">\
                <div id="g-cap-message"></div>\
            </div>\
            <div class="form-group">\
                <a href="javascript:void(0)" class="cms-btn-opt" onclick="cmsFnLoginOpt(this)" cms-data="1">Reset password</a>\
                <input type="hidden" id="cms-login-option" value="0">\
            </div><?=$strSSOSelection?>\
            ',
            onshown: function () {
                $('#cms-username').on('keypress',
                    function (e) {
                        var key = e.which;
                        if(key == 13)  // the enter key code
                        {
                            $('#cms-password').focus();
                            return false;
                        }
                    }
                );
                $('#cms-password').on('keypress',
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

                @if ($hasGoogleCaptcha)
                grecaptcha.render($('#g-cap-message')[0], {
                    'sitekey': $('.form-group.captcha').attr('data-key')
                });
                @endif
            },
            closable: false,
            buttons: [
                {
                    label: 'Login',
                    cssClass: 'btn-primary',
                    action: function (pDialog) {

                        var postData = {}

                        if ($('#cms-login-option').val() == 0) {
                            postData = {
                                type: 0,
                                username: $('#cms-username').val(),
                                password: $('#cms-password').val(),
                                @if ($hasGoogleCaptcha)
                                g_recaptcha_response: $('#g-recaptcha-response').val()
                                @endif
                            }
                        } else if ($('#cms-login-option').val() == 1) {
                            postData = {
                                type: 1,
                                username: $('#cms-email-reset').val(),
                                @if ($hasGoogleCaptcha)
                                g_recaptcha_response: $('#g-recaptcha-response').val()
                                @endif
                            }
                        }

                        $.ajax(
                            {
                                type: 'POST',
                                data: 'cmsPostData='+encodeURIComponent(base64_encode(json_encode(postData)))
                            }
                        ).done(
                            function (data) {
                                var arrRet = JSON.parse(data);
                                //console.log(arrRet)

                                if (arrRet['error']) {
                                    $('.alert').html(arrRet['message']);
                                    $('.alert').removeClass('alert-success');
                                    $('.alert').addClass('alert-danger');
                                    $('.cms-alert-message').show();
                                    @if ($hasGoogleCaptcha)
                                    grecaptcha.reset();
                                    @endif
                                } else {
                                    if (arrRet['type'] == 0) {
                                        window.location = arrRet['success'];
                                    } else if (arrRet['type'] == 1) {
                                        $('.alert').html(arrRet['message']);
                                        $('.alert').removeClass('alert-danger');
                                        $('.alert').addClass('alert-success');
                                        $('#cms-email-reset').val('');
                                        $('.cms-alert-message').show();
                                        @if ($hasGoogleCaptcha)
                                                grecaptcha.reset();
                                        @endif
                                    }
                                }
                            }
                        );
                    }
                }
            ]
        });
    }
</script>
</body>
</html>