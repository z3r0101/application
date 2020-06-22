<?php
/*
 * z3r0101
 *
 * An open source application development framework for PHP
 *
 * @package:    z3r0101
 * @author:     ryanzkizen@gmail.com
 * @version:    Beta 1.0
 *
 */

class cms_datatable
{
    private $controlObj = NULL;
    private $data = NULL;
    private $postId = 0;

    public $id = NULL;
    public $isRepeaterControl = false;
    public $group_name = "";

    function __construct($controlObj)
    {
        #parent::__construct();

        $this->controlObj = $controlObj;

        if (isset($this->controlObj['value'])) {
            $this->data = strval($this->controlObj['value']);
        }
    }

    function value($data) {
        $this->data = $data;
    }

    function postId($pId) {
        $this->postId = $pId;
    }

    function render() {
        $errorMsg = array();

        $tId = ($this->id === NULL) ? $this->controlObj['id'] : $this->id;
        $tName = strval($this->controlObj['id']);
        $footer = strval($this->controlObj['footer']);
        $tCaption = (isset($this->controlObj['caption'])) ? '<label for="'.$tId.'">'.$this->controlObj['caption'].'</label>' : '';
        $tPlaceHolder = (isset($this->controlObj['placeholder'])) ? 'placeholder="'.$this->controlObj['placeholder'].'"' : '';
        $tValue = ($this->data !== NULL) ? $this->data : '';
        $tGroup = (isset($this->controlObj['group'])) ? 'cms-group="'.strval($this->controlObj['group']).'"' : "";
        if ($tGroup!='') {
            if ($this->group_name!=strval($this->controlObj['group'])) {
                $tGroup .= ' style="display: none"';
            }
        }

        $tClass = (!$this->isRepeaterControl) ? 'cms-form-control' : 'cms-form-control-repeater';

        $table = (isset($this->controlObj['table_name'])) ? strval($this->controlObj['table_name']) : '';
        $table_name_temp = '';
        if (isset($this->controlObj['table_select'])) {
            $table_name_temp = "({$this->controlObj['table_select']}) AS {$table}";
        }
        $table_where = (isset($this->controlObj['table_where'])) ? strval($this->controlObj['table_where']) : '';

        //if ($this->postId == 0)
        $table_where .= ($table_where!='') ? ' AND '.strval($this->controlObj['table_parent_pk']).' = '.$this->postId : strval($this->controlObj['table_parent_pk']).' = '.$this->postId;



        $table_view = '
                                (
                                    SELECT
                                        '.$table.'.*
                                    FROM
                                        '. (($table_name_temp=='') ? $table : $table_name_temp).'
                                ) AS '.$table . (($table_where!='') ? " WHERE {$table_where}" : '');

        $db = new cmsDatabaseClass();
        $arrData = $db->select("SHOW TABLES LIKE '{$table}';");
        if (count($arrData) == 0) {
            $errorMsg[] = "Table named {$table} not found.";
        }

        $arrHTMLOut = array();
        $arrHTMLOut[] = "<thead><tr>";
        $arrHTMLOutFooter = array();
        $arrColumns = array();
        $arrDTColumns = array();
        $arrDTColumnsDef = array();

        $arrListColumnAll = array();
        $arrListColumnOrder = array();
        $datatable_order_by = (isset($this->controlObj['datatable_order_by'])) ? strval($this->controlObj['datatable_order_by']) : '';
        if ($datatable_order_by!='') {
            $tArr = explode(',', $datatable_order_by);
            foreach($tArr as $Index => $Value) {
                $tSArr = explode(' ', trim($Value));
                $arrListColumnOrder[$tSArr[0]] = (isset($tSArr[1])) ? (($tSArr[1]!='') ? strtolower($tSArr[1]) : 'asc') : 'asc';
            }
        }

        $strOnInitialize = "";
        $counter = 0;
        foreach($this->controlObj->children() as $tagObj) {
            if (strval($tagObj->getName())=='column') {
                #$column = (object) array('column'=>$tagObj);
                #print_r($column); print '<hr>';
                #print_r(strval($tagObj['fieldname'])); print '<hr>';

                $arrHTMLOut[] = "<th>".strval($tagObj['caption'])."</th>";
                $arrHTMLOutFooter[] = "<th>".strval($tagObj['caption'])."</th>";
                $arrColumns[strval($tagObj['fieldname'])] = strval($tagObj['fieldname']);

                $arrColumnsProp = array();

                $arrColumnsProp['data'] = strval($tagObj['fieldname']);
                $arrListColumnAll[strval($tagObj['fieldname'])] = $counter;

                if (isset($tagObj['width'])) {
                    $arrColumnsProp['width'] = strval($tagObj['width']);
                }

                if (isset($tagObj['sortable'])) {
                    $arrColumnsProp['bSortable'] = (strval($tagObj['sortable'])=='true') ? true : false;
                }

                $arrColumnsProp['className'] = "datatable-col-{$counter} ";
                if (isset($tagObj['class'])) {
                    $arrColumnsProp['className'] .= strval($tagObj['class']);
                }

                if (isset($tagObj['searchable'])) {
                    $arrColumnsProp['bSearchable'] = (strval($tagObj['searchable'])=='true') ? true : false;
                }


                if (isset($tagObj['visible'])) {
                    $tVisible = (strval($tagObj['visible'])!='') ? strval($tagObj['visible']) : 'false';
                }

                $arrDTColumnsDefSub = array();
                foreach($tagObj->children() as $colSub) {
                    if (strval($colSub->getName()) == 'columndef') {
                        $arrDTColumnsDefSub[] = strval($colSub);
                        $arrDTColumnsDefSub[] = '"targets": '.$counter;
                    } else if (strval($colSub->getName()) == 'footer') {
                        $tClass = (isset($colSub["class"])) ? ' class="'.$colSub["class"].'"' : "";
                        $arrHTMLOutFooter[$counter] = "<th{$tClass}>".strval($colSub["caption"])."</th>";
                    }
                }
                $arrDTColumnsDef[] = $arrDTColumnsDefSub;

                $arrDTColumns[] = $arrColumnsProp;

                $counter++;
            } else if (strval($tagObj->getName())=="on_initialize") {
                $strOnInitialize .= strval($tagObj).",";
            }
        }
        $arrHTMLOut[] = "</tr></thead>";

        $arrColumnsOrder = array();
        foreach($arrListColumnOrder as $Index => $Value) {
            $arrColumnsOrder[] = '['.$arrListColumnAll[$Index].', \''.$Value.'\']';
        }
        $strOrders = "";
        if (count($arrColumnsOrder)>0) {
            $strOrders = '"order": ['.implode(', ', $arrColumnsOrder).'],';
        }

        if ($footer == "yes") {
            $arrHTMLOut[] = "<tfoot><tr>";
            $arrHTMLOut[] = implode("", $arrHTMLOutFooter);
            $arrHTMLOut[] = "</tr></tfoot>";
        }

        $dataSet = array();
        $db->mysqli->query("SET @cnt = 0");

        $arrDataPK = $db->select("SHOW KEYS FROM {$table} WHERE Key_name = 'PRIMARY'");
        $tPKField = "";
        if (count($arrDataPK)>0) {
            $tPKField = $arrDataPK[0]['Column_name'];
        }

        $table_order_by = (isset($this->controlObj['table_order_by'])) ? strval($this->controlObj['table_order_by']) : '';
        if ($table_order_by!='') {
            $table_order_by = "ORDER BY $table_order_by";
        }

        $arrData = $db->select("SELECT @cnt:=@cnt+1 as cms_row_index, {$table}.*, {$tPKField} AS cms_row_delete FROM {$table_view} {$table_order_by}");

        for($i=0; $i<count($arrData); $i++) {
            $dataSetRow = array();
            foreach($arrColumns as $ColName) {
                $dataSetRow[$ColName] = $arrData[$i][$ColName];
            }
            $dataSet[] = $dataSetRow;
        }

        $datatable_paging = strval($this->controlObj['datatable_paging']);
        $datatable_info = strval($this->controlObj['datatable_info']);
        $datatable_ordering = strval($this->controlObj['datatable_ordering']);
        $datatable_filter = strval($this->controlObj['datatable_filter']);

        $arrListDataTableFeature = array();
        if ($datatable_paging!='') $arrListDataTableFeature[] = '"paging": '.$datatable_paging.',';
        if ($datatable_info!='') $arrListDataTableFeature[] = '"info": '.$datatable_info.',';
        if ($datatable_ordering!='') $arrListDataTableFeature[] = '"ordering": '.$datatable_ordering.',';
        if ($datatable_filter!='') $arrListDataTableFeature[] = '"filter": '.$datatable_filter.',';
        $strListDataTableFeature = implode('', $arrListDataTableFeature);

        $data_add = strval($this->controlObj['data_add']);
        $data_add_caption = strval($this->controlObj['data_add_caption']);
        $table_parent_pk = strval($this->controlObj['table_parent_pk']);

        #print '<pre>';
        #print json_encode($arrDTColumns);
        #print '</pre>';

        if (count($errorMsg)==0) {
            $strDataSet = json_encode($dataSet);
            $strDTColumn = json_encode($arrDTColumns);
            $strHTMLOut = implode('', $arrHTMLOut);

            $arrDTColumnDefJoin = array();
            foreach($arrDTColumnsDef as $Index => $arrVal) {
                if (count($arrVal)>0) {
                    $arrDTColumnDefJoin[] = "{".implode(',', $arrVal)."}";
                }
            }
            $strDTColumnDefJoin = implode(',', $arrDTColumnDefJoin);

            $dataAddButton = "";
            if ($data_add == "true") {
                $data_add_caption = ($data_add_caption!='') ? $data_add_caption : 'Add';
                $dataAddButton = '
                <button class="btn btn-default datatable-add" type="submit" onclick="'.$tId.'_data_add(this)">'.$data_add_caption.'</button>
                ';
            }

            $arrColumnRowAdd = array();
            foreach($arrColumns as $FieldName => $FieldValue) {
                if ($FieldName == 'cms_row_index') {
                    $arrColumnRowAdd[] = "{$FieldName}: cmsRowIndex";
                } else if ($FieldName == $table_parent_pk) {
                    $arrColumnRowAdd[] = "{$FieldName}: $('.cms-form-primary-id').val()";
                } else {
                    $arrDataDef = $db->select(
                        "
                            SELECT *
                            FROM information_schema.COLUMNS
                            WHERE
                                TABLE_SCHEMA = '{$db->database}' AND
                                TABLE_NAME = '{$table}' AND
                                COLUMN_NAME = '{$FieldName}'
                        "
                    );
                    if(count($arrDataDef)>0) {
                        if ($arrDataDef[0]["DATA_TYPE"] == 'varchar' || $arrDataDef[0]["DATA_TYPE"] == 'text') {
                            $arrColumnRowAdd[] = "{$FieldName}: ''";
                        } else if ($arrDataDef[0]["DATA_TYPE"] == 'int' || $arrDataDef[0]["DATA_TYPE"] == 'double') {
                            $arrColumnRowAdd[] = "{$FieldName}: 0";
                        } else {
                            $arrColumnRowAdd[] = "{$FieldName}: ''";
                        }
                    } else {
                        #ALIAS FIELD
                        $arrColumnRowAdd[] = "{$FieldName}: ''";
                    }
                }
            }
            $strColumnRowAdd = implode(",\n", $arrColumnRowAdd);

            return <<<EOL
            <div class="form-group" {$tGroup}>
                {$tCaption}
                <table id="{$tId}_table" class="display" width="100%">
                    {$strHTMLOut}
                </table>
                <input type="hidden" id="$tId" class="cms-form-control-datatable" />
                {$dataAddButton}
            </div>
            <script>
                function {$tId}_data_delete(pObj) {
                    var dataIndex = parseInt($(pObj).parents('tr').attr('cms_data_index'),10);

                    $.event.trigger({
                        type: "CMS_CONTROL_DATATABLE_DELETE",
                        cmsData: $('#{$tId}_table').DataTable().rows().data()[dataIndex]
                    });

                    $('#{$tId}_table').DataTable().rows($(pObj).parents('tr')).remove().draw();
                }

                function {$tId}_data_add(pObj, pData) {
                    pData = pData || null;

                    var oDataTable = $('#{$tId}_table').DataTable();
                    var dataLength = oDataTable.rows().data().length;

                    var cmsRowIndex = 0;
                    if (dataLength > 0)
                        cmsRowIndex = parseInt(oDataTable.rows().data()[dataLength-1]['cms_row_index'], 10)+1;

                    var rowData = {
                        {$strColumnRowAdd}
                    }

                    if (pData) {
                        $.each(pData,
                            function (pName, pVal) {
                                if (typeof(rowData[pName]) != 'undefined') {
                                    rowData[pName] = pVal;
                                }
                            }
                        );
                    }

                    oDataTable.row.add(rowData).draw( false );

                    cmsRowIndex = $('#{$tId}_table').DataTable().rows().data().length-1;

                    $.event.trigger({
                        type: "CMS_CONTROL_DATATABLE_ADD",
                        cmsData: $('#{$tId}_table').DataTable().rows().data()[cmsRowIndex],
                        newIndex: cmsRowIndex
                    });

                    return cmsRowIndex;
                }

                function {$tId}_blur(pObj) {
                    var dataIndex = parseInt($(pObj).parents('tr').attr('cms_data_index'), 10);
                    $('#{$tId}_table').DataTable().rows().data()[dataIndex][$(pObj).attr('name')] = $(pObj).val();
                }

                var {$tId}_datatable = null;

                var {$tId}_datatable_options =
                    {
                            columns: {$strDTColumn},
                            data: {$strDataSet},

                            {$strListDataTableFeature}

                            {$strOrders}

                            "columnDefs": [
                                {$strDTColumnDefJoin}
                            ],

                            $strOnInitialize
                    }

                $(document).ready(function() {
                    {$tId}_datatable = $('#{$tId}_table').DataTable({$tId}_datatable_options);
                } );

                $('#{$tId}').val(base64_encode(json_encode({$tId}_datatable_options.data)));
            </script>
EOL;
        } else {
            $errorMsg = implode("\n", $errorMsg);
            return <<<HTML
            <div class="form-group" {$tGroup}>
                <pre>{$errorMsg}</pre>
            </div>
HTML;

        }
    }
}