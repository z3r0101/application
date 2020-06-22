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

class cms_select2
{
    private $controlObj = NULL;
    private $data = NULL;

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

    function render() {

        $tId = ($this->id === NULL) ? $this->controlObj['id'] : $this->id;
        $tName = strval($this->controlObj['id']);
        $tCaption = (isset($this->controlObj['caption'])) ? ((strval($this->controlObj['caption'])!='') ? '<label for="'.$tId.'">'.$this->controlObj['caption'].'</label>' : '') : '';
        $tPlaceHolder = (isset($this->controlObj['placeholder'])) ? strval($this->controlObj['placeholder']) : '';
        $tValue = ($this->data !== NULL) ? $this->data : '';
        $tGroup = (isset($this->controlObj['group'])) ? 'cms-group="'.strval($this->controlObj['group']).'"' : "";
        $tDisabled = (isset($this->controlObj['disabled'])) ? 'disabled="'.strval($this->controlObj['disabled']).'"' : "";
        if ($tGroup!='') {
            if ($this->group_name!=strval($this->controlObj['group'])) {
                $tGroup .= ' style="display: none"';
            }
        }

        $tArrOptions = array();
        foreach($this->controlObj->option as $Option) {
            $tArrOptions[] = '<option value="'.$Option['value'].'">'.strval($Option).'</option>';
        }

        $tClass = (!$this->isRepeaterControl) ? 'cms-form-control' : 'cms-form-control-repeater';

        $data_remote = (isset($this->controlObj['data_remote'])) ? (strval($this->controlObj['data_remote']) != '' ? strval($this->controlObj['data_remote']) : 'false') : 'false';
        $data_remote_url = (isset($this->controlObj['data_remote_url'])) ? strval($this->controlObj['data_remote_url']) : '';
        $table_db_index = (isset($this->controlObj['table_db_index'])) ? intval($this->controlObj['table_db_index']) : 0;
        $table_select = (isset($this->controlObj['table_select'])) ? strval($this->controlObj['table_select']) : '';
        $table_name = (isset($this->controlObj['table_name'])) ? strval($this->controlObj['table_name']) : '';
        $table_field_caption = (isset($this->controlObj['table_field_caption'])) ? strval($this->controlObj['table_field_caption']) : '';
        $table_field_value = (isset($this->controlObj['table_field_value'])) ? strval($this->controlObj['table_field_value']) : '';
        $table_order_by = (isset($this->controlObj['table_order_by'])) ? strval($this->controlObj['table_order_by']) : '';
        $search_field = (isset($this->controlObj['search_field'])) ? strval($this->controlObj['search_field']) : '';
        $coreOption = (isset($this->controlObj['coreOption'])) ? strval($this->controlObj['coreOption']) : '';
        if ($table_name != '') {
//            $tFromTable = $table_name;
//            $db = new cmsDatabaseClass($table_db_index);
//            if ($table_select!='') {
//                $tFromTable = "
//                (
//                  $table_select
//                ) AS $table_name
//                ";
//            }
//            $arrData = $db->select("SELECT {$table_field_caption}, {$table_field_value} FROM {$tFromTable} ".(($table_order_by!="") ? " ORDER BY {$table_order_by}" : ""));
//            for($i=0; $i<count($arrData); $i++) {
//                $tArrOptions[] = '<option value="'.$arrData[$i][$table_field_value].'">'.$arrData[$i][$table_field_caption].'</option>';
//            }

            require_once(VENDORSPATH.'PHP-SQL-Parser/src/PHPSQLParser.php');

            $db = new cmsDatabaseClass($table_db_index);
            if ($tValue!='') {
                $tSQL = ($table_select=='') ? "SELECT {$table_field_caption}, {$table_field_value} FROM {$table_name} WHERE {$table_field_value} = {$tValue}" :
                    $table_select;

                $parser = new PHPSQLParser($tSQL);
                if (isset($parser->parsed['WHERE'])) {
                    if (isset($parser->parsed['ORDER'])) {
                        $tSQL = strrev($tSQL);
                        $tSQL = substr_replace($tSQL, strrev(" AND {$table_name}.{$table_field_value} = {$tValue} "), strpos($tSQL, "YB REDRO")+8, 0) ;
                        $tSQL = strrev($tSQL);
                    } else {
                        $tSQL .= " AND {$table_name}.{$table_field_value} = {$tValue} ";
                    }
                } else {
                    $tSQL .= " WHERE {$table_name}.{$table_field_value} = {$tValue} ";
                }

//                print "DB: {$table_db_index}<hr>";
//                print $tSQL.'<hr>';
//                print "$table_field_value : $table_field_caption".'<hr>';
//                print '<pre>';
//                print_r($parser->parsed);
//                print '</pre>';
//                print $tSQL;
//                exit;

                $arrData = $db->select($tSQL);
                if (count($arrData)>0) {
                    $tArrOptions[] = '<option value="'.$arrData[0][$table_field_value].'" selected="selected">'.$arrData[0][$table_field_caption].'</option>';
                }
            }
        }

        $tOptions = implode('', $tArrOptions);
        #if ($data_remote == 'false') $tOptions = implode('', $tArrOptions);

        $controlSettings = array(
            'id'=>$tName,
            'form_control_type' => 'select2',
            'data_remote' => $data_remote,
            'data_remote_url' => $data_remote_url,
            'table_select'=>$table_select,
            'table_name' => $table_name,
            'table_field_caption' => $table_field_caption,
            'table_field_value' => $table_field_value,
            'table_order_by' => $table_order_by,
            'search_field'=>$search_field,
            'table_db_index'=>$table_db_index
        );

        $strControlSettings = base64_encode(json_encode($controlSettings));

        $renderSelect2 = '';
        if ($data_remote == 'true') {

            if ($data_remote_url == "") {
                $data_remote_url = "'?CMS_POST_REQ='+encodeURIComponent('{$strControlSettings}')";
            } else {
                if( strpos( $data_remote_url, "?" ) !== false ) {
                    $data_remote_url = "'{$data_remote_url}&CMS_POST_REQ='+encodeURIComponent('{$strControlSettings}')";
                } else {
                    $data_remote_url = "'{$data_remote_url}?CMS_POST_REQ='+encodeURIComponent('{$strControlSettings}')";
                }
            }

            $renderSelect2 = "
                var {$tId}_Select2Obj = $('#{$tId}').select2(
                    {
                        placeholder: '{$tPlaceHolder}',
                        minimumInputLength: 1,
                        ajax: {
                            url: {$data_remote_url},
                            dataType: 'json',
                            delay: 250,
                            cache: false,
                            processResults: function (data) {
                              return {
                                results: data
                              };
                            }
                        },
                        {$coreOption}
                    }
                );

                //var option = new Option('OLE', 0, true, true);
                //{$tId}_Select2Obj.append(option).trigger('change');
            ";
        } else {
            $renderSelect2 = "var {$tId}_Select2Obj = $('#{$tId}').select2();";
        }

        $tContainerStyle = (isset($this->controlObj['container-style'])) ? 'style="'.$this->controlObj['container-style'].'"' : '';
        $tContainerObjStyle = (isset($this->controlObj['container-obj-style'])) ? 'style="'.$this->controlObj['container-obj-style'].'"' : '';
        $tContainerObjClass = (isset($this->controlObj['container-obj-class'])) ? 'class="'.$this->controlObj['container-obj-class'].'"' : '';

        $strHTMLOutLeft = "";
        $strHTMLOutRight = "";
        $inputGroupAlign = "right";
        foreach($this->controlObj->children() as $tagObj) {
            if (strval($tagObj->getName())=='input-group') {

                $inputGroupAlign = (isset($tagObj["align"])) ? ($tagObj["align"]!='' ? $tagObj["align"] : "right") : "right";
                if ($inputGroupAlign == "left") {
                    $strHTMLOutLeft .= strval($tagObj->children()->asXML());
                } else {
                    $strHTMLOutRight .= strval($tagObj->children()->asXML());
                }
            }
        }

        return <<<EOL
            <div class="form-group" {$tGroup} {$tContainerStyle}>
                {$tCaption}
                <div {$tContainerObjClass} {$tContainerObjStyle}>
                {$strHTMLOutLeft}
                <select class="form-control {$tClass}" id="{$tId}" name="{$tName}" cms-control-settings="{$strControlSettings}" {$tDisabled}>{$tOptions}</select>
                {$strHTMLOutRight}
                </div>
            </div>
            <script>
                $('#{$tId}').val('{$tValue}');
                {$renderSelect2}
            </script>
EOL;

    }
}