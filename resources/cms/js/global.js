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
function cmsFnfileName(str) {
    if (typeof str !== 'string') return;
    var frags = str.split('.')
    return frags.splice(0,frags.length-1).join('.');
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
function cmsFnAssetLocation(pAssetDir = '', pFile = '') {
    var strRet = '';
    strRet = pFile.replace(pAssetDir, '');
    return strRet;
}