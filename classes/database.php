<?php
/*
 *  @author:        ryanzkizen@gmail.com
 *  @version:       1.0
 */

class cmsDatabaseClass {
    private $filterVariant = NULL;

    public $database = "";
    public $mysqli = null;
    public $sql = "";

    function __construct($index = 0) {
        global $CONFIG, $CMS_FN_MENU;

        if (isset($CONFIG['database'][0]['ssl']['cert'])) {
            $this->mysqli=mysqli_init();
            mysqli_ssl_set($this->mysqli, NULL, NULL, $CONFIG['database'][$index]['ssl']['cert'], NULL, NULL);
            mysqli_real_connect($this->mysqli, $CONFIG['database'][$index]['host'], $CONFIG['database'][$index]['username'], $CONFIG['database'][$index]['password'], $CONFIG['database'][$index]['name'], $CONFIG['database'][$index]['port']);
        } else {
            $this->mysqli = @new mysqli($CONFIG['database'][$index]['host'], $CONFIG['database'][$index]['username'], $CONFIG['database'][$index]['password'], $CONFIG['database'][$index]['name']);
        }

        if ($this->mysqli->connect_error) {
            print pageError("Database connection failed", $this->mysqli->connect_error."<br>Please check your database configuration in ".WWWPATH."www/config.php");
            exit;
        } else {
            /*$cmsDBCheckData = self::select("SHOW TABLES LIKE 'cms_users'");
            if (count($cmsDBCheckData) == 0) {

                #Detect import
                $arrImport = glob(WWWPATH."import/*");
                foreach($arrImport as $Index => $File) {
                    $tFile = basename($File);
                    $tPathInfo = pathinfo($tFile);

                    if ($tPathInfo['extension'] == 'sql') {
                        $this->mysqli->multi_query(file_get_contents($File));
                    }
                    if ($tPathInfo['extension'] == 'json') {
                        $arrJSON = json_decode(file_get_contents($File), true);
                        foreach($arrJSON as $Index => $Data) {
                            $upload_parent_dir = WWWPATH.'uploads/'.$Data['upload_parent_dir'];

                            if (!file_exists($upload_parent_dir))
                                mkdir($upload_parent_dir, 0777);

                            if (file_exists($upload_parent_dir))
                                mkdir($upload_parent_dir.'/'.$Data['id'], 0777);

                            if (file_exists($upload_parent_dir.'/'.$Data['id'])) {
                                mkdir($upload_parent_dir.'/'.$Data['id'].'/'.$Data['upload_container_dir'], 0777);
                            }

                            $file = fopen($upload_parent_dir.'/'.$Data['id'].'/'.$Data['upload_container_dir'].'/'.$Data['file'], "wb");
                            fwrite($file, base64_decode($Data['data']));
                            fclose($file);
                        }
                    }
                }

                $cmsRedirect = $CONFIG['website']['path'].$CONFIG['cms']['route_name']."/login";
                header("location: {$cmsRedirect}");

                #print pageError("CMS Error", "CMS users table not found");
                exit;
            } else {
                $arrDBErrorMsg = array();
                $CMS_Users_Email = '';
                $CMS_Users_Name = '';
                $CMS_Users_Password = '';
                $CMS_Users_Password_Conf = '';
                if (isset($_POST["cms-super-admin-setup"])) {
                    $CMS_Users_Email = (isset($_POST["cms-email"])) ? trim($_POST["cms-email"]) : "";
                    $CMS_Users_Name = $CMS_Users_Email;
                    $CMS_Users_Password = (isset($_POST["cms-password"])) ? trim($_POST["cms-password"]) : "";
                    $CMS_Users_Password_Conf = (isset($_POST["cms-password-confirm"])) ? trim($_POST["cms-password-confirm"]) : "";
                    $CMS_Users_Name_First = (isset($_POST["cms-fname"])) ? trim($_POST["cms-fname"]) : "";
                    $CMS_Users_Name_Last = (isset($_POST["cms-lname"])) ? trim($_POST["cms-lname"]) : "";

                    if ($CMS_Users_Email == '') {
                        $arrDBErrorMsg[] = "Email is required";
                    }
                    if ($CMS_Users_Password == '') {
                        $arrDBErrorMsg[] = "Password is required";
                    }
                    if ($CMS_Users_Password_Conf == '') {
                        $arrDBErrorMsg[] = "Confirm Password is required";
                    }
                    if ($CMS_Users_Name_First == '') {
                        $arrDBErrorMsg[] = "First Name is required";
                    }
                    if ($CMS_Users_Name_Last == '') {
                        $arrDBErrorMsg[] = "Last Name is required";
                    }

                    if (count($arrDBErrorMsg) == 0) {
                        $arrData = self::select(sprintf("SELECT COUNT(*) AS dCount FROM cms_users WHERE CMS_Users_Name = '%s' AND CMS_Users_Website = '{$CONFIG['website']['domain']}'", $this->mysqli->real_escape_string(trim($CMS_Users_Name))));
                        if ($arrData[0]["dCount"] > 0) {
                            $arrDBErrorMsg[] = "Username is already taken";
                        }

                        if ($CMS_Users_Password != $CMS_Users_Password_Conf) {
                            $arrDBErrorMsg[] = "Password does not match the confirm password";
                        } else {
                            if ($CMS_Users_Password != '' && $CMS_Users_Password_Conf != '') {
                                if (strlen($CMS_Users_Password) < 12) {
                                    $arrDBErrorMsg[] = "Password must at least 12 characters";
                                }
                                if (!preg_match("#[A-Z]+#", $CMS_Users_Password)) {
                                    $arrDBErrorMsg[] = "Password must include at least one capital letter";
                                }
                                if (!preg_match("#[a-z]+#", $CMS_Users_Password)) {
                                    $arrDBErrorMsg[] = "Password must include at least one lower case letter";
                                }
                            }
                        }
                    }

                    if (count($arrDBErrorMsg) == 0) {
                        $tCrypt = new cmsCryptonite();
                        self:$this->insert("cms_users",
                            array(
                                'CMS_Users_Email'=>$CMS_Users_Email,
                                'CMS_Users_Name'=>$CMS_Users_Name,
                                'CMS_Users_Name_First'=>$CMS_Users_Name_First,
                                'CMS_Users_Name_Last'=>$CMS_Users_Name_Last,
                                'CMS_Users_Password'=>$tCrypt->encrypt($CMS_Users_Password),
                                'CMS_Users_Status'=>1,
                                'CMS_Users_Date_Created'=>date("Y-m-d H:i:s"),
                                'CMS_Users_Website'=>$CONFIG['website']['domain']
                            )
                        );

                        $cmsRedirect = $CONFIG['website']['path'].$CONFIG['cms']['route_name']."/login";
                        header("location: {$cmsRedirect}");
                        exit;
                    }
                }

                $cmsDBCheckData = self::select("SELECT * FROM cms_users WHERE CMS_Users_Name <> 'dev'");
                if (count($cmsDBCheckData) == 0) {
                    $VENDORS_URL = $CONFIG['website']['path'].'vendors/';

                    $htmlErrorMsg = '';
                    if (count($arrDBErrorMsg)>0) {

                        $tArr = array();
                        foreach($arrDBErrorMsg as $Index => $Data) {
                            $tArr[] = '&bull; '.$Data.'<br>';
                        }

                        $htmlErrorMsg = '<div style="width: 40%; padding: 0.4%; margin-bottom: 0.9%">'.implode('', $tArr).'</div>';
                    }

                    $cmsEmail = ($CMS_Users_Email != '') ? 'value="'.htmlspecialchars($CMS_Users_Email, ENT_QUOTES).'"' : '';
                    $cmsUsername = ($CMS_Users_Name != '') ? 'value="'.htmlspecialchars($CMS_Users_Name, ENT_QUOTES).'"' : 'value="admin"';
                    $cmsPassword = ($CMS_Users_Password != '') ? 'value="'.htmlspecialchars($CMS_Users_Password, ENT_QUOTES).'"' : '';
                    $cmsPasswordConf = ($CMS_Users_Password_Conf != '') ? 'value="'.htmlspecialchars($CMS_Users_Password_Conf, ENT_QUOTES).'"' : '';

                    $htmlData = <<<HTML
                        <form method="post">
                            {$htmlErrorMsg}
                            <label>Email</label><br>
                            <input type="text" name="cms-email" {$cmsEmail} style="width: 40%; padding: 0.4%; margin-bottom: 0.6%" required><br>
                            <label>Password</label><br>
                            <input type="password" name="cms-password" {$cmsPassword} style="width: 40%; padding: 0.4%; margin-bottom: 0.6%" required><br>
                            <label>Confirm Password</label><br>
                            <input type="password" name="cms-password-confirm" {$cmsPasswordConf} style="width: 40%; padding: 0.4%; margin-bottom: 0.2%" required><br>
                            <div style="width: 40%; padding: 0.4%; margin-bottom: 0.9%">
                                Password Rules:<br>
                                At least 12 characters<br>
                                At least one capital and one lower case letter
                            </div>
                            <label>First Name</label><br>
                            <input type="text" name="cms-fname" {$cmsPassword} style="width: 40%; padding: 0.4%; margin-bottom: 0.6%" required><br>
                            <label>Last Name</label><br>
                            <input type="text" name="cms-lname" {$cmsPassword} style="width: 40%; padding: 0.4%; margin-bottom: 0.6%" required><br>
                            <button style="padding: 0.4%">Submit</button>
                            <input type="hidden" name="cms-super-admin-setup" value="true">
                        </form>
                        <script src="{$VENDORS_URL}jquery/jquery-3.4.1.min.js"></script>
HTML;


                    print pageError("CMS Administrator", $htmlData);
                    exit;
                }
            }*/
        }
        $this->database = $CONFIG['database'][$index]['name'];
    }

    function cmsDatabaseClass($index = 0) {
        global $CONFIG;
        $this->database = $CONFIG['database'][$index]['name'];
    }

    function select($strSql) {
        $arrData = array();

        $this->mysqli->set_charset("utf8");

        if (method_exists('mysqli_result', 'fetch_all')) {
            /*
             * If Fatal error: Call to undefined method mysqli_result::fetch_all()
             * mysqli_result::fetch_all() requires MySQL Native Driver (mysqlnd).
             * Ubuntu users can just do: sudo apt-get install php5-mysqlnd
             */

            if ($this->mysqli->query($strSql)) {
                $result = $this->mysqli->query($strSql);
                $arrData = $result->fetch_all(MYSQLI_ASSOC);
            } else {
                $arrData = array('error'=>$this->mysqli->error);
            }

            #print "Debug:\n\n";
            #print $strSql."\n\n";
            #print_r($this->mysqli->query($strSql));
            #print_r($arrData);
            #exit;
            #exit;
        } else {
            if ($this->mysqli->query($strSql)) {
                $result = $this->mysqli->query($strSql);
                #$arrData = $result->fetch_all(MYSQLI_ASSOC);
                while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                    $arrData[] = $row;
                }
            } else {
                $arrData = array('error'=>$this->mysqli->error);
            }
        }
        return $arrData;
    }

    function delete($tableName, $filterVariant) {
        $this->filterVariant = $filterVariant;

        $strSql = "DELETE FROM {$tableName}";

        $arrFieldNamesFilter = array();
        $arrFilter = array();
        if ($filterVariant !== NULL) {
            if (is_numeric($filterVariant)) {
                $data = $this->select("SHOW KEYS FROM {$tableName} WHERE Key_name = 'PRIMARY'");
                $arrFilter = array($data[0]['Column_name'] => $filterVariant);
            } else if (is_array($filterVariant)) {
                $arrFilter = $filterVariant;
            }

            $this->filterVariant = $arrFilter;

            $arrFieldNamesFilter = array_keys($arrFilter);

            $arrMapFilterPrep = function ($val, $key) {
                return $this->arrMapFilterPrep($val, $key);
            };
            $strSql .= " WHERE ".implode('  ', array_map($arrMapFilterPrep, $arrFieldNamesFilter, array_keys($arrFieldNamesFilter)))."";
        }

        $stmt = $this->mysqli->prepare($strSql);
        if ($stmt) {

            if ($filterVariant !== NULL) {
                $arrValues = array_values($arrFilter);

                $arrMapFilterBindValues = function ($val, $key) {
                    return $this->arrMapFilterBindValues($val, $key);
                };
                $arrFilters = array_map($arrMapFilterBindValues, $arrFieldNamesFilter, array_keys($arrFieldNamesFilter));

                $arrValuesTemp = array();
                foreach($arrValues as $Index => $Value) {
                    if (is_array($Value)) {
                        if (is_array($Value['value'])) {
                            foreach($Value['value'] as $sIndex => $sValue) {
                                $arrValuesTemp[] = $sValue;
                            }
                        } else {
                            $arrValuesTemp[] = $Value['value'];
                        }
                    } else {
                        $arrValuesTemp[] = $Value;
                    }
                }
                $arrValues = $arrValuesTemp;

                array_unshift($arrValues , $this->getDataTypeBind($arrValues));

                #print $strSql.'<hr>';
                #print_r($arrValues);
                #print '<hr>';

                call_user_func_array(array($stmt, 'bind_param'), $this->refValues($arrValues));
            }

            $stmt->execute();
            $error = $stmt->error;

            if ($error=='') {
                return array();
            } else {
                return array('error'=>$error);
            }
        } else {
            return array('error'=>$this->mysqli->error);
        }
    }

    function safe_select($tableName, $arrFields, $filterVariant = NULL, $arrFieldsOrder = NULL) {
        $arrData = array();

        $this->mysqli->set_charset("utf8");

        $strSqlOrder = ($arrFieldsOrder !== NULL) ? ' ORDER BY '.implode(', ', $arrFieldsOrder) : '';

        $strSql = "SELECT ".implode(', ', $arrFields)." FROM {$tableName}";

        $arrFieldNamesFilter = array();
        $arrFilter = array();
        if ($filterVariant !== NULL) {
            if (is_numeric($filterVariant)) {
                $data = $this->select("SHOW KEYS FROM {$tableName} WHERE Key_name = 'PRIMARY'");
                $arrFilter = array($data[0]['Column_name'] => $filterVariant);
            } else if (is_array($filterVariant)) {
                $arrFilter = $filterVariant;
            }

            $this->filterVariant = $arrFilter;

            $arrFieldNamesFilter = array_keys($arrFilter);

            $arrMapFilterPrep = function ($val, $key) {
                return $this->arrMapFilterPrep($val, $key);
            };
            $strSql .= " WHERE ".implode('  ', array_map($arrMapFilterPrep, $arrFieldNamesFilter, array_keys($arrFieldNamesFilter)))."";
            #print $strSql; exit;
        }

        $stmt = $this->mysqli->prepare($strSql.$strSqlOrder);


        if ($stmt) {

            if ($filterVariant !== NULL) {
                $arrValues = array_values($arrFilter);

                $arrMapFilterBindValues = function ($val, $key) {
                    return $this->arrMapFilterBindValues($val, $key);
                };
                $arrFilters = array_map($arrMapFilterBindValues, $arrFieldNamesFilter, array_keys($arrFieldNamesFilter));

                $arrValuesTemp = array();

                foreach($arrValues as $Index => $Value) {
                    if (is_array($Value)) {
                        if (is_array($Value['value'])) {
                            foreach($Value['value'] as $sIndex => $sValue) {
                                $arrValuesTemp[] = $sValue;
                            }
                        } else {
                            $arrValuesTemp[] = $Value['value'];
                        }
                    } else {
                        $arrValuesTemp[] = $Value;
                    }
                }
                $arrValues = $arrValuesTemp;

                array_unshift($arrValues , $this->getDataTypeBind($arrValues));

                call_user_func_array(array($stmt, 'bind_param'), $this->refValues($arrValues));
            }

            $stmt->execute();
            $error = $stmt->error;

            $arrFieldsRef = array();
            foreach($arrFields as $key => $value) {
                $arrFieldsRef[$value] = &$arr[$key];
            }

            #print_r($arrFields);
            #print '<hr>';

            call_user_func_array(array($stmt, 'bind_result'), $arrFieldsRef);

            #print_r($arrFieldsRef);

            while ($stmt->fetch()) {
                $arrRow = array();
                foreach($arrFieldsRef as $Index => $Value) {
                    $arrRow[$Index] = $Value;
                }
                $arrData[] = $arrRow;
            }

            $stmt->close();

            if ($error=='') {
                return $arrData;
            } else {
                return array('error'=>$this->mysqli->error);
            }
        } else {
            return array('error'=>$this->mysqli->error);
        }
    }

    function execute($strSql) {
        $stmt = $this->mysqli->prepare($strSql);
        if ($stmt) {
            $stmt->execute();
            $error = $stmt->error;
            $stmt->close();

            if ($error=='') {
                return array();
            } else {
                return array('error'=>$error);
            }
        } else {
            return array('error'=>$this->mysqli->error);
        }
    }

    function insert($tableName, $arrData) {
        global $CONFIG;

        $arrFieldNames = array_keys($arrData);
        $arrValues = array_values($arrData);

        $this->mysqli->set_charset("utf8");

        $strSql = "INSERT INTO {$tableName} (".implode(', ', $arrFieldNames).") VALUES (".implode(', ', array_map(function($val) { return '?'; }, $arrFieldNames)).")";

        $stmt = $this->mysqli->prepare($strSql);

        $error = '';
        if ($stmt) {
            array_unshift($arrValues , $this->getDataTypeBind($arrValues));

            call_user_func_array(array($stmt, 'bind_param'), $this->refValues($arrValues));

            $stmt->execute();
            $newId = $stmt->insert_id;

            if ($stmt->error<>"") {
                return array('error'=>$stmt->error);
            } else {
                $data = $this->select("SHOW KEYS FROM {$tableName} WHERE Key_name = 'PRIMARY'");
                return array('name'=>$data[0]['Column_name'], 'value'=>$newId);
            }
            $stmt->close();
        } else {
            return array('error'=>$this->mysqli->error);
        }
    }

    function update($tableName, $arrData, $filterVariant) {
        global $CONFIG;

        $this->filterVariant = $filterVariant;

        $this->mysqli->set_charset("utf8");

        $arrFieldNames = array_keys($arrData);
        $strSql = "UPDATE {$tableName} SET ".implode(', ', array_map(function($val) { return $val.' = ?'; }, $arrFieldNames));

        $arrFieldNamesFilter = array();
        $arrFilter = array();
        if ($filterVariant !== NULL) {
            if (is_numeric($filterVariant)) {
                $data = $this->select("SHOW KEYS FROM {$tableName} WHERE Key_name = 'PRIMARY'");
                $arrFilter = array($data[0]['Column_name'] => $filterVariant);
            } else if (is_array($filterVariant)) {
                $arrFilter = $filterVariant;
            }

            $this->filterVariant = $arrFilter;

            $arrFieldNamesFilter = array_keys($arrFilter);

            $arrMapFilterPrep = function ($val, $key) {
                return $this->arrMapFilterPrep($val, $key);
            };
            $strSql .= " WHERE ".implode('  ', array_map($arrMapFilterPrep, $arrFieldNamesFilter, array_keys($arrFieldNamesFilter)))."";
        }
        $stmt = $this->mysqli->prepare($strSql);

        $this->sql = $strSql;

        if ($stmt) {
            $arrValues = array_values($arrData);

            #combine filter array object
            $arrMapFilterBindValues = function ($val, $key) {
                return $this->arrMapFilterBindValues($val, $key);
            };

            $arrFilters = array_map($arrMapFilterBindValues, $arrFieldNamesFilter, array_keys($arrFieldNamesFilter));
            $arrValues = array_merge($arrValues, $arrFilters);

            $arrValuesTemp = array();
            foreach($arrValues as $Index => $Value) {
                if (is_array($Value)) {
                    if (is_array($Value['value'])) {
                        foreach($Value['value'] as $sIndex => $sValue) {
                            $arrValuesTemp[] = $sValue;
                        }
                    } else {
                        $arrValuesTemp[] = $Value['value'];
                    }
                } else {
                    $arrValuesTemp[] = $Value;
                }
            }
            $arrValues = $arrValuesTemp;

            array_unshift($arrValues , $this->getDataTypeBind($arrValues));

            if (call_user_func_array(array($stmt, 'bind_param'), $this->refValues($arrValues)) === false) {
                if ($CONFIG['environment'] == 'development') {
                    print $strSql."\n\n";
                    print_r($arrValues);
                    exit;
                }
            }
            $stmt->execute();
            $error = $stmt->error;
            $stmt->close();

            if ($error=='') {
                return array();
            } else {
                return array('error'=>$error);
            }
        } else {
            return array('error'=>$this->mysqli->error);
        }
    }

    private function refValues($arr){
        if (strnatcmp(phpversion(),'5.3') >= 0) //Reference is required for PHP 5.3+
        {
            $refs = array();
            foreach($arr as $key => $value) {
                $refs[$key] = &$arr[$key];
            }
            return $refs;
        }
        return $arr;
    }

    private function arrMapFilterPrep($val, $key) {
        $operator = '';
        $value = ' = ?';
        if ($key > 0) {
            $operator = ' AND ';

            if (is_array($this->filterVariant[$val])) {
                if (isset($this->filterVariant[$val]['operator']))
                    $operator = " {$this->filterVariant[$val]['operator']} ";
            }
        }

        if (is_array($this->filterVariant[$val])) {
            if (isset($this->filterVariant[$val]['condition'])) {
                $value = "{$this->filterVariant[$val]['condition']} (?)";

                if (strtoupper(trim($this->filterVariant[$val]['condition'])) == 'IN' || strtoupper(trim($this->filterVariant[$val]['condition'])) == 'NOT IN') {
                    if (is_array($this->filterVariant[$val]['value'])) {
                        $arrValPrep = array();
                        foreach($this->filterVariant[$val]['value'] as $Index => $Value) {
                            $arrValPrep[] = "?";
                        }
                        $value = "{$this->filterVariant[$val]['condition']} (".implode(", ", $arrValPrep).")";
                    }
                }
            }
        }

        return "{$operator} {$val} {$value}";
    }

    private function arrMapFilterBindValues($val, $key) {
        $retVal = '';

        if (is_array($this->filterVariant[$val])) {
            $retVal = $this->filterVariant[$val]['value'];
        } else {
            $retVal = $this->filterVariant[$val];
        }

        return $retVal;
    }

    private function getDataTypeBind($arrValues) {
        $arrRet = array();
        foreach($arrValues as $Index => $Value) {
            $arrRet[] = strval(gettype($Value))[0];
        }
        return implode('', $arrRet);
    }
}