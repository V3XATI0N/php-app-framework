<?php

$plugins = buildPluginRegistry(true);
$pluginReturn = [];
foreach ($plugins as $pluginName => $pluginConf) {
    $plugDir = $pluginConf['file_root'];
    $pluginConf['settings_changes'] = [];
    $pluginConf['settings_override'] = parse_file($plugDir . '/settings_override.json');
    $pluginConf['settings_schema'] = parse_file($plugDir . '/settings_schema.json');
    $pluginConf['version_compare'] = version_compare($version_data['version'], $pluginConf['depends']['core']);
    foreach ($pluginConf['settings_override'] as $oKey => $oVal) {
        if (empty($oset[$oKey]) or $oset[$oKey] != $oVal) {
            if (!empty($pluginConf['settings_schema']['core'][$oKey]) and $pluginConf['settings_schema']['core'][$oKey]['type'] == "password") {
                $pluginConf['settings_changes'][$oKey] = "XXXXXX";
            } else {
                $pluginConf['settings_changes'][$oKey] = $oVal;
            }
        }
    }
    if (file_exists($plugDir . '/info.html')) {
        $pluginConf['info'] = file_get_contents($plugDir . '/info.html');
    }
    if (!empty($api_path[4]) && $api_path[4] == $pluginName) {
        apiDie([$pluginName => $pluginConf], 200);
    }
    $pluginReturn[$pluginName] = $pluginConf;
}

if (!empty($api_path[4])) {
    apiDie('no such plugin', 404);
}

apiDie($pluginReturn, 200);
