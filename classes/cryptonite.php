<?php

/**********************************************************************************************************
'*	String Encoding/Decoding function (Cryptonite - Function PHP Version)  							  *
'*	Programmed by: Ryan P. Sandigan													  				  	  *
'*	Email: ryanzkizen@gmail.com																			  *
'*	Date Created: 05/26/2004																			  *
'*  Date Updated: 10/19/2016                                                                              *
'**********************************************************************************************************

'	- encode(gStr)					-> return the ecrypted string
'	- decode(gStr)					-> return the decrpted string
 ***********************************************************************************************************/

class cmsCryptonite {
    private $EncryptKey = array();

    #region -- Constructor --
    function __construct($pArr = NULL) {
        if (is_null($pArr)) {
            $this->EncryptKey = array('A', 'E', 'H', 'B', 'F', 'I', 'C', 'G', 'J', 'D');
        } else {
            $this->EncryptKey = $pArr;
        }
    }
    #endregion

    function decode($gStr) {
        $i = 0;
        $sLen = 0;
        $gHex = ""; $cHex = ""; $nHex = 0; $dStr = "";
        $rLen = ""; $sEnc = ""; $rand = 0;

        $rLen = substr(strrev($gStr), 0, 1);

        if (!is_numeric($rLen)) {
            return("");
        }

        if ($rLen == "0") {
            $sEnc = strrev(substr($gStr, 0, strlen($gStr) - 1));
        } else {
            $sEnc = substr($gStr, 0, strlen($gStr) - 1);
        }

        $sLen = strlen($sEnc);

        for($i=0; $i<$sLen; $i=$i+4) {
            $gHex = $this->Right(substr($sEnc, $i, 4), strlen(substr($sEnc, $i, 4)) - 1);
            $rand = ord($this->Right($this->Left($gHex, strlen($gHex)), 1));
            $cHex = $this->Left($gHex, strlen($gHex) - 1);

            if ($rand >= 65 && $rand <= 70) {
                $nHex = strtoupper(strrev($cHex));
            } else if ($rand >= 71 && $rand <= 75) {
                $nHex = strrev($cHex);
            } else if ($rand >= 76 && $rand <= 80) {
                $nHex = strtoupper(strrev($cHex));
            } else if ($rand >= 81 && $rand <= 85) {
                $nHex = strtoupper($cHex);
            } else if ($rand >= 86 && $rand <= 90) {
                $nHex = $cHex;
            }

            $nHex = $this->inverseHex($nHex);

            $dStr = $dStr . chr(doubleval(hexdec($nHex)));
        }

        return($dStr);
    }
    function encode($gStr) {
        $lpEx = false;
        $sBuf = "";
        $sLen = strlen($gStr);
        $rand = 0;
        $i = 0;
        for($i=1; $i<=$sLen; $i++) {
            while ($lpEx <> true) {
                $rand = rand(1, 90);
                if ($rand > 64) { $lpEx = true; }
            }

            //CONCAT ENCRYPTED STRING
            $sBuf = $sBuf . chr($rand) . $this->rndChar($this->inverseHex(strtoupper(dechex((ord($this->Right($this->Left($gStr, $i), 1)))))));

            $lpEx = false;
        }
        return($this->rndEncStr($sBuf));
    }

    private function Right($sVal, $strlen) {
        return strrev(substr(strrev($sVal), 0, $strlen));
    }
    private function Left($sVal, $strlen) {
        return substr($sVal, 0, $strlen);
    }
    //REPLACING THE 0 - 9 TO ASSIGNED CHARACTER AND
    //CONVERTING ASSIGNED CHARACTER TO ORIGINAL VALUE
    private function inverseHex($gStr) {
        $lHex = ""; $lHex = $this->Left($gStr, 1);  //<- GET LEFT HEX NO. (LEFT STRING CHAR)
        $rHex = ""; $rHex = $this->Right($gStr, 1); //<- GET RIGHT HEX NO. (RIGHT STRING CHAR)
        $chHex = ""; //<- GET THE lHex OR rHex VALUE
        $i = 0; //<- COUNTING NUMBER (0-9)
        $j = 0; //<- SWITCHING NUMBER (0 - LEFT HEX / 1 - RIGHT HEX)

        $nuHex = $this->EncryptKey; //array("A", "E", "H", "B", "F", "I", "C", "G", "J", "D"); //<- ASSIGNED CHARACTER
        /*$nuHex[0] = "A"; $nuHex[1] = "E"; $nuHex[2] = "H";
        $nuHex[3] = "B"; $nuHex[4] = "F"; $nuHex[5] = "I";
        $nuHex[6] = "C"; $nuHex[7] = "G"; $nuHex[8] = "J";
        $nuHex[9] = "D";*/

        for($j=0; $j<=1; $j++) {
            if ($j == 0) {
                $chHex = $lHex; //<- GET LEFT HEX NO. (LEFT STRING CHAR)
            } else {
                $chHex = $rHex; //<- GET RIGHT HEX NO. (RIGHT STRING CHAR)
            }

            if (is_numeric($chHex)) { //<- HEXADECIMAL (LONG)
                for($i=0; $i<=9; $i++) {
                    if (strval($i) == $chHex) {
                        if ($j == 0) {
                            $lHex = $nuHex[$i]; //<- REPLACE A NEW CHARACTER
                        } else {
                            $rHex = $nuHex[$i]; //<- REPLACE A NEW CHARACTER
                        }
                        break;
                    }
                }
            } else { //<- ASCII CODE (STRING)
                for($i=0; $i<=9; $i++) {
                    if ($nuHex[$i] == $chHex) {
                        if ($j == 0) {
                            $lHex = $i; //<- RETURN TO ORIGINAL HEX NO.
                        } else {
                            $rHex = $i; //<- RETURN TO ORIGINAL HEX NO.
                        }
                        break;
                    }
                }
            }
        }
        return($lHex . $rHex);
    }
    private function rndEncStr($gStr) {
        $rand = 0;
        $rand = rand(0, 1);
        if ($rand == 0) {
            return(strrev($gStr) . $rand);
        } else {
            return($gStr . $rand);
        }
    }
    private function rndChar($gStr) {
        $lHex = $this->Left($gStr, 1);
        $rHex = $this->Right($gStr, 1);
        $lpEx = false;
        $rand = 0;
        $nHex = "";

        while ($lpEx != true) {
            $rand = rand(1, 90);
            if ($rand > 64) { $lpEx = true; }
        }

        if ($rand >= 65 && $rand <= 70) {
            $nHex =  strtolower(strrev($gStr)) . chr($rand);
        } else if ($rand >= 71 && $rand <= 75) {
            $nHex = strrev($gStr) . chr($rand);
        } else if ($rand >= 76 && $rand <= 80) {
            $nHex = $rHex . strtolower($lHex) . chr($rand);
        } else if ($rand >= 81 && $rand <= 85) {
            $nHex = strtolower($lHex) . $rHex . chr($rand);
        } else if ($rand >= 86 && $rand <= 90) {
            $nHex = $gStr . chr($rand);
        }

        return($nHex);
    }
}