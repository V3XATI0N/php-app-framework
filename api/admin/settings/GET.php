<?php

if (!accessMatch($_SESSION['id'], "moderator:user")) {
    apiDie('no access', 401);
}

$schema_full = parse_file(__DIR__ . '/../../../utils/settings_schema.json');
$schema_groups = $schema_full['groups'];
$core_schema = $schema_full['core'];
$list_schema = $schema_full['list_schema'];
$schema_return = [];
$list_return = [];
$group_return = [];

foreach ($plugins as $pluginName => $pluginConf) {
    $file_root = $pluginConf['file_root'];
    if (file_exists($file_root . '/settings_schema.json')) {
        $pSchema = parse_file($file_root . '/settings_schema.json');
        if (!isset($pSchema['core']) or !isset($pSchema['groups'])) { continue; }
        $pCore = $pSchema['core'];
        if (isset($pSchema['list'])) {
            $pList = $pSchema['list'];
            foreach ($pList as $key => $conf) {
                if (!isset($list_schema[$key])) {
                    $list_schema[$key] = $conf;
                }
            }
        }
        $pGroups = $pSchema['groups'];
        foreach ($pGroups as $key => $conf) {
            if (!isset($schema_groups[$key])) {
                $schema_groups[$key] = $conf;
            }
        }
        foreach ($pCore as $key => $conf) {
            if (!isset($core_schema[$key])) {
                $core_schema[$key] = $conf;
                $core_schema[$key]['source'] = $pluginName;
            }
        }
    }
    if (file_exists($pluginConf['file_root'] . '/settings_override.json')) {
        $settingsOverride = parse_file($pluginConf['file_root'] . '/settings_override.json');
        foreach ($settingsOverride as $key => $val) {
            $core_schema[$key]['source'] = $pluginName;
        }
    }
}

foreach ($core_schema as $key => $conf) {
    if (!isset($conf['group'])) {
        error_log(json_encode([$key, $conf], JSON_PRETTY_PRINT));
    };
    if (accessMatch($_SESSION['id'], $schema_groups[$conf['group']]['access'])) {
        // logError(['checking access against ' . $schema_groups[$conf['group']]['access']], 'meh!!!!!');
        $schema_return[$key] = $conf;
        if (!isset($conf['source'])) { $schema_return[$key]['source'] = "core"; }
        if (isset($oset[$key])) {
            $schema_return[$key]['value'] = $oset[$key];
        } elseif (isset($conf['default'])) {
            $schema_return[$key]['value'] = $conf['default'];
        } else {
            $schema_return[$key]['value'] = null;
        }
        if (isset($list_schema[$key])) {
            $list_return[$key] = $list_schema[$key];
        }
    }
}

foreach ($schema_groups as $groupName => $groupConf) {
    if (accessMatch($_SESSION['id'], $groupConf['access'])) {
        logError(['checking access for ' . $groupConf['access']], '!!!!!!!!!!!!!!!!!!');
        $group_return[$groupName] = $groupConf;
    }
}

$return = [
    "groups" => $group_return,
    "core" => $schema_return,
    "lists" => $list_return
];

if (isset($api_path[4]) and isset($return[$api_path[4]])) {
    if (isset($api_path[5]) and $api_path[5] != "") {
        if (isset($return[$api_path[4]][$api_path[5]])) {
            apiDie($return[$api_path[4]][$api_path[5]], 200);
        } else {
            apiDie('not found', 404);
        }
    }
    apiDie($return[$api_path[4]], 200);
}

apiDie($return, 200);
