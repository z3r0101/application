<?php
class cmsTools
{
    #region -- Constructor --
    public function __construct() {
    }
    #endregion

    static public function makeSlug( $string, $separator = '-' )
    {
        $accents_regex = '~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i';
        $special_cases = array( '&' => 'and', "'" => '');
        $string = mb_strtolower( trim( $string ), 'UTF-8' );
        $string = str_replace( array_keys($special_cases), array_values( $special_cases), $string );
        $string = preg_replace( $accents_regex, '$1', htmlentities( $string, ENT_QUOTES, 'UTF-8' ) );
        $string = preg_replace("/[^a-z0-9]/u", "$separator", $string);
        $string = preg_replace("/[$separator]+/u", "$separator", $string);
        $string = rtrim($string,'-');
        return $string;
    }


    /**
     * Copy a file, or recursively copy a folder and its contents
     * @author      Aidan Lister <aidan@php.net>
     * @version     1.0.1
     * @link        http://aidanlister.com/2004/04/recursively-copying-directories-in-php/
     * @param       string   $source    Source path
     * @param       string   $dest      Destination path
     * @param       int      $permissions New folder creation permissions
     * @return      bool     Returns true on success, false on failure
     */
    static public function xcopy($source, $dest, $permissions = 0755)
    {
        // Check for symlinks
        if (is_link($source)) {
            return symlink(readlink($source), $dest);
        }

        // Simple copy for a file
        if (is_file($source)) {
            return copy($source, $dest);
        }

        // Make destination directory
        if (!is_dir($dest)) {
            mkdir($dest, $permissions);
        }

        // Loop through the folder
        $dir = dir($source);
        while (false !== $entry = $dir->read()) {
            // Skip pointers
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            // Deep copy directories
            self::xcopy("$source/$entry", "$dest/$entry", $permissions);
        }

        // Clean up
        $dir->close();
        return true;
    }

    public static function rmDir($dirPath) {
        if (!is_dir($dirPath)) {
            throw new InvalidArgumentException("$dirPath must be a directory");
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '{,.}[!.,!..]*',GLOB_MARK|GLOB_BRACE);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::rmDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }

    public static function timeTodayDateYesterday($pTimeStamp) {
        $retDT = "";
        if ($pTimeStamp >= strtotime("today"))
            $retDT = date("h:i a", $pTimeStamp);
        else { #if ($pTimeStamp >= strtotime("yesterday"))
            if (date("Y", $pTimeStamp) == date("Y"))
                $retDT = date("M d", $pTimeStamp);
            else
                $retDT = date("m/d/Y", $pTimeStamp);
        }

        return $retDT;
    }

    public static function getMimeType($filename)
    {
        $mimetype = false;
        if(function_exists('finfo_open')) {
            // open with FileInfo
        } elseif(function_exists('getimagesize')) {
            // open with GD
        } elseif(function_exists('exif_imagetype')) {
            // open with EXIF
        } elseif(function_exists('mime_content_type')) {
            $mimetype = mime_content_type($filename);
        }
        return $mimetype;
    }

    public static function formatBytes($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = array('', 'K', 'M', 'G', 'T');

        return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
    }

    public static function isImage($pFilePath) {
        if(@is_array(getimagesize($pFilePath))){
            return true;
        } else {
            return false;
        }
    }

    public static function getFileMimeExtension($file) {
        $arrData = array(
            'image/jpeg'=>'jpg',
            'image/gif'=>'gif',
            'image/png'=>'png',
            'image/tiff'=>'tif',
            'image/x-icon'=>'ico',
            'application/pdf'=>'pdf',
            'application/msword'=>'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'=>'docx',
            'application/vnd.ms-excel'=>'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'=>'xlxs',
            'application/vnd.ms-powerpoint'=>'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation'=>'pptx',
            'text/plain'=>'txt',
            'video/mpeg'=>'mpeg',
            'audio/x-wav'=>'wav',
            'video/webm'=>'webm',
            'audio/webm'=>'weba',
            'video/3gpp'=>'3gp',
            'audio/3gpp'=>'3gp',
            'video/3gpp2'=>'3g2',
            'audio/3gpp2'=>'3g2',
            'audio/ogg'=>'oga',
            'video/ogg'=>'ogv',
            'audio/midi'=>'mid',
            'audio/aac'=>'aac',
            'video/x-msvideo'=>'avi'
        );

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file);
        finfo_close($finfo);

        return isset($arrData[$mimeType]) ? $arrData[$mimeType] : '';
    }

    public static function ellipsis($input, $count = 160) {
        $str = $input;
        if( strlen( $input) > $count) {
            $str = explode( "\n", wordwrap( $input, $count));
            $str = $str[0] . '...';
        }
        return $str;
    }

    public static function DOMInnerHTML(DOMNode $element)
    {
        $innerHTML = "";
        $children  = $element->childNodes;

        foreach ($children as $child)
        {
            $innerHTML .= $element->ownerDocument->saveHTML($child);
        }

        return $innerHTML;
    }

    /**
     * Turn all URLs in clickable links.
     *
     * @param string $value
     * @param array  $protocols  http/https, ftp, mail, twitter
     * @param array  $attributes
     * @param string $mode       normal or all
     * @return string
     */
    public static function linkify($value, $protocols = array('http', 'mail'), array $attributes = array('target'=>'_blank'))
    {
        // Link attributes
        $attr = '';
        foreach ($attributes as $key => $val) {
            $attr = ' ' . $key . '="' . htmlentities($val) . '"';
        }

        $links = array();

        // Extract existing links and tags
        $value = preg_replace_callback('~(<a .*?>.*?</a>|<.*?>)~i', function ($match) use (&$links) { return '<' . array_push($links, $match[1]) . '>'; }, $value);

        // Extract text links for each protocol
        foreach ((array)$protocols as $protocol) {
            switch ($protocol) {
                case 'http':
                case 'https':   $value = preg_replace_callback('~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i', function ($match) use ($protocol, &$links, $attr) { if ($match[1]) $protocol = $match[1]; $link = $match[2] ?: $match[3]; return '<' . array_push($links, "<a $attr href=\"$protocol://$link\">$link</a>") . '>'; }, $value); break;
                case 'mail':    $value = preg_replace_callback('~([^\s<]+?@[^\s<]+?\.[^\s<]+)(?<![\.,:])~', function ($match) use (&$links, $attr) { return '<' . array_push($links, "<a $attr href=\"mailto:{$match[1]}\">{$match[1]}</a>") . '>'; }, $value); break;
                case 'twitter': $value = preg_replace_callback('~(?<!\w)[@#](\w++)~', function ($match) use (&$links, $attr) { return '<' . array_push($links, "<a $attr href=\"https://twitter.com/" . ($match[0][0] == '@' ? '' : 'search/%23') . $match[1]  . "\">{$match[0]}</a>") . '>'; }, $value); break;
                default:        $value = preg_replace_callback('~' . preg_quote($protocol, '~') . '://([^\s<]+?)(?<![\.,:])~i', function ($match) use ($protocol, &$links, $attr) { return '<' . array_push($links, "<a $attr href=\"$protocol://{$match[1]}\">{$match[1]}</a>") . '>'; }, $value); break;
            }
        }

        // Insert all link
        return preg_replace_callback('/<(\d+)>/', function ($match) use (&$links) { return $links[$match[1] - 1]; }, $value);
    }

    public static function array_diff_assoc_recursive($array1, $array2)
    {
        foreach($array1 as $key => $value)
        {
            if(is_array($value))
            {
                if(!isset($array2[$key]))
                {
                    $difference[$key] = $value;
                }
                elseif(!is_array($array2[$key]))
                {
                    $difference[$key] = $value;
                }
                else
                {
                    $new_diff = cmsTools::array_diff_assoc_recursive($value, $array2[$key]);
                    if($new_diff != false)
                    {
                        $difference[$key] = $new_diff;
                    }
                }
            }
            elseif(!isset($array2[$key]) || $array2[$key] != $value)
            {
                $difference[$key] = $value;
            }
        }
        return !isset($difference) ? 0 : $difference;
    }

    public static function compare($array1, $array2)
    {
        $result = array();
        foreach ($array1 as $key => $value) {
            if (!is_array($array2) || !array_key_exists($key, $array2)) {
                $result[$key] = $value;
                continue;
            }
            if (is_array($value)) {
                $recursiveArrayDiff = static::compare($value, $array2[$key]);
                if (count($recursiveArrayDiff)) {
                    $result[$key] = $recursiveArrayDiff;
                }
                continue;
            }
            if ($value != $array2[$key]) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    public static function excelColumnRange($lower, $upper) {
        ++$upper;
        for ($i = $lower; $i !== $upper; ++$i) {
            yield $i;
        }
    }

    public static function numberToColumnName($num) {
        $numeric = ($num - 1) % 26;
        $letter = chr(65 + $numeric);
        $num2 = intval(($num - 1) / 26);
        if ($num2 > 0) {
            return static::numberToColumnName($num2) . $letter;
        } else {
            return $letter;
        }
    }

    public static function getClientIP() {
        $ip_address = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP']))
        {
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        }
        //whether ip is from proxy
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        //whether ip is from remote address
        else
        {
            $ip_address = $_SERVER['REMOTE_ADDR'];
        }
        return $ip_address;
    }

    public static function decToFraction($float) {
        // 1/2, 1/4, 1/8, 1/16, 1/3 ,2/3, 3/4, 3/8, 5/8, 7/8, 3/16, 5/16, 7/16,
        // 9/16, 11/16, 13/16, 15/16
        $whole = floor ( $float );
        $decimal = $float - $whole;
        $leastCommonDenom = 48; // 16 * 3;
        $denominators = array (2, 3, 4, 8, 16, 24, 48 );
        $roundedDecimal = round ( $decimal * $leastCommonDenom ) / $leastCommonDenom;
        if ($roundedDecimal == 0)
            return $whole;
        if ($roundedDecimal == 1)
            return $whole + 1;
        foreach ( $denominators as $d ) {
            if ($roundedDecimal * $d == floor ( $roundedDecimal * $d )) {
                $denom = $d;
                break;
            }
        }
        return ($whole == 0 ? '' : $whole) . " " . ($roundedDecimal * $denom) . "/" . $denom;
    }

    public static function nl2p($string, $line_breaks = true, $xml = true) {
        $string = str_replace(array('<p>', '</p>', '<br>', '<br />'), '', $string);
        // It is conceivable that people might still want single line-breaks
        // without breaking into a new paragraph.
        if ($line_breaks == true)
            return '<p>'.preg_replace(array("/([\n]{2,})/i", "/([^>])\n([^<])/i"), array("</p>\n<p>", '$1<br'.($xml == true ? ' /' : '').'>$2'), trim($string)).'</p>';
        else
            return '<p>'.preg_replace(
                    array("/([\n]{2,})/i", "/([\r\n]{3,})/i","/([^>])\n([^<])/i"),
                    array("</p>\n<p>", "</p>\n<p>", '$1<br'.($xml == true ? ' /' : '').'>$2'),
                    trim($string)).'</p>';
    }

    public static function str_replace_once($search, $replace, $subject)
    {
        $pos = strpos($subject, $search);
        if ($pos !== false) {
            return substr_replace($subject, $replace, $pos, strlen($search));
        }
        return $subject;
    }
}