<?php

class sms_class extends Model
{
    function __construct()
    {
        parent::__construct();
        $this->dbClass->cmsDatabaseClass(0);
    }

    function getVendorId() {
        global $CONFIG;
        $SEASIDE_Market_Id = (isset($_SESSION[$CONFIG['cookie']['prefix']."_cms_market"])) ? intval($_SESSION[$CONFIG['cookie']['prefix']."_cms_market"]) : 0;

        $tSQL = sprintf("SELECT * FROM seaside_market WHERE SEASIDE_Market_Id = %d ORDER BY SEASIDE_Market_Name, SEASIDE_Market_Id", $this->dbClass->mysqli->real_escape_string($SEASIDE_Market_Id));
        if (CMS_Users_Type == 1) {
            $tSQL = sprintf(
                "
                          SELECT 
                              seaside_market.SEASIDE_Market_Id
                          FROM 
                              seaside_market
                          WHERE 
                              SEASIDE_Market_Id = %d
                          INNER JOIN seaside_market_users ON seaside_market_users.SEASIDE_Market_Id = seaside_market.SEASIDE_Market_Id AND seaside_market_users.CMS_Users_Id = %d
                          ORDER BY seaside_market.SEASIDE_Market_Name
                ",
                $this->dbClass->mysqli->real_escape_string($SEASIDE_Market_Id),
                $this->dbClass->mysqli->real_escape_string(CMS_Users_Id)
            );
        }

        $arrData = $this->dbClass->select($tSQL);
        if (count($arrData) == 0) {
            $tSQL = "SELECT * FROM seaside_market ORDER BY SEASIDE_Market_Name, SEASIDE_Market_Id";
            if (CMS_Users_Type == 1) {
                $tSQL = sprintf(
                    "
                              SELECT 
                                  seaside_market.SEASIDE_Market_Id
                              FROM 
                                  seaside_market
                              INNER JOIN seaside_market_users ON seaside_market_users.SEASIDE_Market_Id = seaside_market.SEASIDE_Market_Id AND seaside_market_users.CMS_Users_Id = %d
                              ORDER BY seaside_market.SEASIDE_Market_Name
                    ",
                    $this->dbClass->mysqli->real_escape_string(CMS_Users_Id)
                );
            }
            $arrData = $this->dbClass->select($tSQL);
            if (count($arrData) > 0) {
                $SEASIDE_Market_Id = $arrData[0]["SEASIDE_Market_Id"];
            }
        }
        $_SESSION[$CONFIG['cookie']['prefix']."_vendor"] = $SEASIDE_Market_Id;

        return $SEASIDE_Market_Id;
    }
}