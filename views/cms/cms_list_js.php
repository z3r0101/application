<?php
    header("Content-Type: text/javascript")
?>

var CMS_DATATABLE_SELECT_DATA = {};
var CMS_DATATABLE_ORDER_DATA = [];
var CMS_DATATABLE = {};

var dataTableAction = JSON.parse('<?=json_encode(@$self->dataTableAction, true)?>');

function cmsFnListRefresh() {
    $.each(CMS_DATATABLE,
        function (pIndex, pObj) {
            pObj.ajax.reload(null, false);
        }
    )
}

function cmsFnListActionPost(pObj) {
    var table_id = $(pObj).attr('data-table');

    var data = {
        'table_id': table_id,
        'button_id': $(pObj).attr('data-button'),
        'value': $(pObj).attr('data-id')
    };

    /*var formActionId = data['table_id']+'_'+data['button_id']+'_'+data['value'];
    $('body').append('<form id="'+formActionId+'" method="post"><input type="hidden" name="cmsListPostVal"></form>');
    $('#'+formActionId+' input').val(JSON.stringify(data));
    $('#'+formActionId).submit();*/

    $.ajax(
        {
            type: 'POST',
            url: '',
            data: 'cmsListPostVal='+JSON.stringify(data)
        }
    ).done(
        function (data) {
            console.log(data);

            $.each(CMS_DATATABLE,
                function (pIndex, pObj) {
                    pObj.ajax.reload(null, false);
                }
            )

            CMS_DATATABLE_SELECT_DATA = {};
        }
    );

}

function cmsFnListActionDelete(pObj) {
    BootstrapDialog.confirm(
        {
            type: BootstrapDialog.TYPE_WARNING,
            title: `<?=$CONFIG['cms']['title']?>`,
            message: 'You are about to delete a record. This cannot be undone. Are you sure?',
            callback: function (result) {
                if (result) {

                    var data = {
                        'table_id': $(pObj).attr('data-table'),
                        'value': $(pObj).attr('data-id')
                    };

                    $.ajax(
                        {
                            type: 'POST',
                            url: '',
                            data: 'cmsListDeleteVal='+JSON.stringify(data)
                        }
                    ).done(
                        function (data) {
                            console.log(data);
                            $.each(CMS_DATATABLE,
                                function (pIndex, pObj) {
                                    pObj.ajax.reload(null, false);
                                }
                            );

                            $.event.trigger({
                                type: "CMS_LIST_DELETE",
                                data: data
                            });
                        }
                    );

                    /*var formActionId = data['table_id']+'_'+data['value'];
                    $('body').append('<form id="'+formActionId+'" method="post"><input type="hidden" name="cmsListDeleteVal"></form>');
                    $('#'+formActionId+' input').val(JSON.stringify(data));
                    $('#'+formActionId).submit();*/
                }
            }
        }
    );
}

function cmsFnDataTableSelect(pObj) {
    if ($(pObj)[0].checked) {
        //console.log(json_decode(base64_decode($(pObj).attr('cms-row-data'))));
        var arrData = json_decode(base64_decode($(pObj).attr('cms-row-data')));
        CMS_DATATABLE_SELECT_DATA['row_'+$(pObj).val()] = [$(pObj).val(), $(pObj).parents('tr').find('td:not(.cms-extended-col):eq(0)').html(), arrData];
    } else {
        delete CMS_DATATABLE_SELECT_DATA['row_'+$(pObj).val()];
    }
}

$('.cms-batch-upload').on('click',
    function () {
        window.location = '?cms-batch-upload';
    }
);

$('.cms-button.add').on('click',
    function () {
        window.location = ($(this).attr('cms-url')) ? $(this).attr('cms-url') : cmsInfo['config']['website']['path'] + cmsInfo['config.cms']['route_name'] + '/' + cmsInfo['route']['selectedUrlClass'] + '/' + cmsInfo['route']['selectedUrlMethod'] + '/post';
        //window.location = ($(this).attr('cms-url')) ? $(this).attr('cms-url') : cmsInfo['config']['website']['path'] + cmsInfo['config']['website']['path'] + 'post';
    }
);

$('.cms-button.delete').on('click',
    function () {
        var table_id = $(this).attr('cms-target-datatable');

        var button_id = $(this).attr('id').split('_')[1];

        var tVal = [];
        var tCaption = [];
        $.each(CMS_DATATABLE_SELECT_DATA,
            function (pIndex, pObj) {
                console.log(pObj);
                tVal[tVal.length] = pObj[0];

                var tCol = [];
                $('#'+table_id+' th').each(
                    function (pSubIndex, pSubObj) {
                        if ($(pSubObj).attr('cms-fieldname')) {
                            console.log($(pSubObj).attr('cms-fieldname'));
                            tCol[tCol.length] = pObj[2][$(pSubObj).attr('cms-fieldname')];
                        }
                    }
                );

                /*console.log(tCol);
                var strCol = '<div class="cms-row">';
                $.each(tCol,
                    function (pSubIndex, pSubObj) {
                        strCol += '<div class="cms-col cms-col-3">'+pSubObj+'</div>';
                    }
                )
                strCol += '</div>';
                tCaption[tCaption.length] = '<li style="padding-bottom: 10px"><table width="100%">'+strCol+'</table></li>';*/

                tCaption[tCaption.length] = '<li style="padding-bottom: 10px">'+pObj[1]+'</li>';
            }
        );

        var data = {
            'table_id': table_id,
            'value': tVal
        };

        if (Object.keys(CMS_DATATABLE_SELECT_DATA).length==0) {
            BootstrapDialog.alert(
                {
                    type: BootstrapDialog.TYPE_WARNING,
                    title: `<?=$CONFIG['cms']['title']?>`,
                    message: 'No selected data.'
                }
            );
            return false;
        }


        $('#cms-content .cms-row:not(:eq(0))').hide();
        $('#cms-content .cms-row:last').after('\
            <div class="cms-row delete-confirm">\
                <div class="cms-col cms-col-12">\
                    <ul>\
                    '+tCaption.join('')+'\
                    </ul>\
                    <div class="cms-line-border"></div>\
                    <div class="cms-datatable-confirm-delete"><input type="checkbox"> Confirm Deletion</div>\
                    <div class="cms-line-border"></div>\
                    <a href="javascript:void(0)" class="btn btn-default cms-button confirm-ok" cms-target-datatable="'+$(this).attr('cms-target-datatable')+'">Ok</a> <a href="javascript:void(0)" class="btn btn-default cms-button confirm-cancel">Cancel</a>\
                </div>\
            </div>\
        ');
        $('#cms-content .cms-row:last').show();

        $('.cms-button.confirm-ok').on('click',
            function () {
                if (!$('.cms-datatable-confirm-delete input')[0].checked) {

                    BootstrapDialog.alert(
                        {
                            type: BootstrapDialog.TYPE_WARNING,
                            title: `<?=$CONFIG['cms']['title']?>`,
                            message: 'You must confirm deletion.'
                        }
                    );

                    return false;
                }

                var table_id = $(this).attr('cms-target-datatable');

                $.ajax(
                    {
                        type: 'POST',
                        url: '',
                        data: 'cmsButtonDeleteVal='+JSON.stringify(data)
                    }
                ).done(
                    function (data) {
                        console.log(data);

                        CMS_DATATABLE[table_id].ajax.reload(null, false);

                        CMS_DATATABLE_SELECT_DATA = {};

                        $('#cms-content .cms-row.delete-confirm').remove();
                        $('#cms-content .cms-row').removeAttr('style');
                    }
                );
            }
        );
        $('.cms-button.confirm-cancel').on('click',
            function () {
                $('#cms-content .cms-row.delete-confirm').remove();
                $('#cms-content .cms-row').removeAttr('style');
            }
        );

    }
);

$('.cms-button.post').on('click',
    function () {
        var table_id = $(this).attr('id').split('_')[0];
        var button_id = $(this).attr('id').split('_')[1];

        var tVal = [];
        $.each(CMS_DATATABLE_SELECT_DATA,
            function (pIndex, pObj) {
                tVal[tVal.length] = pObj[0];
            }
        );

        var data = {
            'table_id': table_id,
            'button_id': button_id,
            'value': tVal
        };

        console.log(data);

        if (Object.keys(CMS_DATATABLE_SELECT_DATA).length==0) {
            BootstrapDialog.alert(
                {
                    type: BootstrapDialog.TYPE_WARNING,
                    title: `<?=$CONFIG['cms']['title']?>`,
                    message: 'No selected data.'
                }
            );
            return false;
        }

//        var formActionId = data['table_id']+'_post';
//        $('body').append('<form id="'+formActionId+'" method="post"><input type="hidden" name="cmsButtonPostVal"></form>');
//        $('#'+formActionId+' input').val(JSON.stringify(data));
//        $('#'+formActionId).submit();

        $.ajax(
            {
                type: 'POST',
                url: '',
                data: 'cmsButtonPostVal='+JSON.stringify(data)
            }
        ).done(
            function (data) {
                console.log(data);

                CMS_DATATABLE[table_id].ajax.reload(null, false);

                CMS_DATATABLE_SELECT_DATA = {};
            }
        );


    }
);

function cmsFnDataTableOrder() {
    $(this).find('tbody').sortable({
        handle: ($(this).find('tbody .cms-draggable')[0]) ? '.cms-draggable' : '',
        axis: 'y',
        stop: function( event, ui ) {
            var getRowIndex = $(ui['item'][0]).index();
            $(this).find('tr[role="row"]').removeAttr('style');
            $(this).find('tr[role="row"] td').removeAttr('style');


            var tSelf = this;
            setTimeout(
                function () {
                    $(tSelf).find('tr[role="row"]').each(
                        function (pIndex, pObj) {
                            CMS_DATATABLE_ORDER_DATA[pIndex] = $(pObj).attr('id').replace('row_', '');
                        }
                    );

                    var data = {
                        'table_id': $(tSelf).parent().attr('id'),
                        'value': CMS_DATATABLE_ORDER_DATA
                    };

                    $.ajax(
                        {
                            type: 'POST',
                            url: '',
                            data: 'cmsListOrderVal='+encodeURIComponent(base64_encode(json_encode(data))) /*JSON.stringify(data)*/
                        }
                    ).done(
                        function (data) {
                            console.log(data);
                        }
                    );

                },
                400
            );
        },
        remove: function( event, ui ) {
            var getRowIndex = $(ui['item'][0]).index();
            $(this).find('tr[role="row"]').removeAttr('style');
            $(this).find('tr[role="row"] td').removeAttr('style');
        },
        over: function (event, ui) {
            var getRowIndex = $(ui['item'][0]).index();
            //console.log('Row Index: '+getRowIndex);

            var arrCol = [];
            var dataRownLen = $(this).find('tr').length-1;

            //console.log('dataRownLen: '+dataRownLen);

            var otherIndex = ((getRowIndex+1)>=dataRownLen) ? 0 : (getRowIndex+1);

            $(this).find('tr[role="row"]:eq('+(otherIndex)+') td').each(
                function (pIndex, pObj) {
                    arrCol[pIndex] = $(pObj).width();
                }
            );

            var tblRowWidth = $('#'+$(ui['item'][0]).attr('id')).width()+glbScrollBarWidth;

            $(this).find('tr[role="row"]:eq('+getRowIndex+')').css({display: 'table', border: '1px solid #333', width: tblRowWidth+'px', backgroundColor: '#c0c0c0'});
            $(this).find('tr[role="row"]:eq('+getRowIndex+') td').each(
                function (pIndex, pObj) {
                    $(pObj).css('width', arrCol[pIndex]);
                }
            );

            $(this).find('tr[role="row"]:not(tbody tr[class*=" ui-sortable-"])').removeAttr('style');
        }
    });
}

<?php
        $arrHTMLOut = array();

        foreach($self->formLayoutData->body->datatable as $child)
        {
            $arrListColumn = array();
            $arrListColumnOrder = array();
            $arrListColumnAll = array();
            $tableId = strval($child->table['id']);
            $table_order_field = (isset($self->dataTable[$tableId]['table_order_field'])) ? strval($self->dataTable[$tableId]['table_order_field']) : strval($child->table['table_order_field']);
            $table_order_by = (isset($self->dataTable[$tableId]['table_order_by'])) ? strval($self->dataTable[$tableId]['table_order_by']) : strval($child->table['table_order_by']);

            $datatable_paging = strval($child->table['datatable_paging']);
            $datatable_info = strval($child->table['datatable_info']);
            $datatable_ordering = strval($child->table['datatable_ordering']);
            $datatable_filter = strval($child->table['datatable_filter']);
            $datatable_pageLength = (isset($child->table['datatable_pageLength'])) ? strval($child->table['datatable_pageLength']) : 50;

            if ($table_order_field!='') {
                $arrListColumn[$table_order_field] = 'asc';
                $arrListColumnOrder[$table_order_field] = 'asc';
            }
            if ($table_order_by!='') {
                $tArr = explode(',', $table_order_by);
                foreach($tArr as $Index => $Value) {
                    $tSArr = explode(' ', trim($Value));
                    $arrListColumn[$tSArr[0]] = (isset($tSArr[1])) ? (($tSArr[1]!='') ? strtolower($tSArr[1]) : 'asc') : 'asc';
                    $arrListColumnOrder[$tSArr[0]] = (isset($tSArr[1])) ? (($tSArr[1]!='') ? strtolower($tSArr[1]) : 'asc') : 'asc';
                }
            }

            $arrEvents = array();
            $hasDraggable = false;
            if (count($child->children())) {

                foreach($child->children() as $subChild) {
                    $arrColumns = array();
                    $arrScriptCodes = array();
                    if (count($child->children())) {
                        /* BODY */
                        if (count($subChild->body->column) > 0) {
                            $counter = 0;
                            foreach($subChild->body->column as $subColumn) {

                                $tVisible = (isset($subColumn['visible'])) ? filter_var(strval($subColumn['visible']), FILTER_VALIDATE_BOOLEAN) : true;

                                if ($tVisible) {
                                    $fieldName = '';
                                    $className = '';
                                    $columnOption = array();
                                    if ($subColumn['type']=='data') {
                                        $fieldName = strval($subColumn['fieldname']);
                                    } else if ($subColumn['type']=='action') {
                                        $fieldName = 'cms_datatable_action';
                                        $columnOption[] = '"bSearchable": false';
                                        $columnOption[] = '"bSortable": false';
                                        $className .= " cms-extended-col ";
                                    } else if ($subColumn['type']=='select') {
                                        $fieldName = 'cms_datatable_select';
                                        $columnOption[] = '"bSearchable": false';
                                        $columnOption[] = '"bSortable": false';
                                        $className .= " cms-extended-col ";
                                    } else if ($subColumn['type']=='drag') {
                                        $fieldName = 'cms_datatable_drag';
                                        $columnOption[] = '"bSearchable": false';
                                        $columnOption[] = '"bSortable": false';
                                        $className .= " cms-extended-col cms-draggable cms-cursor-ns-resize ";
                                        $hasDraggable = true;
                                    }

                                    if (isset($subColumn['searchable'])) {
                                        $columnOption[] = '"bSearchable": '.$subColumn['searchable'];
                                    }

                                    if (isset($subColumn['sortable'])) {
                                        $columnOption[] = '"bSortable": '.$subColumn['sortable'];
                                    }

                                    if (isset($subColumn['width'])) {
                                        $columnOption[] = '"width": "'.$subColumn['width'].'"';
                                    }

                                    if (isset($subColumn['show'])) {
                                        $columnOption[] = '"visible": '.$subColumn['show'].'';
                                    }

                                    if ($table_order_field!="") {
                                        if (!$hasDraggable) $className .= " cms-cursor-ns-resize ";
                                    }

                                    if (isset($subColumn['class'])) {
                                        $className .= " ".$subColumn['class'];
                                    }

                                    $arrColumns[] = '{ "data": "'.$fieldName.'", "className": "datatable-col-'.($counter).$className.'"'.(count($columnOption) > 0 ? ', '.implode(',', $columnOption) : '').' }';
                                    $arrListColumnAll[strval($fieldName)] = $counter;
                                    $counter++;

                                    if (isset($arrListColumn[strval($subColumn['fieldname'])])) {
                                        unset($arrListColumn[strval($subColumn['fieldname'])]);
                                    }
                                }

                            }
                        }
                        /* BODY */

                        if (isset($subChild->events->event) && count($subChild->events->event) > 0) {
                            foreach($subChild->events->event as $subEvent) {
                                $tFunc = '"'.strval($subEvent['name']).'": function ('.strval($subEvent['param']).') {
                                    '.strval($subEvent).'
                                }
                                ';
                                $arrEvents[] = $tFunc;
                            }
                        }

                        if (isset($subChild->scripts->code) && count($subChild->scripts->code) > 0) {
                            foreach($subChild->scripts->code as $subCode) {
                                $arrScriptCodes[strval($subCode['name'])] = strval($subCode);
                            }
                        }
                    }

                    $counter = count($arrListColumnAll);
                    foreach($arrListColumn as $Index => $Value) {
                        $arrColumns[] = '{ "data": "'.$Index.'", "className": "", "bSearchable": false, "visible": false }';
                        $arrListColumnAll[$Index] = $counter;
                        $counter++;
                    }

                    $strColumns = implode(",\n", $arrColumns);
                    $strQuery = (isset($_SERVER['QUERY_STRING'])) ? '&'.$_SERVER['QUERY_STRING'] : "";


                    $arrColumnsOrder = array();
                    foreach($arrListColumnOrder as $Index => $Value) {
                        $arrColumnsOrder[] = '['.$arrListColumnAll[$Index].', \''.$Value.'\']';
                    }

                    $strOrders = "";
                    if (count($arrColumnsOrder)>0) {
                        $strOrders = '"order": ['.implode(', ', $arrColumnsOrder).'],';
                    }

                    if ($table_order_field!='') $table_order_field = "fnInitComplete: cmsFnDataTableOrder,";

                    /* DATATABLE FEATURE ENABLE/DISABLE */
                    $arrListDataTableFeature = array();
                    if ($datatable_paging!='') $arrListDataTableFeature[] = '"paging": '.$datatable_paging.',';
                    if ($datatable_info!='') $arrListDataTableFeature[] = '"info": '.$datatable_info.',';
                    if ($datatable_ordering!='') $arrListDataTableFeature[] = '"ordering": '.$datatable_ordering.',';
                    if ($datatable_filter!='') $arrListDataTableFeature[] = '"filter": '.$datatable_filter.',';
                    if ($datatable_pageLength!='') $arrListDataTableFeature[] = '"pageLength": '.$datatable_pageLength.',';
                    $strListDataTableFeature = implode('', $arrListDataTableFeature);

                    $strEvents = implode(",", $arrEvents);
                    if ($strEvents!='') $strEvents.=',';

                    $strScriptCodesAjaxData = '';
                    if (isset($arrScriptCodes['ajax_data'])) {
                        $strScriptCodesAjaxData = $arrScriptCodes['ajax_data'];
                    }


                    $arrHTMLOut[] = <<<EOL
                        CMS_DATATABLE['{$child->table['id']}'] = $('#{$child->table['id']}').DataTable(
                            {
                                "processing": true,
                                "serverSide": true,
                                "ajax": {
                                    url: "{$self->cmsActivePath}?datatable={$child->table['id']}{$strQuery}",
                                    data: function (data) {
                                        {$strScriptCodesAjaxData}
                                    }
                                },
                                "columns": [
                                    {$strColumns}
                                ],

                                language: {
                                    search: "",
                                    searchPlaceholder: "Search",
                                    lengthMenu: "_MENU_",
                                    infoFiltered: "",
                                    "paginate": {
                                        "first":      "First",
                                        "last":       "Last",
                                        "next":       "<i class=\"fa fa-arrow-right\" aria-hidden=\"true\"></i>",
                                        "previous":   "<i class=\"fa fa-arrow-left\" aria-hidden=\"true\"></i>"
                                    }
                                },

                                {$strEvents}

                                {$table_order_field}

                                {$strOrders}

                                {$strListDataTableFeature}
                            }
                        );

                        $('#{$child->table['id']}').on('draw.dt',
                            function () {

                                $.event.trigger({
                                    type: "CMS_DATATABLE_DRAW",
                                    dtId: '{$child->table['id']}'
                                });

                                var thisTableObj = this;
                                $.each(CMS_DATATABLE_SELECT_DATA,
                                        function (pIndex, pValue) {
                                            console.log(pIndex+' '+pValue);
                                            if ($(thisTableObj).find('tbody tr[id="row_'+pValue[0]+'"] input[type="checkbox"]')[0]) {
                                                $(thisTableObj).find('tbody tr[id="row_'+pValue[0]+'"] input[type="checkbox"]')[0].checked = true;
                                            }
                                        }
                                );
                            }
                        );

EOL;
                }

            }
        }

        print '$.fn.dataTable.ext.errMode = "none";'."\n\n";

        print implode("", $arrHTMLOut);
?>
    $(document).on('CMS_DATATABLE_DRAW',
        function (e) {
            if ($('#cms-side-menu')[0]) $('#cms-side-menu').css('border-right', '1px #23282d solid');
            $('#cms-side-menu').height($(document).height()-$('#cms-nav').outerHeight());
            $('#cms-content').css('min-height', $(window).height()-($('#cms-nav').outerHeight())+'px');
        }
    );

    var arrDTHandle = {};

    $(document.body).on('xhr.dt', function (e, settings, json, xhr){
        var api = new $.fn.dataTable.Api(settings);

        if (!arrDTHandle[api.context[0].nTable.id]) {
            arrDTHandle[api.context[0].nTable.id] = {
                retry_counter: 0
            }
        }

        // If there is an Ajax error and status code is 401

        if(json === null && (xhr.status === 401 || xhr.status === 500)){
            arrDTHandle[api.context[0].nTable.id]['retry_counter'] = arrDTHandle[api.context[0].nTable.id]['retry_counter']+1;

            if (arrDTHandle[api.context[0].nTable.id]['retry_counter']<=3) {
                api.ajax.reload();
            } else {
                BootstrapDialog.alert('Error on loading the data table.');
            }

        }

        //console.log(api.context[0]);
    });