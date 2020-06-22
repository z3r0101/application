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

class cms_form_repeater
{
    private $controlObj = NULL;
    private $data = NULL;

    public $id = NULL;
    public $isRepeaterControl = false;
    public $repeater_style = "";
    public $group_name = "";

    public $vendor_css_path = "DataTables/datatables.min.css";
    public $vendor_js_path = "DataTables/datatables.js";
    public $control_style = <<<CSS
        .control-form-repeater {
            display: block; 
            width: 100%; 
            padding: 1%; 
            border: 1px solid #ced4da; 
            margin-bottom: 2%; 
            border-radius: .25rem;
        }
        .control-form-repeater-item {
            display: inline-block; 
            width: 100%; 
            background-color: #e2e2e2; 
            padding: 1%
        }

        .control-form-repeater-item:nth-child(even) {
            background-color: #dcdcdc;
        }
CSS;


    function __construct($controlObj)
    {
        #parent::__construct();

        $this->controlObj = $controlObj;
    }

    function value($data) {
        $this->data = $data;
    }

    function render() {

        $tId = ($this->id === NULL) ? $this->controlObj['id'] : $this->id;
        $tName = strval($this->controlObj['id']);
        $tCaption = (isset($this->controlObj['caption'])) ? '<label for="'.$tId.'">'.$this->controlObj['caption'].'</label>' : '';
        $tPlaceHolder = (isset($this->controlObj['placeholder'])) ? 'placeholder="'.$this->controlObj['placeholder'].'"' : '';
        $tValue = ($this->data !== NULL) ? $this->data : ((isset($this->controlObj['value']) ? $this->controlObj['value'] : ''));
        $tGroup = (isset($this->controlObj['group'])) ? 'cms-group="'.strval($this->controlObj['group']).'"' : "";
        if ($tGroup!='') {
            if ($this->group_name!=strval($this->controlObj['group'])) {
                $tGroup .= ' style="display: none"';
            }
        }

        $tContainerStyle = (isset($this->controlObj['container-style'])) ? 'style="'.$this->controlObj['container-style'].'"' : '';
        $tContainerObjStyle = (isset($this->controlObj['container-obj-style'])) ? 'style="'.$this->controlObj['container-obj-style'].'"' : '';
        $tContainerObjClass = (isset($this->controlObj['container-obj-class'])) ? 'class="'.$this->controlObj['container-obj-class'].'"' : '';

        $tClass = (!$this->isRepeaterControl) ? 'cms-form-control' : 'cms-form-control-repeater';

        $tControlStyle = (isset($this->controlObj['control_style'])) ? $this->controlObj['control_style'] : '';
        $tReadonly = (isset($this->controlObj['readonly'])) ? 'readonly="'.$this->controlObj['readonly'].'""' : '';

        $tValue = base64_encode($tValue);

        if ($this->isRepeaterControl) {

            if (isset($this->repeater_style)) {
                if ($this->repeater_style=='table') $tCaption = '';
            }
        }

        $tInputType = (isset($this->controlObj['input-type'])) ? $this->controlObj['input-type'] : 'text';

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

        $tStyle = (isset($this->controlObj['style'])) ? 'style="'.$this->controlObj['style'].'""' : '';

        return <<<EOL
            <div class="control-form-repeater">
                <div class="control-form-repeater-item">
                    <div class="form-group">
                        <label for="exampleInputEmail1">Email address</label>
                        <textarea class="form-control"></textarea>
                        <small id="emailHelp" class="form-text text-muted">We'll never share your email with anyone else.</small>
                    </div>
                    <div class="form-group">
                        <label for="exampleInputEmail1">Email address</label>
                        <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Enter email">
                        <small id="emailHelp" class="form-text text-muted">We'll never share your email with anyone else.</small>
                    </div>
                    <div class="form-group">
                        <label for="exampleInputEmail1">Email address</label>
                        <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Enter email">
                        <small id="emailHelp" class="form-text text-muted">We'll never share your email with anyone else.</small>
                    </div>
                    <div style="position: relative; display: inline-block; width: 100%; padding: 1%; border: 1px solid #ced4da; background-color: #fff; border-radius: .25rem;">
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon1"><i class="fas fa-image"></i></span>
                            </div>
                            <input type="text" class="form-control" placeholder="/uploads/files/image.jpg" aria-label="Recipient's username" aria-describedby="basic-addon2" readonly="readonly">
                            <div class="input-group-append">
                                <span class="input-group-text" id="basic-addon2"><i class="far fa-plus-square"></i></span>
                            </div>
                            <div class="input-group-append">
                                <span class="input-group-text" id="basic-addon2"><i class="far fa-minus-square"></i></span>
                            </div>
                        </div>
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon1"><i class="fas fa-image"></i></span>
                            </div>
                            <input type="text" class="form-control" placeholder="/uploads/files/image.jpg" aria-label="Recipient's username" aria-describedby="basic-addon2" readonly="readonly">
                            <div class="input-group-append">
                                <span class="input-group-text" id="basic-addon2"><i class="far fa-plus-square"></i></span>
                            </div>
                            <div class="input-group-append">
                                <span class="input-group-text" id="basic-addon2"><i class="far fa-minus-square"></i></span>
                            </div>
                        </div>
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon1"><i class="fas fa-image"></i></span>
                            </div>
                            <input type="text" class="form-control" placeholder="/uploads/files/image.jpg" aria-label="Recipient's username" aria-describedby="basic-addon2" readonly="readonly">
                            <div class="input-group-append">
                                <span class="input-group-text" id="basic-addon2"><i class="far fa-plus-square"></i></span>
                            </div>
                            <div class="input-group-append">
                                <span class="input-group-text" id="basic-addon2"><i class="far fa-minus-square"></i></span>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div style="display: inline-block; width: 100%;">
                        <div style="display: inline-block; width: 50%; text-align: left"><a href="#"><i class="fas fa-sort"></i></a></div><div style="display: inline-block; width: 50%; text-align: right"><a href="#"><i class="fas fa-plus-circle"></i></a> <a href="#"><i class="fas fa-minus-circle"></i></a></div>
                    </div>
                </div>
                <div class="control-form-repeater-item">
                    <div class="form-group">
                        <label for="exampleInputEmail1">Email address</label>
                        <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Enter email">
                        <small id="emailHelp" class="form-text text-muted">We'll never share your email with anyone else.</small>
                    </div>
                    <div class="form-group">
                        <label for="exampleInputEmail1">Email address</label>
                        <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Enter email">
                        <small id="emailHelp" class="form-text text-muted">We'll never share your email with anyone else.</small>
                    </div>
                    <div class="form-group">
                        <label for="exampleInputEmail1">Email address</label>
                        <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Enter email">
                        <small id="emailHelp" class="form-text text-muted">We'll never share your email with anyone else.</small>
                    </div>
                    <hr>
                    <div style="display: inline-block; width: 100%;">
                        <div style="display: inline-block; width: 50%; text-align: left"><a href="#"><i class="fas fa-sort"></i></a></div><div style="display: inline-block; width: 50%; text-align: right"><a href="#"><i class="fas fa-plus-circle"></i></a> <a href="#"><i class="fas fa-minus-circle"></i></a></div>
                    </div>
                </div>                
            </div>

  
            <!--table id="{$tId}_dt" class="table table-striped table-bordered">
            </table-->
            <div class="form-group" {$tGroup} {$tContainerStyle}>
                {$tCaption}
                <div {$tContainerObjClass} {$tContainerObjStyle}>
                {$strHTMLOutLeft}
                <input type="{$tInputType}" class="form-control {$tClass} {$tControlStyle}" id="{$tId}" name="{$tName}" {$tPlaceHolder} {$tReadonly} {$tStyle}>
                {$strHTMLOutRight}
                </div>
            </div>
            <script>
                var dataSet = json_decode(base64_decode('{$tValue}'));
                console.log(dataSet);
                $('#{$tId}').val(base64_decode('{$tValue}'));
                
                $(document).ready(
                    function () {
                        /*var dataSet = [
                            [ "Tiger Nixon", "System Architect", "Edinburgh", "5421", "2011/04/25", "$320,800" ],
                            [ "Garrett Winters", "Accountant", "Tokyo", "8422", "2011/07/25", "$170,750" ],
                            [ "Ashton Cox", "Junior Technical Author", "San Francisco", "1562", "2009/01/12", "$86,000" ],
                            [ "Cedric Kelly", "Senior Javascript Developer", "Edinburgh", "6224", "2012/03/29", "$433,060" ],
                            [ "Airi Satou", "Accountant", "Tokyo", "5407", "2008/11/28", "$162,700" ],
                            [ "Brielle Williamson", "Integration Specialist", "New York", "4804", "2012/12/02", "$372,000" ],
                            [ "Herrod Chandler", "Sales Assistant", "San Francisco", "9608", "2012/08/06", "$137,500" ],
                            [ "Rhona Davidson", "Integration Specialist", "Tokyo", "6200", "2010/10/14", "$327,900" ],
                            [ "Colleen Hurst", "Javascript Developer", "San Francisco", "2360", "2009/09/15", "$205,500" ],
                            [ "Sonya Frost", "Software Engineer", "Edinburgh", "1667", "2008/12/13", "$103,600" ],
                            [ "Jena Gaines", "Office Manager", "London", "3814", "2008/12/19", "$90,560" ],
                            [ "Quinn Flynn", "Support Lead", "Edinburgh", "9497", "2013/03/03", "$342,000" ],
                            [ "Charde Marshall", "Regional Director", "San Francisco", "6741", "2008/10/16", "$470,600" ],
                            [ "Haley Kennedy", "Senior Marketing Designer", "London", "3597", "2012/12/18", "$313,500" ],
                            [ "Tatyana Fitzpatrick", "Regional Director", "London", "1965", "2010/03/17", "$385,750" ],
                            [ "Michael Silva", "Marketing Designer", "London", "1581", "2012/11/27", "$198,500" ],
                            [ "Paul Byrd", "Chief Financial Officer (CFO)", "New York", "3059", "2010/06/09", "$725,000" ],
                            [ "Gloria Little", "Systems Administrator", "New York", "1721", "2009/04/10", "$237,500" ],
                            [ "Bradley Greer", "Software Engineer", "London", "2558", "2012/10/13", "$132,000" ],
                            [ "Dai Rios", "Personnel Lead", "Edinburgh", "2290", "2012/09/26", "$217,500" ],
                            [ "Jenette Caldwell", "Development Lead", "New York", "1937", "2011/09/03", "$345,000" ],
                            [ "Yuri Berry", "Chief Marketing Officer (CMO)", "New York", "6154", "2009/06/25", "$675,000" ],
                            [ "Caesar Vance", "Pre-Sales Support", "New York", "8330", "2011/12/12", "$106,450" ],
                            [ "Doris Wilder", "Sales Assistant", "Sydney", "3023", "2010/09/20", "$85,600" ],
                            [ "Angelica Ramos", "Chief Executive Officer (CEO)", "London", "5797", "2009/10/09", "$1,200,000" ],
                            [ "Gavin Joyce", "Developer", "Edinburgh", "8822", "2010/12/22", "$92,575" ],
                            [ "Jennifer Chang", "Regional Director", "Singapore", "9239", "2010/11/14", "$357,650" ],
                            [ "Brenden Wagner", "Software Engineer", "San Francisco", "1314", "2011/06/07", "$206,850" ],
                            [ "Fiona Green", "Chief Operating Officer (COO)", "San Francisco", "2947", "2010/03/11", "$850,000" ],
                            [ "Shou Itou", "Regional Marketing", "Tokyo", "8899", "2011/08/14", "$163,000" ],
                            [ "Michelle House", "Integration Specialist", "Sydney", "2769", "2011/06/02", "$95,400" ],
                            [ "Suki Burks", "Developer", "London", "6832", "2009/10/22", "$114,500" ],
                            [ "Prescott Bartlett", "Technical Author", "London", "3606", "2011/05/07", "$145,000" ],
                            [ "Gavin Cortez", "Team Leader", "San Francisco", "2860", "2008/10/26", "$235,500" ],
                            [ "Martena Mccray", "Post-Sales support", "Edinburgh", "8240", "2011/03/09", "$324,050" ],
                            [ "Unity Butler", "Marketing Designer", "San Francisco", "5384", "2009/12/09", "$85,675" ]
                        ];
                        
                        $('#{$tId}_dt').DataTable(
                            {
                                data: dataSet,
                                columns: [
                                    { title: "Name" },
                                    { title: "Position" },
                                    { title: "Office" },
                                    { title: "Extn." },
                                    { title: "Start date" },
                                    { title: "Salary" }
                                ]    
                            }
                        );*/
                        
                        /*$('#{$tId}_dt').DataTable(
                            {
                                data: dataSet,
                                columns: [
                                    { title: "col_name", data: "col_name" },
                                    { title: "col_caption", data: "col_caption" }
                                ]    
                            }
                        );*/
                    }
                )
            </script>
EOL;

    }
}