<?php

$data = getUsersAndGroups(true)['users'];
$conf = $userConf['theme'];
$scriptOut = "";
foreach ($api_data as $key => $val) {
    if (($key == "local_dark_theme" and is_bool($val)) or (isset($oset_schema[$key]) and $oset_schema[$key]['group'] == "presentation")) {
        if (!isset($oset['user_themes']) or $oset['user_themes'] === true or $key == "local_dark_theme") {
            if ($key == "local_dark_theme") {
                $valType = "bool";
            } else {
                $valType = $oset_schema[$key]['type'];
            }
            if (isValidType($val, $valType) and $val != $conf[$key]) {
                $conf[$key] = $val;
                if (file_exists($oset['file_root'] . '/utils/settings_js/' . $key . '/update.js')) {
                    $scriptOut .= str_replace(["\r", "\n"], '', file_get_contents($oset['file_root'] . '/utils/settings_js/' . $key . '/update.js'));
                }
            }
        } else {
            apiDie('invalid module', 400);
        }
    }
}
$userConf['theme'] = $conf;
$_SESSION['userdata']['theme'] = $conf;
$saveData = [];
if (isset($_SESSION['userdata']['auth_source'])) {
    $saveFile = parse_file($oset['file_root'] . '/data/plugin_users.json');
    foreach ($saveFile as $un => $uc) {
        if ($un == $userName and $uc['id'] == $userid) {
            $saveData[$un] = $userConf;
        } else {
            $saveData[$un] = $uc;
        }
    }
    $saveFile = $saveData;
    if (emit_file($oset['file_root'] . '/data/plugin_users.json', $saveFile)) {
        apiDie(["conf" => $conf, "script" => $scriptOut], 200);
    } else {
        apiDie('error lol', 500);
    }
} else {
    $saveFile = getUsersAndGroups();
    foreach ($data as $un => $uc) {
        if ($un == $userName and $uc['id'] == $userid) {
            $saveData[$un] = $userConf;
        } else {
            $saveData[$un] = $uc;
        }
    }
    $saveFile['users'] = $saveData;
    if (saveUsersAndGroups($saveFile)) {
        apiDie(["conf" => $conf, "script" => $scriptOut], 200);
    } else {
        apiDie('error!', 500);
    }
}