<?php

// functions

function logEvent($data = null, $logLevel = 3) {
    /*
    logging needs attention. right now every time any event is
    logged, the app parses the entire log file as a JSON object, adds the new
    event to the array, and then saves the file. as you can guess, this means
    that everything gets incredibly slow the larger the log file gets. this is
    just bad design, tbh.

    it won't take much to fix, just open the file in append mode
    instead and yeet the new event to the end. this will only affect the
    log viewer on the admin page, which is garbage anyway, nothing else
    depends on this terrible design.
    */
    global $oset;
    global $api_path;
    global $api_method;
    if (isset($oset['audit_log']) and $oset['audit_log'] === false) { return true; }
    /*
    LOG LEVELS
    1 - garbage
    2 - trace
    3 - debug
    4 - info [default]
    5 - warning
    6 - error
    */
    if (empty($oset['log_level']) or !is_numeric($oset['log_level'])) {
        $logLevelMatch = 4;
    } else {
        $logLevelMatch = $oset['log_level'];
    }
    if (!empty($oset['audit_log_file'])) {
        $logFile = str_replace("__app_root__", $oset['file_root'], $oset['audit_log_file']);
    } else {
        $logFile = $oset['file_root'] . '/data/audit_log.json';
    }
    $logFileTxt = $oset['file_root'] . '/data/audit_log.json.log';
    if ($api_method == "HEAD") { return true; }
    if ($logLevel < $logLevelMatch) { return true; }
    $api_path_str = implode("/", $api_path);
    $filetype = pathinfo($api_path_str, PATHINFO_EXTENSION);
    //$filename = pathinfo($api_path_str, PATHINFO_BASENAME);
    if (isset($data['password'])) { unset($data['password']); }
    if ($api_path_str == "/api/account/timeout") { return true; }
    if (isset($oset['audit_log']) and $oset['audit_log'] === false) {
        return true;
    }
    switch ($api_method) {
        case "GET":
            switch ($logLevelMatch) {
                case 1:
                case 2:
                    break;
                case 3:
                    if ($api_path[1] != "api") { return true; }
                    break;
                default:
                    return true;
            }
            break;
        case "OPTIONS":
            if ($logLevelMatch > 2) { return true; }
    }
    switch (strtolower($filetype)) {
        case "png":
        case "gif":
        case "js":
        case "css":
        case "svg":
        case "html":
        case "jpg":
        case "jpeg":
            return true;
            break;
    }
    if (!file_exists($logFile)) {
        $logData = [];
    } else {
        $logData = parse_file($logFile);
    }
    if (isset($_SESSION['id'])) {
        $user_id = getUserProfile($_SESSION['id'])["fullname"];
    } else {
        $user_id = "anonymous";
    }
    if (!isset($url_query)) {
        $url_query = null;
    }
    $eventData = [
        "log_id" => generateRandomString(16),
        "user" => $user_id,
        "time" => date('Y/m/d H:i:s ' . time()),
        "method" => $api_method,
        "path" => $api_path_str,
        "addr" => [
            $_SERVER['REMOTE_ADDR']
        ],
        "args" => $url_query,
        "data" => $data,
        "level" => $logLevelMatch
    ];
    $eventDataTxt = json_encode($eventData);
    $fp = fopen($logFileTxt, 'a');
    fwrite($fp, $eventDataTxt . "\n");
    fclose($fp);
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        array_push($eventData['addr'], $_SERVER['HTTP_X_FORWARDED_FOR']);
    }
    if (empty($logData)) { $logData = []; }
    if (empty($eventData)) { $eventData = []; }
    array_push($logData, $eventData);
    if (emit_file($logFile, $logData)) {
        return true;
    } else {
        logError("failed to save audit log at {$logFile}", "auditlog");
        return false;
    }
}

function getDb($query, $a = null) {
    global $oset;
    if ($a === null) {
        $a = [
            $oset['sql_host'],
            $oset['sql_name'],
            $oset['sql_user'],
            $oset['sql_pass']
        ];
    } elseif (count($a) != 4) {
        return false;
    }
    $dbx = new mysqli($a[0], $a[2], $a[3], $a[1]);
    $dbq = $dbx->query($query);
    if (mysqli_error($dbx)) {
        $err = mysqli_error($dbx);
        $dbx->close();
        return [false, 500, $err];
    }
    $dbx->close();
    return [true, 200, $dbq];
}

function dbEsc($string, $a = null) {
    global $oset;
    if ($a === null) {
        $a = [
            $oset['sql_host'],
            $oset['sql_name'],
            $oset['sql_user'],
            $oset['sql_pass']
        ];
    } elseif (count($a) != 4) {
        return false;
    }
    $dbx = new mysqli($a[0], $a[2], $a[3], $a[1]);
    $escString = mysqli_real_escape_string($dbx, $string);
    if (mysqli_error($dbx)) {
        $err = mysqli_error($dbx);
        $dbx->close();
        return [false, 500, $err];
    }
    $dbx->close();
    return [true, 200, $escString];
}

function getDbArray($query, $a = null) {
    global $oset;
    if ($a === null) {
        $a = [
            $oset['sql_host'],
            $oset['sql_name'],
            $oset['sql_user'],
            $oset['sql_pass']
        ];
    } elseif (count($a) != 4) {
        return false;
    }
    $exec = getDb($query, $a);
    if ($exec[0] === false) {
        //logError($exec, 'getDbArray:getDb');
        return [false, 500, $exec];
    }
    $sqlres = $exec[2];
    $ret = [];
    while ($row = mysqli_fetch_assoc($sqlres)) {
        array_push($ret, $row);
    }
    return [true, 200, $ret];
}

function logError($errorString, $context = "") {
    global $api_path;
    global $api_method;
    if (is_array($api_path) and count($api_path) > 1) {
        $api_path_str = implode('/', $api_path);
    } else {
        $api_path_str = "";
    }
    error_log(json_encode([$context . " NOTICE", $api_method . ' >> ' . $api_path_str, $_SERVER['REMOTE_ADDR'], $errorString], JSON_PRETTY_PRINT));
}

function minimizeCSSsimple($css) {
    $css = preg_replace('/\/\*((?!\*\/).)*\*\//', '', $css); // negative look ahead
    $css = preg_replace('/\s{2,}/', ' ', $css);
    $css = preg_replace('/\s*([:;{}])\s*/', '$1', $css);
    $css = preg_replace('/;}/', '}', $css);
    return $css;
}

function checkPasswordStrength($pwd) {
    if (preg_match("#.*^(?=.{8,20})(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*\W).*$#", $pwd)) {
        return true;
    }
    return false;
}

function moveUserToGroup($user, $group) {
    global $oset;
    global $coreUsers;
    $user_found = false;
    $group_found = false;
    foreach ($coreUsers['users'] as $userName => $userConf) {
        if ($userConf['id'] == $user) {
            $user_name = $userName;
            $user_found = true;
            $userConf['group'] = $group;
            $coreUsers['users'][$userName] = $userConf;
        }
    }
    if ($user_found === false) { return [false, 404, 'no such user ' . $user]; }
    //apiDie($coreUsers['users'][$user_name]);
    foreach ($coreUsers['groups'] as $groupName => $groupConf) {
        if ($groupConf['id'] == $group) {
            $group_found = true;
            if (!in_array($user, $groupConf['users'])) {
                array_push($groupConf['users'], $user);
            }
        } else {
            $unset = [];
            foreach ($groupConf['users'] as $group_user) {
                if ($group_user != $user) {
                    array_push($unset, $group_user);
                }
            }
            $groupConf['users'] = $unset;
        }
        $coreUsers['groups'][$groupName] = $groupConf;
    }
    if ($group_found === false) {
        return [false, 404, 'no such group ' . $group];
    }
    /*
    foreach ($coreUsers['groups'] as $groupName => $groupConf) {
        if ($groupConf['id'] != $group) {
            $uset = [];
            foreach ($groupConf['users'] as $group_user) {
                if ($group_user != $user) {
                    array_push($uset, $group_user);
                }
            }
            $groupConf['users'] = $uset;
        }
        $coreUsers['groups'][$groupName] = $groupConf;
    }
    */
    $saveData = ['users'=>$coreUsers['users'],'groups'=>$coreUsers['groups']];
    $saveExec = saveUsersAndGroups($saveData);
    //$saveData = saveUsersAndGroups($coreUsers['users'][$user_name]['group']);
    if ($saveExec === true) {
        return [true, 200, $coreUsers['users'][$user_name]['group']];
    } else {
        return [false, 200, 'failed to store data.'];
    }
    /*
    if (emit_file($oset['file_root'] . '/data/users.json', $coreUsers['users'][$user_name]['group'])) {
        return [true, 200, $coreUsers['users'][$user_name]['group']];
    } else {
        return [false, 500, 'failed to store data'];
    }
    */
}

function getOptionScript($option) {
    global $oset;
    global $plugins;
    $script_out = "";
    if (file_exists(__DIR__ . '/settings_js/' . $option . '/update.js')) {
        $script_out .= str_replace(["\r", "\n"], '', file_get_contents(__DIR__ . '/settings_js/' . $option . '/update.js'));
    }
    foreach ($plugins as $pluginName => $pluginConf) {
        if (file_exists(__DIR__ . '/../plugins/' . $pluginName . '/settings_js/' . $option . '/update.js')) {
            $script_out .= str_replace(["\r", "\n"], '', file_get_contents(__DIR__ . '/../plugins/' . $pluginName . '/settings_js/' . $option . '/update.js'));
        }
    }
    return $script_out;
}

function getOptionChange($option, $value) {
    global $changes_full;
    /*
    global $oset;
    global $oset_schema;
    $schema = $oset_schema;
    */
    $set = buildSystemSettings(true);
    $schema = $set[0];
    $oset = $set[1];
    if (!isset($schema[$option])) {
        return false;
    }
    $source = $schema[$option]['source'];
    $changes = [

        $option => [
            "value" => $value,
            "source" => $source,
            "script" => getOptionScript($option)
        ]
    ];
    if (is_bool($value)) {
        $revVal = revBool($value);
        if ($value === true) {
            $strValue = "on";
            $revStrValue = "off";
        } else {
            $strValue = "off";
            $revStrValue = "on";
        }
    } else {
        $strValue = $value;
    }
    if (isset($schema[$option]['require'])) {
        foreach ($schema[$option]['require'] as $reqName => $reqStates) {
            if (isset($reqStates[$strValue]) and $oset[$reqName] != $reqStates[$strValue] and $reqName != $option) {
                $changes[$reqName] = [
                    "value" => $reqStates[$strValue],
                    "source" => $schema[$reqName]['source'],
                    "script" => getOptionScript($reqName)
                ];
            }
        }
    }
    return $changes;
}

function setOptionChange($option, $conf) {
    global $oset;
    if ($conf['source'] == "core") {
        $setFile = $oset['file_root'] . '/utils/settings.json';
        $setData = parse_file($setFile);
        $setData[$option] = $conf['value'];
    } else {
        $setFile = $oset['file_root'] . '/plugins/' . $conf['source'] . '/settings_override.json';
        if (file_exists($setFile)) {
            $setData = parse_file($setFile);
        } else {
            $setData = [];
        }
        $setData[$option] = $conf['value'];
    }
    $saveData = emit_file($setFile, $setData);
    if ($saveData === false) {
        return false;
    } else {
        return ["result" => true, "script" => getOptionScript($option)];
    }
}

function revBool($bool, $str = false) {

    if (!is_bool($bool)) { return false; };
    if ($bool === true) {
        $res = false;
    } else {
        $res = true;
    }
    if ($str === true) {
        if ($res === true) {
            return "on";
        } else {
            return "off";
        }
    } else {
        return $res;
    }
}

// -- color functions

function greyBright($hex, $steps = 96) {
    $bv = isLightColor($hex, true);
    return adjustBrightness(rgb2hex([$bv, $bv, $bv]), $steps);
}

function adjustBrightness($hex, $steps) {
    // Steps should be between -255 and 255. Negative = darker, positive = lighter
    $steps = max(-255, min(255, $steps));

    // Normalize into a six character long hex string
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
    }

    // Split into three parts: R, G and B
    $color_parts = str_split($hex, 2);
    $return = '#';

    foreach ($color_parts as $color) {
        $color   = hexdec($color); // Convert to decimal
        $color   = max(0,min(255,$color + $steps)); // Adjust color
        $return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT); // Make two char hex code
    }

    return $return;
}

function textOnBgColor($color, $darkcolor = 'white', $lightcolor = 'black') {
    global $oset;
    if (isLightColor($color, true) > 160) {
        return $lightcolor;
    } else {
        return $darkcolor;
    }
}

function isLightColor($color, $returnBrightness = false) {
    if (preg_match("/^\#.*$/", $color)) {
        $color = hex2rgb($color);
    }
    if (!is_array($color)) {
        $color = explode(',', $color);
    }
    $r = $color[0];
    $g = $color[1];
    $b = $color[2];
    $rb = $r * 299;
    $gb = $g * 587;
    $bb = $b * 114;
    $bv = ($rb + $gb + $bb) / 1000;
    if ($returnBrightness === true) {
        return $bv;
    }
    if ($bv > 200) {
        return true;
    } else {
        return false;
    }
}

function makeHex($color) {
    if (preg_match("/^\#.*$/", $color)) {
        return $color;
    } elseif (preg_match("/^rgb(a)\(.*$/", $color)) {
        return rgb2hex(explode(',', explode('(', $color)[1]));
    } else {
        return rgb2hex($color);
    }
}

function hex2rgb($hex) {
   $hex = str_replace("#", "", $hex);

   if(strlen($hex) == 3) {
      $r = hexdec(substr($hex,0,1).substr($hex,0,1));
      $g = hexdec(substr($hex,1,1).substr($hex,1,1));
      $b = hexdec(substr($hex,2,1).substr($hex,2,1));
   } else {
      $r = hexdec(substr($hex,0,2));
      $g = hexdec(substr($hex,2,2));
      $b = hexdec(substr($hex,4,2));
   }
   $rgb = array($r, $g, $b);
   //return implode(",", $rgb); // returns the rgb values separated by commas
   return $rgb; // returns an array with the rgb values
}

function rgb2hex($rgb) {
   $hex = "#";
   $hex .= str_pad(dechex($rgb[0]), 2, "0", STR_PAD_LEFT);
   $hex .= str_pad(dechex($rgb[1]), 2, "0", STR_PAD_LEFT);
   $hex .= str_pad(dechex($rgb[2]), 2, "0", STR_PAD_LEFT);

   return $hex; // returns the hex value including the number sign (#)
}

function setColorAlpha($color, $alpha) {
    if (preg_match('/^\#.*$/', $color)) {
        $color = implode(',', hex2rgb($color));
    }
    $rgb = explode(',', $color);
    if (!isset($rgb[2])) { return '#333'; }
    $result = 'rgba(' . $color . ',' . $alpha . ')';
    return $result;
}

function validateCidr($cidr, $cidr_only = false) {
    $parts = explode("/", $cidr);
    $ip = $parts[0];
    $netmask = $parts[1];
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $return = true;
        $netmaskMax = 32;
    } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $return = true;
        $netmaskMax = 128;
    } else {
        $return = false;
    }
    if ($cidr_only === true) {
        if (empty($netmask) or $netmask == "") {
            $return = false;
        }
    }
    if (!empty($netmask)) {
        $netMaskVal = intval($netmask);
        if (!is_numeric($netMaskVal) or $netMaskVal > $netmaskMax or $netMaskVal < 0) {
            $return = false;
        }
    }
    return $return;
}

function validateField($test, $type) {
    global $oset;
    global $plugins;
    $return = false;
    switch ($type) {
        case "email":
            if (filter_var($test, FILTER_VALIDATE_EMAIL)) { return true; }
            break;
        case "ip":
            if (validateCidr($test)) { return true; }
            break;
        case "phone":
            $testclean = str_replace(['-', ' ', '(', ')', '+'], '', $test);
            if (!is_numeric($testclean)) { return false; }
            $num = filter_var($testclean, FILTER_SANITIZE_NUMBER_INT);
            if ($num != $testclean) { return false; }
            $num = str_replace("-", "", $num);
            if (strlen($num) > 9 and strlen($num) < 15) {
                return true;
            }
            break;
        case "url":
            if (filter_var($test, FILTER_VALIDATE_URL)) { return true; }
            break;
        case "multi":
            return true;
            break;
        default:
            foreach ($plugins as $pluginName => $pluginConf) {
                if (isset($pluginConf['field_validators'])) {
                    if (isset($pluginConf['field_validators'][$type])) {
                        $funcName = $pluginConf['field_validators'][$type]['function'];
                        if (function_exists($funcName)) {
                            //logError($pluginConf['field_validators'][$type]['function']);
                            $return = $funcName($test);
                        }
                    }
                }
            }
    }
    return $return;
}

function isValidType($test, $type) {
    switch ($type) {
        case "int":
            if (is_numeric($test) or is_float($test)) { return true; }
            break;
        case "str":
        case "option":
        case "password":
        case "select":
            if (is_string($test)) {
                return true;
            } else {
                //error_log($test . " is not " . $type);
            }
            break;
        case "json":
            if (is_array($test)) {
                return true;
            }
            break;
        case "color":
            // thanks to khalilgharbaoui https://gist.github.com/olmokramer/82ccce673f86db7cda5e#gistcomment-3170588
            if (preg_match('/(#(?:[0-9a-fA-F]{2}){2,4}$|(#[0-9a-fA-F]{3}$)|(rgb|hsl)a?\((-?\d+%?[,\s]+){2,3}\s*[\d\.]+%?\)$|black$|silver$|gray$|whitesmoke$|maroon$|red$|purple$|fuchsia$|green$|lime$|olivedrab$|yellow$|navy$|blue$|teal$|aquamarine$|orange$|aliceblue$|antiquewhite$|aqua$|azure$|beige$|bisque$|blanchedalmond$|blueviolet$|brown$|burlywood$|cadetblue$|chartreuse$|chocolate$|coral$|cornflowerblue$|cornsilk$|crimson$|darkblue$|darkcyan$|darkgoldenrod$|darkgray$|darkgreen$|darkgrey$|darkkhaki$|darkmagenta$|darkolivegreen$|darkorange$|darkorchid$|darkred$|darksalmon$|darkseagreen$|darkslateblue$|darkslategray$|darkslategrey$|darkturquoise$|darkviolet$|deeppink$|deepskyblue$|dimgray$|dimgrey$|dodgerblue$|firebrick$|floralwhite$|forestgreen$|gainsboro$|ghostwhite$|goldenrod$|gold$|greenyellow$|grey$|honeydew$|hotpink$|indianred$|indigo$|ivory$|khaki$|lavenderblush$|lavender$|lawngreen$|lemonchiffon$|lightblue$|lightcoral$|lightcyan$|lightgoldenrodyellow$|lightgray$|lightgreen$|lightgrey$|lightpink$|lightsalmon$|lightseagreen$|lightskyblue$|lightslategray$|lightslategrey$|lightsteelblue$|lightyellow$|limegreen$|linen$|mediumaquamarine$|mediumblue$|mediumorchid$|mediumpurple$|mediumseagreen$|mediumslateblue$|mediumspringgreen$|mediumturquoise$|mediumvioletred$|midnightblue$|mintcream$|mistyrose$|moccasin$|navajowhite$|oldlace$|olive$|orangered$|orchid$|palegoldenrod$|palegreen$|paleturquoise$|palevioletred$|papayawhip$|peachpuff$|peru$|pink$|plum$|powderblue$|rosybrown$|royalblue$|saddlebrown$|salmon$|sandybrown$|seagreen$|seashell$|sienna$|skyblue$|slateblue$|slategray$|slategrey$|snow$|springgreen$|steelblue$|tan$|thistle$|tomato$|transparent$|turquoise$|violet$|wheat$|white$|yellowgreen$|rebeccapurple$)/i', $test)) { return true; }
            break;
        case "bool":
            switch ($test) {
                case "true":
                case "false":
                case "True":
                case "False":
                case "":
                    return true;
                    break;
                default:
                    if (is_bool($test)) { return true; }
            }
            break;
        case "list":
        case "multi":
            if (is_array($test)) { return true; }
            break;
        case "file":
            return true;
            break;
        case "datetime-local":
            return true;
            break;
    }
    return false;
}

function buildUserGroupModel() {
    global $oset;
    global $plugins;
    $userModel = parse_file($oset['file_root'] . '/data/usermodel.json');
    foreach ($plugins as $pluginName => $pluginConf) {
        if (isset($pluginConf['user_model_extensions'])) {
            foreach ($pluginConf['user_model_extensions'] as $extName => $extConf) {
                if (isset($userModel['user'][$extName])) {
                    $baseConf = $userModel['user'][$extName];
                } else {
                    $baseConf = [];
                }
                switch ($extName) {
                    case "fields":
                    default:
                        foreach($extConf as $extCount => $extConfItem) {
                            array_push($baseConf, $extConfItem);
                        }
                }
                $userModel['user'][$extName] = $baseConf;
            }
        }
        if (isset($pluginConf['group_model_extensions'])) {
            foreach ($pluginConf['group_model_extensions'] as $extName => $extConf) {
                if (isset($userModel['group'][$extName])) {
                    $baseConf = $userModel['group'][$extName];
                } else {
                    $baseConf = [];
                }
                switch ($extName) {
                    case "fields":
                    default:
                        foreach($extConf as $extCount => $extConfItem) {
                            array_push($baseConf, $extConfItem);
                        }
                }
                $userModel['group'][$extName] = $baseConf;
            }
        }
    }
    foreach ($userModel as $context => $contextData) {
        $fieldData = [];
        foreach ($contextData['fields'] as $field) {
            switch ($field['type']) {
                case "option":
                case "multi":
                    $field['options'] = getApiValue($context, $field['source']);
                    break;
            }
            array_push($fieldData, $field);
        }
        $userModel[$context]['fields'] = $fieldData;
    }
    return $userModel;
}

function getApiValue($context, $target) {
    global $oset;
    global $coreUsers;
    $path = explode('.', $target);
    switch ($path[2]) {
        case "settings":
            return $oset[$path[4]];
        case "users":
        case "groups":
            return $coreUsers[$path[2]];
    }
}

function parse_file($filename, $createDataFile = true) {
    if (file_exists($filename)) {
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        switch ($filetype) {
            case "yaml":
            case "yml":
                $filedata = yaml_parse_file($filename);
                /*
                if (!is_array($filedata)) {
                    apiDie('error parsing ' . $filename, 500);
                }
                */
                break;
            case "json":
                $filecontent = file_get_contents($filename);
                $filedata = json_decode($filecontent, true);
                break;
            default:
                return false;
        }
        return $filedata;
    } elseif ($createDataFile === true) {
        $filedata = [];
        if (emit_file($filename, $filedata) === true) {
            return $filedata;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function emit_file($filename, $data) {
    if (!is_array($data)) { return false; }
    $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    switch ($filetype) {
        case "yaml":
        case "yml":
            if (yaml_emit_file($filename, $data)) {
                return true;
            } else {
                return false;
            }
            break;
        case "json":
            if (fopen($filename, 'w')) {
                $fp = fopen($filename, 'w');
                fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
                fclose($fp);
                return true;
            } else {
                return false;
            }
            break;
        default:
            return false;
    }
    return false;
}

function passwordHash($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function incrementBadLoginCount($username, $ipaddr) {
    global $oset;
    $currTime = time();
    if (isset($oset['login_lock_time'])) {
        $lockTime = $oset['login_lock_time'];
    } else {
        $lockTime = 600;
    }
    $dataFile = $oset['file_root'] . '/data/failed_logins.json';
    $failData = parse_file($dataFile);
    if (isset($failData[$username . '|' . $ipaddr])) {
        $userData = $failData[$username . '|' . $ipaddr];
    } else {
        $userData = [
            'count' => 0,
            'last_attempt' => $currTime,
            'status' => 'unlocked'
        ];
    }
    $failCount = $userData['count'];
    $lastAttempt = $userData['last_attempt'];
    $elapsed = $currTime - $lastAttempt;
    if ($elapsed <= $lockTime) {
        $userData['count'] = $userData['count'] + 1;
    } else {
        $userData['count'] = 1;
    }
    if ($userData['count'] == $oset['max_login_attempts']) {
        $userData['status'] = 'locked';
    } else {
        $userData['status'] = 'unlocked';
    }
    $userData['last_attempt'] = $currTime;
    $failData[$username . '|' . $ipaddr] = $userData;
    emit_file($dataFile, $failData);
}

function clearUserLoginLock($user, $ipaddr = null) {
    global $oset;
    // logError([$user, $ipaddr]);
    $failData = parse_file($oset['file_root'] . '/data/failed_logins.json');
    $saveData = [];
    foreach ($failData as $userhost => $userdata) {
        if ($ipaddr === null) {
            $preg_scan = "/^$user\|.*$/";
            if (preg_match($preg_scan, $userhost)) {
                continue;
            }
        } else {
            if ($userhost == $user . '|' . $ipaddr) {
                continue;
            }
        }
        $saveData[$userhost] = $userdata;
    }
    emit_file($oset['file_root'] . '/data/failed_logins.json', $saveData);
    return true;
}

function reCryptUserPassword($userId, $password) {
    return true;
}

function verifyLogin($user, $pass) {
    global $oset;
    global $plugins;
    if ($user == "") { return ["success"=>false,"error"=>"specify a username."]; }
    if ($pass == "") { return ["success"=>false,"error"=>"specify a password."]; }
    $checkFails = parse_file($oset['file_root'] . '/data/failed_logins.json');
    if (isset($checkFails[$user . '|' . $_SERVER['REMOTE_ADDR']])) {
        $userFailData = $checkFails[$user . '|' . $_SERVER['REMOTE_ADDR']];
        if ($userFailData['status'] == "locked") {
            $lockTime = $userFailData['last_attempt'];
            $currTime = time();
            $elapsed = $currTime - $lockTime;
            if ($elapsed < $oset['login_lock_time']) {
                $retryMins = $oset['login_lock_time'] / 60;
                return [
                    "success" => false,
                    "error" => "Account locked. You may retry in {$retryMins} minutes."
                ];
            } else {
                clearUserLoginLock($user);
            }
        }
    }
    $ipChkCount = 0;
    $ipChk = $_SERVER['REMOTE_ADDR'];
    $ipChkScan = "/^.*\|{$ipChk}$/";
    foreach ($checkFails as $userhost => $userdata) {
        if (preg_match($ipChkScan, $userhost)) {
            $chkTime = time() - $userdata['last_attempt'];
            if ($chkTime < $oset['login_lock_time']) {
                $ipChkCount++;
            } else {
                $u = explode('|', $userhost);
                clearUserLoginLock($u[0], $u[1]);
            }
        }
    }
    if ($ipChkCount >= $oset['max_login_attempts']) {
        return [
            "success" => false,
            "error" => "Too many failed logins from this IP."
        ];
    }
    $userData = getUsersAndGroups();
    $users = $userData['users'];
    $groups = $userData['groups'];
    $userGroups = [];
    $authenticated = false;
    if ($user == $oset['api_tmp']) {
        $authpass = false;
        foreach ($users as $uu => $ud) {
            if ($authenticated === true) { continue; }
            if (isset($ud['api_key'])) {
                if (is_array($ud['api_key'])) {
                    foreach ($ud['api_key'] as $kk)  {
                        $kh = $kk['hash'];
                        if (password_verify($pass, $kh)) {
                            $authpass = true;
                            $authenticated = true;
                            $userdata = $users[$uu];
                            $userdata['username'] = $uu;
                        }
                    }
                }
            }
        }
    } elseif (isset($users[$user])) {
        if (password_verify($pass, $users[$user]['password'])) {
            $authenticated = true;
            $userdata = $users[$user];
            $userdata['username'] = $user;
        } elseif (crypt($pass, $users[$user]['password']) == $users[$user]['password']) {
            $authenticated = true;
            $userdata = $users[$user];
            $userdata['username'] = $user;
            $user_legacy_password = true;
        }
    } elseif (isset($oset['emails_are_usernames']) and $oset['emails_are_usernames'] === true) {
        foreach ($users as $uname => $uconf) {
            if ($uname == $user or $uconf['email'] == $user) {
                if (password_verify($pass, $uconf['password'])) {
                    $authenticated = true;
                    $userdata = $uconf;
                    $userdata['username'] = $uname;
                } elseif (crypt($pass, $uconf['password']) == $uconf['password']) {
                    $authenticated = true;
                    $userdata = $uconf;
                    $userdata['username'] = $uname;
                    $user_legacy_password = true;
                }
            }
        }
    }
    if ($authenticated === false) {
        if (isset($oset['allow_plugin_auth']) and $oset['allow_plugin_auth'] === true) {
            foreach ($plugins as $pluginName => $pluginConf) {
                if (isset($pluginConf['auth_provider'])) {
                    $fileRoot = $pluginConf['file_root'];
                    if (file_exists($fileRoot . '/' . $pluginConf['auth_provider'])) {
                        $pluginResult = include($fileRoot . '/' . $pluginConf['auth_provider']);
                        logEvent(["attempting to authenticate ${user} with plugin ${pluginName}", $pluginResult]);
                        $authenticated = $pluginResult['success'];
                        if ($authenticated === true) {
                            $pluginSessionData = $pluginResult['session_data'];
                            $userdata = $pluginResult['user_data'];
                            if (isset($pluginResult['user_groups']) and count($pluginResult['user_groups']) > 0) {
                                $userGroups = $pluginResult['user_groups'];
                            }
                            $userdata["auth_source"] = $pluginName;
                            break;
                        }
                    }
                }
            }
        }
    }
    if ($authenticated === true) {
        logEvent(["login success with username {$user}", $userdata['username']], 2);
        $groupRankLevel = 0;
        $groupRankName = "public";
        clearUserLoginLock($user);
        if (isset($user_legacy_password) and $user_legacy_password === true) {
            /*
            this app was written from scratch to replace an even worse one,
            which used a different mechanism for encrypting user passwords.
            we needed a way to respect those existing passwords without
            resetting them, so this function detects such passwords, logs
            a warning about it, and then re-encrypts the password using
            the password_hash(). this check doesn't need to exist at all
            anymore, actually.
            */
            logEvent(["LEGACY PASSWORD LOGIN!", $userdata['username']], 3);
            reCryptUserPassword($userdata['id'], $pass);
        }
        foreach ($groups as $groupName => $groupConf) {
            if (isset($groupConf['users']) and (in_array($userdata['id'], $groupConf['users']) or (isset($userdata['auth_source']) and $userdata['group'] == $groupConf['id']))) {
                logError('user ' . $userdata['username'] . ' gets group rank ' . $groupConf['rank'], 'LOGIN STUFF');
                array_push($userGroups, $groupConf['id']);
                $groupRank = $groupConf['rank'];
                $groupRankNumber = getRankLevel($groupRank);
                if ($groupRankNumber > $groupRankLevel) {
                    $groupRankName = $groupRank;
                    $groupRankLevel = $groupRankNumber;
                }
            }
        }
        //logEvent('user ' . $userdata['username'] . ' gets user rank ' . $userdata['rank']);
        $userProfileData = [
            "groupRank" => $groupRankName,
            "groupRankLevel" => $groupRankLevel,
            "userRank" => $userdata['rank'],
            "access_level" => $groupRankName . ":" . $userdata['rank']
        ];

        if (isset($oset['user_themes']) and $oset['user_themes'] === true) {
            if (isset($userdata['theme']) and is_array($userdata['theme'])) {
                $userProfileData['theme'] = $userdata['theme'];
            }
        }

        if (isset($pluginSessionData) and is_array($pluginSessionData)) {
            foreach ($pluginSessionData as $pluginKey => $pluginVal) {
                $userProfileData[$pluginKey] = $pluginVal;
            }
        }
        if (empty($userProfileData['userRank'])) {
            $userProfileData['userRank'] = "user";
        }
        foreach ($userdata as $userKey => $userVal) {
            if (!in_array($userKey, ["password", "rank", "id"])) {
                $userProfileData[$userKey] = $userVal;
            }
        }
        $userReturn = [
            "success" => true,
            "id" => $userdata['id'],
            "userdata" => $userProfileData,
            "groups" => $userGroups
        ];
        if (isset($userProfileData['auth_source'])) {
            updatePluginUserRecord($userProfileData);
        }
        if (isset($userProfileData['require_mfa']) and $userProfileData['require_mfa'] === true) {
            $userReturn['validate_mfa'] = true;
        }
        return $userReturn;
    } else {
        logEvent(["login failure", $user, $pass], 2);
        return ["success" => false, "error" => "login failed"];
    }
}

function saveUserProfile($id, $data) {
    $fdata = getUsersAndGroups();
    $udata = $fdata['users'];
    $usave = [];
    foreach ($udata as $uu => $ux) {
        if ($ux['id'] == $id) {
            $usave[$uu] = $data;
        } else {
            $usave[$uu] = $ux;
        }
    }
    $fdata['users'] = $usave;
    $save = saveUsersAndGroups($fdata);
    return $save;
}

function updatePluginUserRecord($data) {
    global $oset;
    $users = parse_file($oset['file_root'] . '/data/plugin_users.json');
    if (empty($data['login'])) {
        $data['login'] = $data['id'];
    }
    $users[$data['login']] = $data;
    emit_file($oset['file_root'] . '/data/plugin_users.json', $users);
}

function startSession() {
    global $_SESSION;
    if (!isset($_SESSION)) {
        session_start();
    }
    return true;
}

function getRankLevel($rankName) {
    global $oset;
    $accessList = getAccessLevels();
    if (!isset($accessList[$rankName])) {
        logError(['no such rank: ' . $rankName, $accessList]);
        return 999;
    }
    return $accessList[$rankName];
}

function saveUsersAndGroups($data) {
    global $oset;
    if (empty($data['users']) or empty($data['groups'])) { return false; }
    if (!is_array($data['users']) or !is_array($data['groups'])) { return false; }
    if (isset($oset['sql_backend']) and $oset['sql_backend'] === true) {
        $createUserTable = getDb("CREATE TABLE IF NOT EXISTS `users` (`context` VARCHAR(32) UNIQUE, `data` LONGTEXT);");
        if ($createUserTable[0] === false) {
            return false;
        }
        $udata = base64_encode(json_encode($data['users']));
        $gdata = base64_encode(json_encode($data['groups']));
        $usql = "INSERT INTO `users` (`context`, `data`) VALUES ('users', '{$udata}') ON DUPLICATE KEY UPDATE `data` = '{$udata}';";
        $usqlExec = getDb($usql);
        if ($usqlExec[0] === false) {
            return false;
        }
        $gsql = "INSERT INTO `users` (`context`, `data`) VALUES ('groups', '{$gdata}') ON DUPLICATE KEY UPDATE `data` = '{$gdata}';";
        $gsqlExec = getDb($gsql);
        if ($gsqlExec[0] === false) {
            return false;
        }
        return true;
    } else {
        $emitFile = emit_file($oset['file_root'] . '/data/users.json', $data);
        if ($emitFile !== true) {
            return false;
        }
    }
    return true;
}

function getUsersAndGroups($includePluginUsers = false) {
    global $oset;
    if (isset($oset['sql_backend']) and $oset['sql_backend'] === true) {
        $usql = getDbArray("SELECT `data` FROM `users` WHERE `context` = 'users';");
        if ($usql[0] === false) { return false; }
        $gsql = getDbArray("SELECT `data` FROM `users` WHERE `context` = 'groups';");
        if ($gsql[0] === false) { return false; }
        $udata = json_decode(base64_decode($usql[2][0]['data']), true);
        $gdata = json_decode(base64_decode($gsql[2][0]['data']), true);
        $coreUsers = [
            'users' => $udata,
            'groups' => $gdata
        ];
    } else {
        $coreUsers = parse_file($oset['file_root'] . '/data/users.json');
    }
    if ($includePluginUsers === true and file_exists($oset['file_root'] . '/data/plugin_users.json')) {
        $pluginUsers = parse_file($oset['file_root'] . '/data/plugin_users.json');
        foreach ($pluginUsers as $uid => $uConf) {
            if (isset($coreUsers['users'][$uConf['login']])) { continue; }
            $coreUsers['users'][$uConf['login']] = $uConf;
        }
    }
    return $coreUsers;
}

// THIS IS NOT FINISHED, DO NOT USE IT ! //
function delUserSql($id, $context = 'user') {
    $x = getDb("DELETE FROM `core_{$context}s` WHERE `id` = '{$id}'");
    return $x[0];
}

function isUserValid($user, $context = 'user') {
    $x = getDbArray("SELECT `id` FROM `core_{$context}s` WHERE `id` = '{$user}';");
    if (count($x[2]) == 1) { return true; }
    return false;
}

function getUserSql($user, $context = 'user') {
    $x = getDbArray("SELECT * FROM `core_{$context}s` WHERE `id` = '{$user}';");
    if (count($x[2]) == 1) {
        $r = [];
        foreach ($x[2] as $k => $v) {
            $r[$k] = json_decode(base64_decode($v));
        }
        return $r;
    }
    return false;
}

function addUserSql($data, $context = 'user') {
    global $oset;
    $ucols = [];
    $uvals = [];
    $uschema = buildUserGroupModel()[$context]['fields'];
    foreach ($uschema as $i) {
        $n = $i['name'];
        array_push($ucols, $n);
        if (isset($data[$n])) {
            array_push($ucols, base64_encode(json_encode($data[$n])));
        } else {
            if (isset($i['required']) and $i['required'] === true) { return false; }
            array_push($ucols, null);
        }
    }
    $ucols = implode(',', $ucols);
    $uvals = implode(',', $uvals);
    $x = getDb("INSERT INTO `core_{$context}s` ({$ucols}) VALUES ({$uvals});");
    return $x[0];
}

function ugdata2sqlrows($data) {
    global $oset;
    $schemadata = parse_file($oset['file_root'] . '/data/usermodel.json');
    $uschema = $schemadata['user'];
    $gschema = $schemadata['group'];
    $urows = [];
    $grows = [];
    foreach ($data['users'] as $uname => $uconf) {
        $uconf['login_name'] = $uname;
        array_push($urows, $uconf);
    }
    foreach ($data['groups'] as $gname => $gconf) {
        $gconf['group_name'] = $gname;
        array_push($grows, $gconf);
    }
    $ucols = [
        '`id` VARCHAR(32) UNIQUE NOT NULL, `login_name` VARCHAR(64) UNIQUE NOT NULL'
    ];
    $gcols = [
        '`id` VARCHAR(32) UNIQUE NOT NULL, `group_name` VARCHAR(128) UNIQUE NOT NULL'
    ];
    $uxcols = [];
    $gxcols = [];
    foreach ($uschema['fields'] as $f) {
        if ($f['name'] == 'id') { continue; };
        $fcol = '`' . $f['name'] . '` LONGTEXT';
        if (isset($f['required']) and $f['required'] === true) {
            $fcol .= " NOT NULL";
        }
        if (isset($f['unique']) and $f['unique'] === true) {
            $fcol .= " UNIQUE";
        }
        array_push($uxcols, '`' . $f['name']) . '`';
        array_push($ucols, $fcol);
    }
    foreach ($gschema['fields'] as $f) {
        if ($f['name'] == 'id') { continue; }
        $fcol = '`' . $f['name'] . '` LONGTEXT';
        if (isset($f['required']) and $f['required'] === true) {
            $fcol .= " NOT NULL";
        }
        if (isset($f['unique']) and $f['unique'] === true) {
            $fcol .= " UNIQUE";
        }
        array_push($gxcols, '`' . $f['name']) . '`';
        array_push($ucols, $fcol);
    }
    $usql = "CREATE TABLE IF NOT EXISTS `core_users` (" . implode(',', $ucols) . ")";
    $usqlx = getDb($usql);
    if ($usqlx[0] === false) { return false; }
    $gsql = "CREATE TABLE IF NOT EXISTS `core_groups` (" . implode(',', $gcols) . ")";
    $gsqlx = getDb($gsql);
    if ($gsqlx[0] === false) { return false; }
    foreach ($urows as $i) {
        $ii = [];
        $iii = [];
        foreach ($i as $k => $v) {
            $v = base64_encode(json_encode($v));
            array_push($ii, "'{$v}'");
            array_push($iii, "`${k}` = '{$v}'");
        }
        $iix = implode(',', $ii);
        $iiix = implode(',', $iii);
        $ux = getDb("INSERT INTO `core_users` (".implode(', ', $uxcols).") VALUES ({$iix}) ON DUPLICATE KEY UPDATE {$iiix};");
        if ($ux[0] === false) { return false; }
    }
    foreach ($grows as $i) {
        $ii = [];
        $iii = [];
        foreach ($i as $k => $v) {
            $v = base64_encode(json_encode($v));
            array_push($ii, "'${v}'");
            array_push($iii, "`$k` = '{$v}'");
        }
        $iix = implode(',', $ii);
        $iiix = implode(',', $iii);
        $gx = getDb("INSERT INTO `core_groups` (".implode(', ', $gxcols).") VALUES ({$iix}) ON DUPLICATE KEY UPDATE {$iiix}");
        if ($gx[0] === false) { return false; }
    }
    return true;
}

function getUserProfile($id = null) {
    global $oset;
    $users = getUsersAndGroups()['users'];
    if (file_exists($oset['file_root'] . '/data/plugin_users.json')) {
        $pluginUsers = parse_file($oset['file_root'] . '/data/plugin_users.json');
        foreach ($pluginUsers as $puName => $puConf) {
            if (isset($users[$puConf['login']])) { continue; }
            $users[$puConf['login']] = $puConf;
        }
    }
    if ($id === null) {
        return $users;
    }
    foreach ($users as $userName => $userProf) {
        if ($userProf['id'] == $id) {
            $userProf['username'] = $userName;
            return $userProf;
        }
    }
    return false;
}

function getGroupProfile($id = null) {
    global $oset;
    $groupData = getUsersAndGroups()['groups'];
    if ($id === null) {
        return $groupData;
    }
    foreach ($groupData as $groupName => $groupConf) {
        if ($groupConf['id'] == $id) {
            return $groupConf;
        }
    }
    return false;
}

function getGroupAccessLevel($groupId) {
    global $coreUsers;
    $groups = $coreUsers['groups'];
    foreach ($groups as $groupName => $groupConf) {
        if ($groupConf['id'] == $groupId) {
            return $groupConf['rank'];
        }
    }
    return false;
}

function getUserAccessLevel($user) {
    $groups = getGroupProfile();
    $data = getUserProfile($user);
    $user_rank = $data['rank'];
    if (empty($user_rank)) { logError($data, 'NO RANK??'); }
    $user_rank_level = getRankLevel($user_rank);
    $group_rank = "public";
    $group_rank_level = 0;
    if (!isset($data['groups']) or count($data['groups']) == 0) {
        $data['groups'] = [ $data['group'] ];
    }
    if (!isset($data['groups'])) {
        $data['groups'] = [$data['group']];
    }
    foreach ($data['groups'] as $userGroup) {
        foreach ($groups as $groupName => $groupConf) {
            if ($groupConf['id'] == $userGroup) {
                $userGroupRank = $groupConf['rank'];
                if (empty($userGroupRank)) { logError($groupConf, 'no rank'); }
                $userGroupRankLevel = getRankLevel($userGroupRank);
                if ($userGroupRankLevel >= $group_rank_level) {
                    $group_rank = $userGroupRank;
                    $group_rank_level = $userGroupRankLevel;
                }
            }
        }
    }
    return ["user" => $user_rank, "group" => $group_rank];
}

function accessMax($user = null, $standard) {
    return true;
    global $oset;
    $standard = explode(":", $standard);
    $group_standard = $standard[0];
    $user_standard = $standard[1];
    if ($user === null) {
        $user_rank = "public";
        $group_rank = "public";
    } else {
        $user_level = getUserAccessLevel($user);
        $user_rank = $user_level['user'];
        $group_rank = $user_level['group'];
    }
    $user_level = getRankLevel($user_rank);
    $group_level = getRankLevel($group_rank);
    if ($user_level <= $user_standard and $group_level <= $group_standard) {
        return true;
    }
    return false;
}


/*
ACCESS CONTROL:

An item is assigned a particular access control string in the format
<group access level>:<user access level>. This means that in order
to access the item, a user must belong to a group with an access
level at least equal to <group access level> and the user must have
a personal access level at least equal to <user access level>.

For dynamic content (API and Model object commands), access levels
are assigned by HTTP method rather than by specific resource. So
you can have varying levels of access depending on what a user is
trying to do.

The lowest access level for a resource is "public:public", meaning
a user can access that resource without logging in, even if the
require_login parameter is enabled. The lowest access level that
requires a user to log in is "user:user". The highest possible
access level is "owner:owner".

Access is calculated by comparing the value of a user's associated
access levels to the value defined for the resource. If a user's
level is equal to or higher than that value, then access is
granted.

Valid access level names (for both groups and users):

    NAME                VALUE
    - public            0
    - user              10
    - moderator         20
    - admin             30
    - owner             99

Plugins can extend this list by specifying addition all access
levels with associated values in a range between 0 and 99. These
levels enable more granular control (for example a level of 25 would
grant access to anything "moderators" can do without granting access
to "admin" resources).

To determine whether a user can access a given resource, use the
accessMatch(<user id>, <resource access>) function where <user id>
is the user's account ID and <resource access> is the access string
associated with a resource.

*/
function accessMatch($user = null, $standard) {
    global $oset;
    if ($standard == "public:public" or $standard == "public") { return true; }
    $standard = explode(':', $standard);
    $user_standard = getRankLevel($standard[1]);
    if (isset($standard[1])) {
        $group_standard = getRankLevel($standard[0]);
        $user_standard = getRankLevel($standard[1]);
    } else {
        $group_standard = getRankLevel($standard);
        $user_standard = getRankLevel($standard);
    }
    if ($user === null) {
        if (isset($oset['require_login']) and $oset['require_login'] === true) {
            if ($group_standard == 0 and $user_standard == 0) {
                return true;
            } else {
                logError([$user, $user_standard, $group_standard], 'ACCESS DENIED');
                return false;
            }
        }
        $user_rank = "public";
        $group_rank = "public";
    } else {
        $userAccessLevel = getUserAccessLevel($user);
        $user_rank = $userAccessLevel['user'];
        $group_rank = $userAccessLevel['group'];
    }
    $user_level = getRankLevel($user_rank);
    $group_level = getRankLevel($group_rank);
    if ($group_level >= $group_standard) {
        if ($user_level >= $user_standard) {
            return true;
        }
    }
    logError([$user, $user_level, $group_level, $user_standard, $group_standard], 'ACCESS DENIED');
    return false;
}
function uac($standard) {
    global $_SESSION;
    return accessMatch($_SESSION['id'], $standard);
}

function getAccessLevels() {
    global $oset;
    global $plugins;
    $accessList = $oset['access_levels'];
    foreach ($plugins as $pluginName => $pluginConf) {
        if (isset($pluginConf['access_levels'])) {
            foreach ($pluginConf['access_levels'] as $levelName => $levelRank) {
                $accessList[$levelName] = $levelRank;
            }
        }
    }
    return $accessList;
}

function addUserNotification($id, $notify) {
    $actions = loadUserActions();
    if (isset($actions[$id])) {
        $userActions = $actions[$id];
    } else {
        $userActions = [];
    }
    $notifyList = [];
    if (isset($userActions['notifications'])) {
        $notifyList = $userActions['notifications'];
    }
    array_push($notifyList, $notify);
    $userActions['notifications'] = $notifyList;
    $actions[$id] = $userActions;
    saveUserActions($actions);
}

function loadUserActions($id = null) {
    if (!file_exists(__DIR__ . '/user_actions.json')) {
        emit_file(__DIR__ . '/user_actions.json', ["users"=>[]]);
    }
    $actions = parse_file(__DIR__ . '/user_actions.json')['users'];
    if ($id == null) {
        return $actions;
    } else {
        foreach ($actions as $actionId => $actionConf) {
            if ($actionId == $id) { return $actionConf; }
        }
    }
    return [];
}

function addUserAction($id, $action) {
    $actions = loadUserActions();
    $userActions = [];
    if (isset($actions[$id])) {
        $userActions = $actions[$id];
    }
    array_push($userActions, $action);
    $actions[$id] = $userActions;
    saveUserActions($actions);
}

function saveUserActions($actions) {
    emit_file(__DIR__ . '/user_actions.json', ["users"=>$actions]);
}

function logoutUser($id) {
    $actions = loadUserActions();
    if (!isset($actions[$id])) {
        $actions[$id] = [];
    }
    $actions[$id]['logout'] = true;
    saveUserActions($actions);
}

function clearUserActions($id) {
    $actions = loadUserActions();
    $actions_save = [];
    foreach ($actions as $actionId => $actionConf) {
        if ($actionId != $id) {
            $actions_save[$actionId] = $actionConf;
        }
    }
    saveUserActions($actions_save);
}

function overlayUserSettings($context = null) {
    global $oset;
    global $oset_schema;
    $schema = $oset_schema;
    if (isset($oset['user_themes']) and $oset['user_themes'] === true) {
        if (isset($_SESSION['userdata']['theme']) and is_array($_SESSION['userdata']['theme'])) {
            foreach ($_SESSION['userdata']['theme'] as $rule => $val) {
                if (isset($schema[$rule]) and $schema[$rule]['group'] == "presentation") {
                    $oset[$rule] = $val;
                }
            }
        }
    }
    return $oset;
}

function convertSettingsBackend($target) {
    if (file_exists(__DIR__ . '/settings.json')) {
        $set = parse_file(__DIR__ . '/settings.json');
        $scm = parse_file(__DIR__ . '/settings_schema.json');
    } else {
        return false;
    }
    if (isset($set['sql_backend'])) {
        if (is_bool($set['sql_backend']) and $set['sql_backend'] === true) {
            if (!empty($set['sql_host']) and !empty($set['sql_name']) and !empty($set['sql_user']) and !empty($set['sql_pass'])) {
                if (empty($set['sql_port']) or !is_numeric($set['sql_port'])) {
                    $set['sql_port'] = 3306;
                }
                $sql_chk = getDb("CREATE TABLE IF NOT EXISTS `{$set['sql_name']}` (`settings` LONGTEXT, `schema` LONGTEXT)");
                if ($sql_chk[0] === false) {
                    logError($sql_chk[2]);
                    return false;
                }
            } else {
                logError('cannot init sql settings backend, no db config found.');
                return false;
            }
        }
    }
}

function buildSystemSettings($includeSchema = false) {
    global $version_data;
    global $plugins;
    $set = parse_file(__DIR__ . '/settings.json');
    $set['file_root'] = dirname(dirname(__FILE__));
    $schema = parse_file(__DIR__ . '/settings_schema.json')['core'];
    foreach ($schema as $ruleName => $ruleConf) {
        $schema[$ruleName]['source'] = "core";
        if (isset($ruleConf['default']) and !empty($ruleConf['default']) and (!isset($set[$ruleName]) or (empty($set[$ruleName]) and !is_bool($set[$ruleName])))) {
            $set[$ruleName] = $ruleConf['default'];
        }
    }
    if (is_array($plugins)) {
        foreach ($plugins as $pluginName => $pluginConf) {
            if (file_exists($pluginConf['file_root'] . '/settings_schema.json')) {
                $pSchemaData = parse_file($pluginConf['file_root'] . '/settings_schema.json');
                if (isset($pSchemaData['core'])) {
                    $pSchema = $pSchemaData['core'];
                    foreach ($pSchema as $pKey => $pConf) {
                        if (!isset($schema[$pKey])) {
                            $pConf['source'] = $pluginName;
                            $schema[$pKey] = $pConf;
                        }
                    }
                }
            }
            if (file_exists($pluginConf['file_root'] . '/settings_override.json')) {
                $settingsOverride = parse_file($pluginConf['file_root'] . '/settings_override.json');
                foreach ($settingsOverride as $pKey => $pVal) {
                    $set[$pKey] = $pVal;
                    $schema[$pKey]['source'] = $pluginName;
                }
            }
        }
    }
    $set['tox_version'] = $version_data['version'];
    if ($includeSchema === true) {
        return [$schema, $set];
    }
    if (isset($_SESSION['userdata']) and isset($_SESSION['userdata']['theme'])) {
        if (!isset($set['user_themes']) or $set['user_themes'] === true) {
            foreach ($_SESSION['userdata']['theme'] as $theme_opt => $theme_val) {
                if (isset($schema[$theme_opt]) and $schema[$theme_opt]['group'] == "presentation") {
                    $set[$theme_opt] = $theme_val;
                }
            }
        }
    }
    /* this probably doesn't really need to be done
    if (isset($set['sql_backend']) and $set['sql_backend'] === true) {
        $dba = [
            $set['sql_host'],
            $set['sql_name'],
            $set['sql_user'],
            $set['sql_pass']
        ];
        $dbset = getDbArray("SELECT * FROM `oset` WHERE `index` = 1;", $dba);
        if ($dbset[0] === false) { apiDie('database error!', 500); }
        $db_oset = json_decode(base64_decode($dbset[2][0]['oset']), true);
        $db_schema = json_decode(base64_decode($dbset[2][0]['oreg']), true);
        $set = [$db_schema, $db_oset];
    }
    */
    return $set;
}

function buildPluginRegistry($includeDisabled = false) {
    global $oset;
    $pluginDir = scandir(__DIR__ . '/../plugins');
    $plugins = [];
    foreach ($pluginDir as $dir) {
        $core_version_check = $oset['tox_version'];
        if ($dir == "." or $dir == "..") { continue; }
        if (is_dir(__DIR__ . '/../plugins/' . $dir)) {
            $pluginRoot = $oset['file_root'] . '/plugins/' . $dir . '/';
            // $pluginRoot = __DIR__ . '/../plugins/' . $dir . '/';
            if (file_exists($pluginRoot . '/plugin.yaml') or file_exists($pluginRoot . '/plugin.json')) {
                if (file_exists($pluginRoot . '/plugin.yaml')) {
                    $pconf = parse_file($pluginRoot . '/plugin.yaml');
                } else {
                    $pconf = parse_file($pluginRoot . '/plugin.json');
                }
                if (is_array($pconf)) {
                    if ((isset($pconf['enabled']) and $pconf['enabled'] === true) or $includeDisabled === true) {
                        if (version_compare($oset['tox_version'], $core_version_check, '>=')) {
                            $plugins[$dir] = $pconf;
                            $plugins[$dir]['file_root'] = $pluginRoot;
                        }
                    }
                }
            }
        }
    }
    $plugins_out = [];
    foreach ($plugins as $pluginName => $pluginConf) {
        $include = true;
        if (is_array($pluginConf['depends'])) {
            foreach ($pluginConf['depends'] as $depName => $depVer) {
                if ($depName == "core") {
                    if (!version_compare($oset['tox_version'], $depVer, ">=")) {
                        //error_log("\nplugin dependency not met: " . $pluginName . " requires " . $depName . " v. " . $depVer . " but we have " . $oset['tox_version'] . "\n");
                        $include = false;
                    }
                } else {
                    if (!isset($plugins[$depName]) or !version_compare($plugins[$depName]['version'], $depVer, ">=")) {
                        //error_log("\nplugin dependency not met: " . $pluginName . " requires " . $depName . " v. " . $depVer . ".\n");
                        $include = false;
                    }
                }
            }
        }
        if ($include === true) {
            $plugins_out[$pluginName] = $pluginConf;
        } elseif ($includeDisabled === true) {
            $pluginConf['enabled'] = false;
            $pluginConf['LOAD_ERR'] = "dependencies unmet";
            $plugins_out[$pluginName] = $pluginConf;
        }
    }
    return $plugins_out;
}

function getPluginOpts($plugin, $value = NULL) {
    if ($value == NULL) {
        return buildPluginRegistry()[$plugin];
    } else {
        return buildPluginRegistry()[$plugin][$value];
    }
}

function getModels() {
    global $oset;
    $models = [];
    $extends = [];
    $appends = [];
    $coreModels = parse_file(__DIR__ . '/models.json')['models'];
    foreach ($coreModels as $model) {
        $model['source'] = 'core';
        $model_name = $model['name'];
        $models[$model_name] = $model;
    }
    $defModels = parse_file(__DIR__ . '/model_defs.json')['models'];
    foreach ($defModels as $model) {
        $model['source'] = 'core';
        $model_name = $model['name'];
        $models[$model_name] = $model;
    }
    $plugins = buildPluginRegistry();
    foreach ($plugins as $pluginName => $pluginConf) {
        if (!isset($pluginConf['enabled']) or $pluginConf['enabled'] !== true) { continue; }
        if (file_exists($pluginConf['file_root'] . '/models.json') or file_exists($pluginConf['file_root'] . '/models.yaml')) {
            if (file_exists($pluginConf['file_root'] . '/models.json')) {
                $readModelFile = parse_file($pluginConf['file_root'] . '/models.json');
            } else {
                $readModelFile = parse_file($pluginConf['file_root'] . '/models.yaml');
                //logError($readModelFile);
            }
            if (is_array($readModelFile)) {
                if (isset($readModelFile['append'])) {
                    foreach ($readModelFile['append'] as $app) {
                        $modName = $app['model'];
                        $modFile = $app['store'];
                        $appItem = [
                            "plugin" => $pluginName,
                            "file" => $oset['file_root'] . '/plugins/' . $pluginName . '/' . $modFile
                        ];
                        if (!isset($models[$modName]['append'])) {
                            $models[$modName]['append'] = [$appItem];
                        } else {
                            array_push($models[$modName]['append'], $appItem);
                        }
                    }
                }
                if (isset($readModelFile['models']) and is_array($readModelFile['models'])) {
                    $pluginModels = $readModelFile['models'];
                    foreach ($pluginModels as $model) {
                        $model_name = $model['name'];
                        $model['storeType'] = "database";
                        if (!preg_match('/^__.*db__:\/\/.*$/', $model['store'])) {
                            if ($model['store'] == "__controller__") {
                                $model['storeType'] = "controller";
                                $model['store'] = $oset['file_root'] . '/plugins/' . $pluginName . '/utils/model_controllers/' . $model_name . '/';
                            } else {
                                $model['storeType'] = "file";
                                $model['store'] = $oset['file_root'] . '/plugins/' . $pluginName . '/' . $model['store'];
                            }
                        }
                        if (isset($models[$model_name]) and !isset($model['extends'])) {
                            continue;
                        } else {
                            if (isset($model['extends'])) {
                                $extends[$model_name] = $model;
                            } else {
                                $model['source'] = $pluginName;
                                $models[$model_name] = $model;
                            }
                        }
                    }
                }
            }
        }
    }
    foreach ($extends as $modelExtName => $modelConf) {
        $modelName = $modelConf['extends'];
        if (isset($models[$modelName])) {
            $modelFields = $models[$modelName]['fields'];
            $modelFieldsExtended = [];
            $processedFields = [];
            foreach ($modelFields as $exField) {
                $updated = false;
                foreach ($modelConf['fields'] as $newField) {
                    if ($newField['name'] == $exField['name']) {
                        array_push($modelFieldsExtended, $newField);
                        $updated = true;
                        array_push($processedFields, $newField['name']);
                    }
                }
                if ($updated === false) {
                    array_push($modelFieldsExtended, $exField);
                }
            }
            foreach ($modelConf['fields'] as $newField) {
                if (!in_array($newField['name'], $processedFields)) {
                    array_push($modelFieldsExtended, $newField);
                }
            }
            $models[$modelName]['fields'] = $modelFieldsExtended;
        }
    }
    return $models;
}

function getModelStore($model_name) {
    global $oset;
    global $api_models;
    global $plugins;
    if (empty($api_models[$model_name])) { return false; }
    //$obj = new ObjectModel($model_name);
    //$schema = $obj->schema;
    $schema = $api_models[$model_name];
    $store = $schema['store'];
    $returnStore = [
        'schema' => $schema
    ];
    if (preg_match('/^__.*db__:\/\/.*$/', $store)) {
        $conStr = explode(':', $store)[0];
        $authVar = [];
        $returnStore['type'] = 'db';
        $returnStore['table'] = explode('/', $store)[2];
        $returnStore['auth'] = [];
        foreach ($plugins as $pluginName => $pluginConf) {
            if (isset($pluginConf['db_sources'])) {
                foreach ($pluginConf['db_sources'] as $dbs => $dbc) {
                    if ($dbs == $conStr) {
                        foreach (['host', 'name', 'user', 'pass'] as $x) {
                            if (isset($dbc[$x])) {
                                $y = $dbc[$x];
                                $z = explode(':', $y);
                                if (count($z) == 3) {
                                    $authStrSrc = $z[0];
                                    $authStrVal = $z[2];
                                    switch ($authStrSrc) {
                                        case "oset":
                                            $authStrVal = $oset[$authStrVal];
                                            break;
                                    }
                                } else {
                                    $authStrVal = $y;
                                }
                            } else {
                                $authStrVal = $oset['db_' . $x];
                            }
                            array_push($returnStore['auth'], $authStrVal);
                        }
                    }
                }
            }
        }
    } elseif ($store == "__controller__") {
        if ($schema['source'] == "core") {
            // not yet lol
            return false;
        } else {
            $controllerDir = $oset['file_root'] . "/plugins/" . $schema['source'] . "/utils/model_controllers/" . $model_name . "/";
            if (!file_exists($controllerDir . "index.php")) {
                return false;
            }
        }
        $returnStore['type'] = "controller";
        $returnStore['path'] = $controllerDir;
    } else {
        $returnStore['type'] = 'file';
        $returnStore['path'] = $store;
    }
    return $returnStore;
}

function getModelItem($model_name, $item_id, $override_assignment = false) {
    global $oset;
    global $api_models;
    $item_list = getModelItems($model_name, $override_assignment);
    foreach ($item_list as $item) {
        if ($item['id'] == $item_id) { return $item; }
    }
    return false;
}

function getModelItemSql($method, $model, $item) {
    $obj = new ObjectModel($model);
    $store = $obj->store;
    $schema = $obj->schema;
    $fields = $schema['fields'];
    switch ($method) {
        case "POST":
            $cols = [];
            $vals = [];
            foreach ($item as $k => $v) {
                array_push($cols, '`' . $k . '`');
                array_push($vals, "'" . $v . "'");
            }
            $sqlStr = "INSERT INTO `{$store['table']}` (".implode(', ', $cols).") VALUES (".implode(', ', $vals).");";
            break;
        case "PATCH":
            $upd = [];
            foreach ($item as $k => $v) {
                if ($k == "id") { continue; }
                array_push($upd, "`{$k}` = '{$v}'");
            }
            $sqlStr = "UPDATE `{$store['table']}` SET ".implode(', ', $upd)." WHERE `id` = '".$item['id']."';";
            break;
        case "DELETE":
            $sqlStr = "DELETE FROM `{$store['table']}` WHERE `id` = '".$item['id']."';";
            break;
    }
    return $sqlStr;
}

function getModelItems($model_name, $override_assignment = false) {
    global $oset;
    global $api_models;
    global $plugins;
    $modStore = getModelStore($model_name);
    $models = $api_models;
    if (isset($models[$model_name])) {
        $model = $models[$model_name];
        $schema = $modStore['schema'];
        $fields = $schema['fields'];
        if (isset($schema['assign_by_group'])) {
            array_push($fields, [
                'name' => 'owner_group',
                'type' => 'str',
                'required' => true,
                'do_not_hash' => true
            ]);
        }
        switch($modStore['type']) {
            case 'db':
                createModelDbTable($modStore);
                $dbData = getDbArray("SELECT * FROM {$modStore['table']} WHERE 1;", $modStore['auth']);
                // logEvent(['debug', $dbData]);
                if ($dbData[0] === false) {
                    return [];
                } else {
                    $modelData = [];
                    if (count($dbData[2]) > 0) {
                        foreach ($dbData[2] as $row) {
                            $rowData = ['id'=>$row['id']];
                            foreach ($fields as $field) {
                                if ($field['type'] == "password") {
                                    $rowData[$field['name']] = $row[$field['name']];
                                } elseif (empty($field['do_not_hash']) or $field['do_not_hash'] !== true) {
                                    $rowData[$field['name']] = json_decode(base64_decode($row[$field['name']]), true);
                                } else {
                                    $rowData[$field['name']] = $row[$field['name']];
                                }
                            }
                            array_push($modelData, $rowData);
                        }
                    }
                }
                break;
            case 'controller':
                if (file_exists($modStore['path'] . '/GET.php')) {
                    $modelData = include($modStore['path'] . '/GET.php');
                } else {
                    $modelData = include($modStore['path'] . '/index.php');
                }
                break;
            case 'file':
            default:
                if ($schema['source'] == "core") {
                    $dataFilePath = $oset['file_root'] . '/' . $modStore['path'];
                } else {
                    $dataFilePath = $modStore['path'];
                }
                $modelData = parse_file($dataFilePath);
        }
        if (isset($model['append'])) {
            foreach ($model['append'] as $app) {
                $file = $app['file'];
                if (file_exists($file)) {
                    $fileRead = parse_file($file);
                    foreach ($fileRead as $modelItem) {
                        array_push($modelData, $modelItem);
                    }
                }
            }
        }
        if (is_array($modelData)) {
            $returnData = [];
            $fieldSchema = [];
            foreach ($models[$model_name]['fields'] as $f) {
                $fieldSchema[$f['name']] = $f;
            }
            foreach ($modelData as $item) {
                if (isset($models[$model_name]['assign_by_group']) and $models[$model_name]['assign_by_group'] === true) {
                    if ($item['owner_group'] != $_SESSION['userdata']['group'] and $override_assignment !== true) {
                        continue;
                    }
                }
                foreach ($item as $k => $v) {
                    if (isset($fieldSchema[$k]['access']) and !accessMatch($_SESSION['id'], $fieldSchema[$k]['access'])) {
                        $item[$k] = null;
                        continue;
                    }
                    if (isset($fieldSchema[$k]['transform'])) {
                        $tfunc = $fieldSchema[$k]['transform']['on_read'];
                        $func_name = $tfunc['function'];
                        if (!function_exists($func_name)) {
                            logError('NO SUCH FUNCTION ' . $func_name . '()', 'MODEL TRANSFORM ERROR');
                            continue;
                        }
                        if (!empty($tfunc['args'])) {
                            $func_args = [];
                            foreach ($tfunc['args'] as $arg_label => $arg_pointer) {
                                $arg_split = explode(':', $arg_pointer);
                                switch ($arg_split[0]) {
                                    case 'oset':
                                        $arg_value = $oset[explode('|', $arg_split[2])];
                                        break;
                                    case 'item':
                                        $arg_value = $item[$arg_split[2]];
                                        break;
                                }
                                $func_args[$arg_label] = $arg_value;
                            }
                        } else {
                            $func_args = $item;
                        }
                        $tfunc_output = $func_name($func_args);
                        $item[$k] = $tfunc_output;
                    }
                }
                array_push($returnData, $item);
            }
            // what the actual fuck?
            $returnDataIds = [];
            $returnDataDedup = [];
            foreach ($returnData as $rr) {
                if (!in_array($rr['id'], $returnDataIds)) {
                    array_push($returnDataDedup, $rr);
                    array_push($returnDataIds, $rr['id']);
                }
            }
            return $returnDataDedup;
//            return $returnData;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function deleteModelItem($model, $id) {
    global $oset;
    global $api_models;
    if (empty($api_models[$model])) {
        return false;
    }
    $model_def = $api_models[$model];
    $store = getModelStore($model);
    if ($store['type'] == 'db') {
        $exec = getDb("DELETE FROM `{$store['table']}` WHERE `id` = '{$id}';", $store['auth']);
        return $exec;
    }
    $items = getModelItems($model, true);
    $save = [];
    $found = false;
    foreach ($items as $item) {
        if ($item['id'] == $id) {
            if (!accessMatch($_SESSION['id'], 'admin:user')) {
                if (isset($model_def['assign_by_group']) and $model_def['assign_by_group'] === true) {
                    if ($item['owner_group'] != $_SESSION['userdata']['group']) {
                        return [false, 404, 'this item does not exist as far as you are concerned.'];
                    }
                }
            }
            $found = true;
        } else {
            array_push($save, $item);
        }
    }
    if ($found === true) {
        if (storeModelItems($model, $save)) {
            return [true, 200, 'ok'];
        } else {
            return [false, 500, 'error storing data.'];
        }
    }
    return [false, 404, 'this item does not exist.'];
}

function addModelItem($model, $item, $owner = null) {
    global $api_models;
    global $oset;
    if (empty($api_models[$model])) {
        return [false, 404, 'no such model ' . $model];
    }
    $schema = $api_models[$model];
    $store = getModelStore($model);
    $fields = $schema['fields'];
    $data = getModelItems($model, true);
    if ($data === false) { $data = []; }
    $save = [];
    if (isset($schema['assign_by_group']) and $schema['assign_by_group'] === true) {
        array_push($fields, [
            'name' => 'owner_group',
            'type' => 'str',
            'do_not_hash' => true
        ]);
        if (!empty($owner) and accessMatch($_SESSION['id'], 'admin:user')) {
            $item['owner_group'] = $owner;
        } else {
            $item['owner_group'] = $_SESSION['userdata']['group'];
        }
    }
    foreach ($fields as $field) {
        $fname = $field['name'];
        if (isset($field['default'])) {
            if (explode(":", $field['default'])[0] == "session") {
                $defaultValue = $_SESSION['userdata'][explode(":", $field['default'][2])];
            } else {
                $defaultValue = $field['default'];
            }
            if (isset($item[$fname]) and $item[$fname] != $defaultValue) {
                if (isset($field['default_override_access'])) {
                    if (accessMatch($_SESSION['id'], $field['default_override_access'])) {
                        $defaultValue = $item[$fname];
                    }
                }
            }
            $item[$fname] = $defaultValue;
        }
        if (isset($field['required']) and $field['required'] === true) {
            if (!isset($item[$fname])) {
                return [false, 421, 'missing required field: ' . $fname];
            }
        }
        if (isset($field['unique']) and $field['unique'] === true) {
            $uchk = getModelItems($model);
            foreach ($uchk as $uu) {
                if ($uu[$fname] == $item[$fname]) {
                    return [false, 433, $fname . ' value must be unique.'];
                }
            }
            /*
            foreach ($data as $dataItem) {
                if ($dataItem[$fname] == $item[$fname] and (empty($api_models[$model]['assign_by_group']) or (!empty($dataItem['owner_group']) and $dataItem['owner_group'] == $owner))) {
                    return [false, 409, 'There is already an item with that ' . $fname];
                }
            }
            */
        }
        if (isset($field['unique_to_models'])) {
            foreach ($field['unique_to_models'] as $uCheckMod) {
                $uCheckModItems = getModelItems($uCheckMod);
                foreach ($uCheckModItems as $uCheckModItem) {
                    if ($uCheckModItem[$fname] == $item[$fname]) {
                        if (isset($field['unique_to_models_error_text'])) {
                            $etxt = $field['unique_to_models_error_text'];
                        } else {
                            $etxt = $fname . ' value cannot match ' . $uCheckMod . ' items.';
                        }
                        return [false, 434, $etxt];
                    }
                }
            }
        }
        if (!isValidType($item[$fname], $field['type'])) {
            if ($field['type'] == "bool") {
                switch ($item[$fname]) {
                    case "true":
                    case "True":
                        $item[$fname] = true;
                        break;
                    case "false":
                    case "False":
                        $item[$fname] = false;
                        break;
                }
            }
            return [false, 425, $fname . ' value "' . $item[$fname] . '" is not valid type ' . $field['type']];
        }
        if (isset($field['validate']) and !validateField($item[$fname], $field['validate'])) {
            return [false, 430, $field['name'] . ' value is not valid ' . $field['validate'] . ' data.'];
        }
        if (isset($field['transform']) and !empty($field['transform']['on_write'])) {
            $tfunc = $field['transform']['on_write'];
            $func_name = $tfunc['function'];
            if (!function_exists($func_name)) {
                logError('NO SUCH FUNCTION ' . $func_name . '()', 'MODEL TRANSFORM ERROR');
                continue;
            }
            if (!empty($tfunc['args'])) {
                $func_args = [];
                foreach ($tfunc['args'] as $arg_label => $arg_pointer) {
                    $arg_split = explode(':', $arg_pointer);
                    switch ($arg_split[0]) {
                        case 'oset':
                            $arg_value = $oset[explode('|', $arg_split[2])];
                            break;
                        case 'item':
                            $arg_value = $item[$arg_split[2]];
                            break;
                    }
                    $func_args[$arg_label] = $arg_value;
                }
            } else {
                $func_args = $item;
            }
            $tfunc_output = $func_name($func_args);
            $item[$fname] = $tfunc_output;
        }
        if (isset($field['auto']) and $field['auto'] === true) {
            if (isset($field['auto_type'])) {
                $auto_type = $field['auto_type'];
            } else {
                $auto_type = "random_string";
            }
            $type_arr = explode(':', $auto_type);
            if (!empty($type_arr[2])) {
                switch ($type_arr[0]) {
                    case 'function':
                        $auto_func = $type_arr[2];
                        $auto_arg = $type_arr[4];
                        if (function_exists($auto_func)) {
                            $auto_val = $auto_func($item[$auto_arg]);
                        } else {
                            logError('NO SUCH FUNCTION ' . $auto_func, 'AUTO_TYPE ERROR');
                            $auto_val = false;
                        }
                        break;
                    case 'session':
                        $auto_val = $_SESSION[$type_arr[2]];
                        break;
                    case 'datetime':
                        $nowDate = new DateTime($type_arr[2]);
                        $auto_val = $nowDate->format('Ymd\TH:i:s');
                        break;
                    default:
                        $auto_val = generateRandomString(16);
                }
            } else {
                $auto_val = generateRandomString(16);
            }
            $item[$fname] = $auto_val;
        }
        if ($field['type'] == "password") {
            $item[$fname] = password_hash($item[$fname], PASSWORD_DEFAULT);
        } else {
            if ($store['type'] == 'db') {
                if (!isset($field['do_not_hash']) or $field['do_not_hash'] !== true) {
                    $item[$fname] = base64_encode(json_encode($item[$fname]));
                }
            }
        }
        $save[$fname] = $item[$fname];
    }
    $save['id'] = generateRandomString(10);
    if ($store['type'] == 'db') {
        $makeTable = createModelDbTable($store);
        $qq = getModelItemSql('POST', $model, $save);
        if ($qq !== false) {
            $dbExec = getDb($qq, $store['auth']);
            logError([$qq, $store['auth'], $dbExec], 'wtf huh');
            return $dbExec;
        } else {
            return false;
        }
    }
    array_push($data, $save);
    $exec = storeModelItems($model, $data);
    if ($exec[0] === false) {
        return $exec;
    } else {
        return [true, 200, $save['id']];
    }
}

function createModelDbTable($store) {
    $table = $store['table'];
    $auth = $store['auth'];
    $fields = $store['schema']['fields'];
    logError($store['schema']);
    $fieldList = [
        '`id` VARCHAR(100) UNIQUE'
    ];
    if (isset($store['schema']['assign_by_group']) and $store['schema']['assign_by_group'] === true) {
        array_push($fieldList, '`owner_group` VARCHAR(100)');
    }
    foreach ($fields as $f) {
        $fcolsql = "`{$f['name']}` LONGTEXT";
        if (isset($f['unique']) and $f['unique'] === true) {
            $fcolsql .= " UNIQUE";
        }
        if (isset($f['required']) and $f['required'] === true) {
            $fcolsql .= " NOT NULL";
        }
        array_push($fieldList, $fcolsql);
    }
    $fieldList = implode(', ', $fieldList);
    $qq = "CREATE TABLE IF NOT EXISTS `{$table}` ({$fieldList});";
    getDb($qq, $auth);
}

function patchModelItem($model, $id, $item) {
    global $api_models;
    if (empty($api_models[$model])) {
        return [false, 404, 'no such model ' . $model];
    }
    $store = getModelStore($model);
    $fields = $api_models[$model]['fields'];
    //$data = getModelItems($model, true);
    //$savedata = [];
    $updateFields = [];
    foreach ($fields as $field) {
        $fname = $field['name'];
        if (isset($item[$fname])) {
            $fvalue = $item[$fname];
            if (isset($field['unique_to_models'])) {
                foreach ($field['unique_to_models'] as $ucheckmod) {
                    $uchk = getModelItems($ucheckmod);
                    foreach ($uchk as $uu) {
                        if ($uu[$fname] == $item[$fname]) {
                            if (isset($field['unique_to_models_error_text'])) {
                                $etxt = $field['unique_to_models_error_text'];
                            } else {
                                $etxt = $fname . ' value cannot match ' . $ucheckmod . ' items.';
                            }
                            return [false, 434, $etxt];
                        }
                    }
                }
            }
            if (isset($field['unique']) and $field['unique'] === true) {
                $uchk = getModelItems($model);
                foreach ($uchk as $uu) {
                    if ($uu['id'] != $id) {
                        if ($uu[$fname] == $item[$fname]) {
                            return [false, 433, $fname . ' value (' . $item[$fname] . ') must be unique.'];
                        }
                    }
                }
            }
            if (!isValidType($fvalue, $field['type'])) {
                return [
                    false,
                    420,
                    $fname . ' value is not valid type ' . $field['type']
                ];
            }
            if ($field['type'] == 'password') {
                if (!empty($item['SKIP_PASSWORD_HASH']) and $item['SKIP_PASSWORD_HASH'] === true) {
                    $donothing = 'kk';
                } else {
                    $item[$fname] = password_hash($item[$fname], PASSWORD_DEFAULT);
                }
            }
            if (isset($field['validate']) and !validateField($item[$fname], $field['validate'])) {
                return [
                    false,
                    430,
                    $item[$fname] . ' value failed validation.'
                ];
            }
            if (isset($field['auto']) and $field['auto'] === true) {
                if (!empty($field['auto_type'])) {
                    $atype = $field['auto_type'];
                } else {
                    $atype = 'random_string';
                }
                $atypeArr = explode(':', $atype);
                switch ($atypeArr[0]) {
                    case 'datetime':
                        if ($atypeArr[4] == 'updates') {
                            $nowTime = new DateTime('now');
                            $dateStr = $nowTime->format('Ymd\TH:i:s');
                            $item[$fname] = $dateStr;
                        }
                        break;
                    case 'function':
                        $auto_func = $atypeArr[2];
                        $auto_arg = $atypeArr[4];
                        if (function_exists($auto_func)) {
                            $item[$fname] = $auto_func($item[$auto_arg]);
                        } else {
                            logError('NO SUCH FUNCTION ' . $auto_func . '()', 'AUTO_TYPE FUNCTION MISSING');
                            $item[$fname] = false;
                        }
                        break;
                    case 'random_sring':
                    default:
                        $item[$fname] = generateRandomString(8);
                }
            }
            $updateFields[$fname] = [
                'schema' => $field,
                'value' => $item[$fname]
            ];
        }
    }
    switch ($store['type']) {
        case 'db':
            $dbTable = $store['table'];
            $dbAuth = $store['auth'];
            $itemCheckSql = getDbArray("SELECT `id` FROM `{$dbTable}` WHERE `id` = '{$id}';", $store['auth']);
            if ($itemCheckSql[0] === false) { return [false, 444, 'db connect error']; }
            $itemCheck = $itemCheckSql[2][0];
            if ($itemCheck['id'] != $id) {
                return [
                    false,
                    431,
                    ['failed to locate item in database', $dbTable, $store['auth'], $id, $itemCheck]
                ];
            }
            $dbQueryArr = [];
            foreach ($updateFields as $uKey => $uData) {
                if (empty($uData['schema']['do_not_hash']) and $uData['schema']['type'] != "password") {
                    $uVal = base64_encode(json_encode($uData['value']));
                } else {
                    $uVal = $uData['value'];
                }
                array_push($dbQueryArr, "`{$uKey}` = '{$uVal}'");
            }
            $dbQueryList = implode(',', $dbQueryArr);
            $dbQuery = "UPDATE `{$dbTable}` SET " . $dbQueryList . " WHERE `id` = '{$id}';";
            //return [false, 444, [$updateFields, $dbQuery]];
            $updateItemExec = getDb($dbQuery, $store['auth']);
            if ($updateItemExec[0] === true) {
                return $updateItemExec;
            } else {
                return [
                    false,
                    432,
                    'database exec error'
                ];
            }
            break;
        case 'controller':
            // this probably should be done at some point //
            break;
        case 'file':
        default:
            $data = getModelItems($model, true);
            $savedata = [];
            foreach ($data as $instance) {
                $save = $instance;
                if ($instance['id'] == $id) {
                    foreach ($instance as $iKey => $iVal) {
                        if (isset($updateFields[$iKey])) {
                            $instance[$iKey] = $updateFields[$iKey]['value'];
                        }
                    }
                }
                array_push($savedata, $instance);
            }
            $saveExec = storeModelItems($model, $savedata);
            return $saveExec;
    }
}

function storeModelItems($model, $save) {
    global $api_models;
    global $plugins;
    global $oset;
    if (empty($api_models[$model])) {
        return [false, 404, 'no such model ' . $model];
    }
    $source = $api_models[$model]['source'];
    $store = $api_models[$model]['store'];
    $storeType = $api_models[$model]['storeType'];
    if ($source == 'core') {
        $store = $oset['file_root'] . '/' . $store;
    }
    if ($storeType == "controller") {
        //
    } elseif (preg_match('/^__.*db__:\/\/.*$/', $store)) {
        $dbSourceName = explode(':', $store)[0];
        foreach ($plugins as $pluginName => $pluginConf) {
            if (isset($pluginConf['db_sources'])) {
                foreach ($pluginConf['db_sources'] as $srcName => $srcConf) {
                    if ($srcName == $dbSourceName) {
                        $dbAuthArray = [];
                        foreach (['host', 'name', 'user', 'pass'] as $dbci) {
                            if (explode(':', $srcConf[$dbci])[0] == "oset") {
                                array_push($dbAuthArray, $oset[explode(':', $srcConf[$dbci])[2]]);
                            } else {
                                array_push($dbAuthArray, $srcConf[$dbci]);
                            }
                        }
                        $dbStore = explode('/', $store);
                        $dbTable = $dbStore[2];
                        $modelObject = new ObjectModel($model);
                        $modelSchema = $modelObject->schema;
                        $modelFields = $modelSchema['fields'];
                        $dbCols = getDbArray("SHOW COLUMNS FROM `{$dbTable}`;", $dbAuthArray);
                        if ($dbCols[0] === true) {
                            if (isset($modelSchema['assign_by_group']) and $modelSchema['assign_by_group'] === true) {
                                $addOwnerColumn = true;
                                foreach ($modelFields as $ochk) {
                                    if ($ochk['name'] == 'owner_group') {
                                        $addOwnerColumn = false;
                                    }
                                }
                                if ($addOwnerColumn === true) {
                                    array_push($modelFields, [
                                        'name' => 'owner_group',
                                        'type' => 'str',
                                        'do_not_hash' => true
                                    ]);
                                }
                            }
                            foreach ($modelFields as $mf) {
                                $mfName = $mf['name'];
                                $addDbCol = true;
                                foreach ($dbCols[2] as $dbc) {
                                    if ($dbc['Field'] == "id") { continue; }
                                    if ($dbc['Field'] == $mfName) { $addDbCol = false; }
                                }
                                if ($addDbCol === true) {
                                    getDb("ALTER TABLE `{$dbTable}` ADD COLUMN `{$mfName}` LONGTEXT;", $dbAuthArray) or apiDie("Database error while creating column {$mfName} in {$dbTable}", 500);
                                }
                            }
                            foreach ($dbCols[2] as $dbc) {
                                $colName = $dbc['Field'];
                                if ($colName == "id") { continue; }
                                $delDbCol = true;
                                foreach ($modelFields as $mf) {
                                    if ($mf['name'] == $colName) {
                                        $delDbCol = false;
                                    }
                                }
                                if ($delDbCol === true) {
                                    getDb("ALTER TABLE `{$dbTable}` DROP COLUMN `{$colName}`;", $dbAuthArray) or apiDie("Database error while pruning column {$colName} from {$dbTable}", 500);
                                }
                            }
                        } else {
                            $makeTableFields = [
                                'id VARCHAR(100) UNIQUE'
                            ];
                            foreach ($modelFields as $field) {
                                array_push($makeTableFields, $field['name'] . ' LONGTEXT');
                            }
                            if (isset($modelSchema['assign_by_group']) and $modelSchema['assign_by_group'] === true) {
                                array_push($makeTableFields, 'owner_group VARCHAR(100)');
                            }
                            $makeTableFields = implode(', ', $makeTableFields);
                            $makeTableQuery = "CREATE TABLE IF NOT EXISTS {$dbTable} ({$makeTableFields});";
                            $makeTable = getDb($makeTableQuery, $dbAuthArray);
                        }
                        $dropData = getDb("DELETE FROM {$dbTable} WHERE 1;", $dbAuthArray);
                        $addOwnerColumn = true;
                        foreach ($modelFields as $ochk) {
                            if ($ochk['name'] == 'owner_group') {
                                $addOwnerColumn = false;
                            }
                        }
                        foreach ($save as $saveItem) {
                            $fieldListSql = ['`id`'];
                            $valueListSql = [$saveItem['id']];
                            if (isset($saveItem['owner_group']) and $addOwnerColumn === true) {
                                array_push($modelFields, [
                                    'name' => 'owner_group',
                                    'type' => 'str',
                                    'do_not_hash' => true
                                ]);
                            }
                            foreach ($modelFields as $field) {
                                array_push($fieldListSql, '`' . $field['name'] . '`');
                                if (empty($field['do_not_hash']) or $field['do_not_hash'] !== true) {
                                    array_push($valueListSql, base64_encode(json_encode($saveItem[$field['name']])));
                                } else {
                                    $sqlEscVal = dbEsc($saveItem[$field['name']], $dbAuthArray)[2];
                                    array_push($valueListSql, $sqlEscVal);
                                }
                            }
                            $fieldListSql = implode(',', $fieldListSql);
                            $valueListSql = "'" . implode("', '", $valueListSql) . "'";
                            $sqlQueryString = "INSERT INTO `{$dbTable}` ({$fieldListSql}) VALUES ({$valueListSql});";
                            $sqlExec = getDb($sqlQueryString, $dbAuthArray);
                        }
                        return [true, 200, 'okay'];
                    }
                }
            }
        }
    } else {
        if (emit_file($store, $save)) {
            return [true, 200, 'ok'];
        }
    }
    return [false, 500, 'failed to store data'];
}

function getPluginComposerUnique($plugin) {
    global $plugins;
    global $oset;
    $reqList = [];
    $unqList = [];
    $composer_base = parse_file(__DIR__ . '/composer_base.json')['require'];
    foreach ($composer_base as $pkg => $ver) {
//        $reqList[$pkg] = $ver;
        array_push($reqList, $pkg);
    }
    foreach ($plugins as $pluginName => $pluginConf) {
        if ($pluginName != $plugin) {
            if (isset($pluginConf['composer'])) {
                foreach($pluginConf['composer'] as $pkg => $ver) {
                    if (!in_array($pkg, $reqList)) {
//                    if (!isset($reqList[$pkg])) {
//                        $reqList[$pkg] = $ver;
                        array_push($reqList, $pkg);
                    }
                }
            }
        }
    }
    if (isset($plugins[$plugin]['composer'])) {
        foreach ($plugins[$plugin]['composer'] as $pkg => $ver) {
            if (!in_array($pkg, $reqList)) {
//            if (!isset($reqList[$pkg])) {
//                $unqList[$pkg] = $ver;
                array_push($unqList, $pkg);
            }
        }
    }
    return $unqList;
}

function requireComposerPkg($pkg) {
    global $oset;
    global $plugins;
    if (isset($oset['disable_composer']) and $oset['disable_composer'] === true) { return false; }
    if (empty($oset['composer_path'])) {
        $oset['composer_path'] = '/usr/local/bin/composer';
    }
    chdir($oset['file_root'] . '/composer');
    shell_exec($oset['composer_path'] . ' require ' . $pkg . ' >> composer_update.log 2>&1');
    sleep(2);
    return true;
}

function removeComposerPkg($pkg) {
    global $oset;
    global $plugins;
    if (isset($oset['disable_composer']) and $oset['disable_composer'] === true) { return false; }
    if (empty($oset['composer_path'])) {
        $oset['composer_path'] = '/usr/local/bin/composer';
    }
    chdir($oset['file_root'] . '/composer');
    shell_exec($oset['composer_path'] . ' remove ' . $pkg . ' >> composer_update.log 2>&1');
    sleep(2);
    return true;
}

function buildComposerRequires() {
    global $oset;
    global $plugins;
    if (isset($oset['disable_composer']) and $oset['disable_composer'] === true) { return false; }
    if (empty($oset['composer_path'])) {
        $oset['composer_path'] = '/usr/local/bin/composer';
    }
    $plugins = buildPluginRegistry();
    $core_config = parse_file($oset['file_root'] . '/utils/composer_base.json');
    $core_reqs = $core_config['require'];
    foreach ($plugins as $pluginName => $pluginConf) {
        if (isset($pluginConf['composer'])) {
            foreach ($pluginConf['composer'] as $pkg => $ver) {
                if (!isset($core_reqs[$pkg])) {
                    $core_reqs[$pkg] = $ver;
                }
            }
        }
    }
    if (emit_file($oset['file_root'] . '/composer/composer.json', ["require"=>$core_reqs])) {
        chdir($oset['file_root'] . '/composer');
        shell_exec($oset['composer_path'] . ' update >> composer_update.log 2>&1');
        sleep(2);
        return true;
    } else {
        return false;
    }
}

function updateComposerPackages() {
    global $oset;
    global $plugins;
    if (isset($oset['disable_composer']) and $oset['disable_composer'] === true) { return false; }
    $base = parse_file(__DIR__ . '/composer_base.json')['require'];
    foreach ($plugins as $pluginName => $pluginConf) {
        if ($pluginConf['enabled'] === false) { continue; }
        if (!empty($pluginConf['composer'])) {
            foreach ($pluginConf['composer'] as $pkg => $ver) {
                if (!isset($base[$pkg])) {
                    $base[$pkg] = $ver;
                }
            }
        }
    }
    $allReqs = ['require'=>$base];
    if (empty($oset['composer_path'])) { $oset['composer_path'] = '/usr/local/bin/composer'; }
    if (emit_file($oset['file_root'] . '/composer/composer.json', $allReqs)) {
        chdir($oset['file_root'] . '/composer');
        shell_exec($oset['composer_path'] . ' update >> composer_update.log 2>&1');
        sleep(2);
        return true;
    } else {
        return false;
    }
}

function insertHtmlHead() {
    global $api_path;
    global $api_uri;
    global $plugins;
    global $oset;
    global $access;
    include(__DIR__ . '/../resource/head.php');
}

function metaVars($str) {
    global $oset;
    global $api_path;
    global $api_uri;
    global $access;
    $pathStrArr = array_slice($api_path, 2);
    $pathStr = "";
    foreach ($pathStrArr as $seg) {
        $pathStr .= ' - ' . $seg;
    }
    $str_out = str_replace(
        [
            '%APP_URL%',
            '%API_PATH%'
        ],
        [
            $oset['app_url'],
            $pathStr
        ],
        $str
    );
    return $str_out;
}

function insertPageHeader($context = null) {
    global $api_path;
    global $api_uri;
    global $plugins;
    global $oset;
    if ($api_path[1] == "login") { $context = "login"; $oset['vertical_layout'] = false; }
    include(__DIR__ . '/../resource/header.php');
}

function serveLocalFile($path = null, $attachment = false) {
    global $oset;
    if ($path == null or $path == "") { apiDie('bad', 400); }
    if (file_exists($path)) {
        if ($attachment === false) {
            $disposition = "inline";
        } else {
            header('Content-Description: File Transfer');
            header('Pragma: public');
            $disposition = "attachment";
        }
        $file_name = basename($path);
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        $expires = 0;
        switch ($file_ext) {
            case "json":
                $file_mime = "application/json;charset=UTF-8";
                $file_data = parse_file($path);
                $expires = 0;
                break;
            case "yaml":
            case "yml":
                $file_mime= "text/plain;charset=UTF-8";
                if (empty($oset['upgrade_yaml_files']) or $oset['upgrade_yaml_files'] === false) {
                    $file_data = file_get_contents($path);
                } else {
                    $file_data = yaml_emit(parse_file($path));
                }
                $expires = 0;
            case "css":
                $file_mime = "text/css;charset=UTF-8";
                $expires = 600;
                break;
            case "js":
                $file_mime = "text/javascript;charset=UTF-8";
                $expires = 86400;
                break;
            case "zip":
                $file_mime = "application/octet-stream";
                $expires = 86400;
                break;
            case "gif":
            case "png":
            case "jpg":
            case "jpeg":
                $expires = 86400;
            case "svg":
                $expires = 600;
            default:
                $file_mime = mime_content_type($path);
        }
        $file_size = filesize($path);
        header('Cache-Control: max-age=' . $expires);
        header('Content-Type: ' . $file_mime);
        header('Content-Disposition: ' . $disposition . '; filename=' . $file_name);
        header('Content-Length: ' . $file_size);
        if (empty($file_data)) {
            $file_contents = file_get_contents($path);
        } else {
            $file_contents = $file_data;
        }
        if ($attachment === false and $file_ext != "svg") {
            exit($file_contents);
        } else {
            readfile($path);
            die();
        }
    } else {
        apiDie("no such file " . $path, 404);
    }
}
function downloadLocalFile($path = null) {
    if ($path == null or $path == "") { apiDie('bad', 400); }
    if (!file_exists($path)) { apiDie("no such file", 404); }
    $file_name = "Old_Textures_Pack_Customized-" . time();
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . $file_name . '.zip');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($path));
    flush();
    readfile($path);
    die();
}
function real_scandir($dir) {
    return array_slice(array_diff(scandir($dir), array('..', '.', '.DS_Store')), 0);
}
function apiDie($msg = "", $code = 400, $ctype = "application/json") {
    global $api_method;
    global $api_path;
    if ($code > 399) {
        $logEvent = [
            "error" => $msg,
            "code" => $code
        ];
        logError($logEvent, "API_DIE");
        logEvent($logEvent);
    }
    if (is_array($msg)) {
        $msg = json_encode($msg);
    } elseif ($ctype == "application/json") {
        $msg = json_encode([$msg]);
    }
    logEvent([
        'path' => $api_path,
        'method' => $api_method,
        'client' => $_SERVER['REMOTE_ADDR'],
        'event' => 'apiDie()'
    ], 1);
    header("Content-Type: " . $ctype);
    http_response_code($code);
    publishEvent("API_DIE", $api_method, $api_path);
    die($msg);
}
function generateRandomString(int $length = 10) {
    $characters = '123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
function getHomeHref() {
    global $plugins;
    global $oset;
    if (isset($oset['require_login']) and $oset['require_login'] === true and !isset($_SESSION['userdata']['userRank'])) {
        if (!empty($oset['custom_login_page'])) {
            return "/" . $oset['custom_login_page'];
        } else {
            return "/login";
        }
    }
    if (isset($oset['landing_page'])) {
        return $oset['landing_page'];
    }
    return "/";
}
function returnToHome($context = null) {
    global $oset;
    global $plugins;
    global $api_path;
    if (isset($context)) {
        if (is_numeric($context)) {
            $optStr = "redirect_" . $context;
            if (!empty($oset[$optStr])) {
                header("Location: " . $oset[$optStr]);
            } else {
                include(__DIR__ . '/error_pages/error.php');
            }
        } else {
            header("Location: " . $context);
        }
    } else {
        header("Location: " . getHomeHref());
    }
    die();
}
function openTemplate($context = null) {
    global $oset;
    global $skip_template;
    global $access;
    include(__DIR__ . '/../resource/base_top.php');
}
function closeTemplate($context = null) {
    global $oset;
    global $skip_template;
    global $access;
    include(__DIR__ . '/../resource/base_bottom.php');
}
function getSessionLinks() {
    global $oset;
    global $plugins;
    $links = [];
    if (!empty($oset['custom_login_page'])) {
        $login_page = $oset['custom_login_page'];
    } else {
        $login_page = "login";
    }    
    if (isset($_SESSION['userdata'])) {
        if (accessMatch($_SESSION['id'], "moderator:user")) {
            array_push($links, [
                "href" => "/admin/settings",
                "icon" => "/resource/admin.png",
                "text" => "Admin"
            ]);
        }
        array_push($links, [
            "href" => "/account/settings",
            "icon" => "/resource/gear_white.png",
            "text" => "Settings"
        ]);
        array_push($links, [
            "href" => "/logout",
            "icon" => "/resource/logout.png",
            "text" => "Log Out"
        ]);
    } else {
        if (!isset($oset['hide_login']) or $oset['hide_login'] === false) {
            array_push($links, [
                "href" => "/" . $login_page,
                "icon" => "/resource/login.png",
                "text" =>"Log In"
            ]);
        }
    }
    return $links;
}
function getNavLinks() {
    global $oset;
    global $plugins;
    $links = [];
    foreach ($plugins as $pluginName => $pluginConf) {
        if (isset($pluginConf['nav_menu'])) {
            $navCount = 0;
            foreach ($pluginConf['nav_menu'] as $navName => $navConf) {
                $showItem = false;
                if (isset($navConf['access'])) {
                    $showItem = accessMatch($_SESSION['id'], $navConf['access']);
                }
                if (isset($navConf['access_max'])) {
                    $showItem = accessMax($_SESSION['id'], $navConf['access']);
                }
                if ($showItem === true) {
                    if (isset($navConf['icon'])) {
                        $navIcon = $navConf['icon'];
                    } else {
                        $navIcon = "/resource/plugin.png";
                    }
                    if (!isset($oset['show_nav_text']) or $oset['show_nav_text'] === true or (isset($navConf['show_text']) and $navConf['show_text'] === true)) {
                        $showText = true;
                    } else {
                        $showText = false;
                    }
                    array_push($links, [
                        "text" => $navName,
                        "icon" => $navIcon,
                        "show_text" => $showText,
                        "href" => $navConf['href'],
                        "id" => $navConf['menu_id'],
                        "plugin" => $pluginName
                    ]);
                    $navCount++;
                }
            }
        }
    }
    return $links;
}

// --- experiments

function in_array_tox($match, $arr) {
    if (count(explode('|', $match)) > 1) {
        $matches = explode('|', $match);
        foreach ($matches as $mx) {
            if (in_array($mx, $arr)) { return true; }
        }
    } else {
        if (in_array($match, $arr)) {
            return true;
        }
    }
    return false;
}

/*

"event bus" (extremely rudimentary and terrible in every way)

*/
function publishEvent($event_context, $event_method, $event_data) {
    global $oset;
    global $plugins;
    global $api_models;
    if (isset($oset['event_bus']) and $oset['event_bus'] === true) {
        foreach ($api_models as $modName => $modConf) {
            // if this is even an event handler object
            if (!empty($modConf['model_type']) and $modConf['model_type'] == "event_listener") {
                // if this handles this particular event
                if ($modConf['context'] == $event_context) {
                    $efunc = $modConf['function_name'];
                    // if the function exists
                    if (function_exists($efunc)) {
                        $eresult = $efunc($event_context, $event_method, $event_data);
                        return $eresult;
                    }
                }
            }
        }
    }
}

function toxCurl($url, $opts = null) {
    $req = new CurlRequest();
    $req->set_url($url);
    if ($opts !== null) {
        foreach ($opts as $optk => $optv) {
            switch ($optk) {
                case 'method':
                    $req->set_method($optv);
                    break;;
                case 'data':
                    $req->set_data($optv);
                    break;;
                case 'headers':
                    $req->set_headers($optv);
                    break;;
                case 'useragent':
                    $req->set_agent($optv);
                    break;;
            }
        }
    }
    $ret = $req->exec();
    return $ret;
}

// thanks buggedcom (https://gist.github.com/alexcorvi/df8faecb59e86bee93411f6a7967df2c)
function mime2ext($mime) {
    $mime_map = [
        'video/3gpp2'                                                               => '3g2',
        'video/3gp'                                                                 => '3gp',
        'video/3gpp'                                                                => '3gp',
        'application/x-compressed'                                                  => '7zip',
        'audio/x-acc'                                                               => 'aac',
        'audio/ac3'                                                                 => 'ac3',
        'application/postscript'                                                    => 'ai',
        'audio/x-aiff'                                                              => 'aif',
        'audio/aiff'                                                                => 'aif',
        'audio/x-au'                                                                => 'au',
        'video/x-msvideo'                                                           => 'avi',
        'video/msvideo'                                                             => 'avi',
        'video/avi'                                                                 => 'avi',
        'application/x-troff-msvideo'                                               => 'avi',
        'application/macbinary'                                                     => 'bin',
        'application/mac-binary'                                                    => 'bin',
        'application/x-binary'                                                      => 'bin',
        'application/x-macbinary'                                                   => 'bin',
        'image/bmp'                                                                 => 'bmp',
        'image/x-bmp'                                                               => 'bmp',
        'image/x-bitmap'                                                            => 'bmp',
        'image/x-xbitmap'                                                           => 'bmp',
        'image/x-win-bitmap'                                                        => 'bmp',
        'image/x-windows-bmp'                                                       => 'bmp',
        'image/ms-bmp'                                                              => 'bmp',
        'image/x-ms-bmp'                                                            => 'bmp',
        'application/bmp'                                                           => 'bmp',
        'application/x-bmp'                                                         => 'bmp',
        'application/x-win-bitmap'                                                  => 'bmp',
        'application/cdr'                                                           => 'cdr',
        'application/coreldraw'                                                     => 'cdr',
        'application/x-cdr'                                                         => 'cdr',
        'application/x-coreldraw'                                                   => 'cdr',
        'image/cdr'                                                                 => 'cdr',
        'image/x-cdr'                                                               => 'cdr',
        'zz-application/zz-winassoc-cdr'                                            => 'cdr',
        'application/mac-compactpro'                                                => 'cpt',
        'application/pkix-crl'                                                      => 'crl',
        'application/pkcs-crl'                                                      => 'crl',
        'application/x-x509-ca-cert'                                                => 'crt',
        'application/pkix-cert'                                                     => 'crt',
        'text/css'                                                                  => 'css',
        'text/x-comma-separated-values'                                             => 'csv',
        'text/comma-separated-values'                                               => 'csv',
        'application/vnd.msexcel'                                                   => 'csv',
        'application/x-director'                                                    => 'dcr',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
        'application/x-dvi'                                                         => 'dvi',
        'message/rfc822'                                                            => 'eml',
        'application/x-msdownload'                                                  => 'exe',
        'video/x-f4v'                                                               => 'f4v',
        'audio/x-flac'                                                              => 'flac',
        'video/x-flv'                                                               => 'flv',
        'image/gif'                                                                 => 'gif',
        'application/gpg-keys'                                                      => 'gpg',
        'application/x-gtar'                                                        => 'gtar',
        'application/x-gzip'                                                        => 'gzip',
        'application/mac-binhex40'                                                  => 'hqx',
        'application/mac-binhex'                                                    => 'hqx',
        'application/x-binhex40'                                                    => 'hqx',
        'application/x-mac-binhex40'                                                => 'hqx',
        'text/html'                                                                 => 'html',
        'image/x-icon'                                                              => 'ico',
        'image/x-ico'                                                               => 'ico',
        'image/vnd.microsoft.icon'                                                  => 'ico',
        'text/calendar'                                                             => 'ics',
        'application/java-archive'                                                  => 'jar',
        'application/x-java-application'                                            => 'jar',
        'application/x-jar'                                                         => 'jar',
        'image/jp2'                                                                 => 'jp2',
        'video/mj2'                                                                 => 'jp2',
        'image/jpx'                                                                 => 'jp2',
        'image/jpm'                                                                 => 'jp2',
        'image/jpeg'                                                                => 'jpeg',
        'image/pjpeg'                                                               => 'jpeg',
        'application/x-javascript'                                                  => 'js',
        'application/json'                                                          => 'json',
        'text/json'                                                                 => 'json',
        'application/vnd.google-earth.kml+xml'                                      => 'kml',
        'application/vnd.google-earth.kmz'                                          => 'kmz',
        'text/x-log'                                                                => 'log',
        'audio/x-m4a'                                                               => 'm4a',
        'application/vnd.mpegurl'                                                   => 'm4u',
        'audio/midi'                                                                => 'mid',
        'application/vnd.mif'                                                       => 'mif',
        'video/quicktime'                                                           => 'mov',
        'video/x-sgi-movie'                                                         => 'movie',
        'audio/mpeg'                                                                => 'mp3',
        'audio/mpg'                                                                 => 'mp3',
        'audio/mpeg3'                                                               => 'mp3',
        'audio/mp3'                                                                 => 'mp3',
        'video/mp4'                                                                 => 'mp4',
        'video/mpeg'                                                                => 'mpeg',
        'application/oda'                                                           => 'oda',
        'audio/ogg'                                                                 => 'ogg',
        'video/ogg'                                                                 => 'ogg',
        'application/ogg'                                                           => 'ogg',
        'application/x-pkcs10'                                                      => 'p10',
        'application/pkcs10'                                                        => 'p10',
        'application/x-pkcs12'                                                      => 'p12',
        'application/x-pkcs7-signature'                                             => 'p7a',
        'application/pkcs7-mime'                                                    => 'p7c',
        'application/x-pkcs7-mime'                                                  => 'p7c',
        'application/x-pkcs7-certreqresp'                                           => 'p7r',
        'application/pkcs7-signature'                                               => 'p7s',
        'application/pdf'                                                           => 'pdf',
        'application/octet-stream'                                                  => 'pdf',
        'application/x-x509-user-cert'                                              => 'pem',
        'application/x-pem-file'                                                    => 'pem',
        'application/pgp'                                                           => 'pgp',
        'application/x-httpd-php'                                                   => 'php',
        'application/php'                                                           => 'php',
        'application/x-php'                                                         => 'php',
        'text/php'                                                                  => 'php',
        'text/x-php'                                                                => 'php',
        'application/x-httpd-php-source'                                            => 'php',
        'image/png'                                                                 => 'png',
        'image/x-png'                                                               => 'png',
        'application/powerpoint'                                                    => 'ppt',
        'application/vnd.ms-powerpoint'                                             => 'ppt',
        'application/vnd.ms-office'                                                 => 'ppt',
        'application/msword'                                                        => 'doc',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/x-photoshop'                                                   => 'psd',
        'image/vnd.adobe.photoshop'                                                 => 'psd',
        'audio/x-realaudio'                                                         => 'ra',
        'audio/x-pn-realaudio'                                                      => 'ram',
        'application/x-rar'                                                         => 'rar',
        'application/rar'                                                           => 'rar',
        'application/x-rar-compressed'                                              => 'rar',
        'audio/x-pn-realaudio-plugin'                                               => 'rpm',
        'application/x-pkcs7'                                                       => 'rsa',
        'text/rtf'                                                                  => 'rtf',
        'text/richtext'                                                             => 'rtx',
        'video/vnd.rn-realvideo'                                                    => 'rv',
        'application/x-stuffit'                                                     => 'sit',
        'application/smil'                                                          => 'smil',
        'text/srt'                                                                  => 'srt',
        'image/svg+xml'                                                             => 'svg',
        'application/x-shockwave-flash'                                             => 'swf',
        'application/x-tar'                                                         => 'tar',
        'application/x-gzip-compressed'                                             => 'tgz',
        'image/tiff'                                                                => 'tiff',
        'text/plain'                                                                => 'txt',
        'text/x-vcard'                                                              => 'vcf',
        'application/videolan'                                                      => 'vlc',
        'text/vtt'                                                                  => 'vtt',
        'audio/x-wav'                                                               => 'wav',
        'audio/wave'                                                                => 'wav',
        'audio/wav'                                                                 => 'wav',
        'application/wbxml'                                                         => 'wbxml',
        'video/webm'                                                                => 'webm',
        'audio/x-ms-wma'                                                            => 'wma',
        'application/wmlc'                                                          => 'wmlc',
        'video/x-ms-wmv'                                                            => 'wmv',
        'video/x-ms-asf'                                                            => 'wmv',
        'application/xhtml+xml'                                                     => 'xhtml',
        'application/excel'                                                         => 'xl',
        'application/msexcel'                                                       => 'xls',
        'application/x-msexcel'                                                     => 'xls',
        'application/x-ms-excel'                                                    => 'xls',
        'application/x-excel'                                                       => 'xls',
        'application/x-dos_ms_excel'                                                => 'xls',
        'application/xls'                                                           => 'xls',
        'application/x-xls'                                                         => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
        'application/vnd.ms-excel'                                                  => 'xlsx',
        'application/xml'                                                           => 'xml',
        'text/xml'                                                                  => 'xml',
        'text/xsl'                                                                  => 'xsl',
        'application/xspf+xml'                                                      => 'xspf',
        'application/x-compress'                                                    => 'z',
        'application/x-zip'                                                         => 'zip',
        'application/zip'                                                           => 'zip',
        'application/x-zip-compressed'                                              => 'zip',
        'application/s-compressed'                                                  => 'zip',
        'multipart/x-zip'                                                           => 'zip',
        'text/x-scriptzsh'                                                          => 'zsh',
    ];

    return isset($mime_map[$mime]) === true ? $mime_map[$mime] : false;
}