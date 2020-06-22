<!-- Optional JavaScript -->
<!-- jQuery first, then Popper.js, then Bootstrap JS -->
<script src="{{VENDORS_URL}}popper/popper.min.js"></script>
<script src="{{VENDORS_URL}}bootstrap/4.3.1/js/bootstrap.min.js"></script>

<script src="{{VENDORS_URL}}bootstrap4-dialog/js/bootstrap-dialog.min.js"></script>

<script src="{{VENDORS_URL}}moment/2.18.1/moment.js"></script>

<script src="{{VENDORS_URL}}jquery.ui/jquery-ui.min.js"></script>
<script src="{{VENDORS_URL}}jquery.ui.touch/jquery.ui.touch-punch.min.js"></script>

<script src="{{RES_CMS_URL}}js/global.js"></script>

@php
    $arrCustomCSS = glob(RESPATH_WWW."js/custom.cms.*.js");
    foreach($arrCustomCSS as $Index => $File) {
        print '<script src="'.RES_URL.'js/'.basename($File).'"></script>';
    }
@endphp