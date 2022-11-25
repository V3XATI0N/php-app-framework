<?php

if (empty($api_path[5])) {
    apiDie('specify module.', 400);
}
if (empty($api_path[4])) {
    apiDie('specify plugin.', 400);
}

$plugin = $api_path[4];
$module = $api_path[5];

foreach ($plugins as $pluginName => $pluginConf) {
    if ($pluginName == $plugin) {
        if (empty($pluginConf['account_settings_modules'])) {
            apiDie('no modules defined', 404);
        }
        $pdir = $pluginConf['file_root'];
        foreach ($pluginConf['account_settings_modules'] as $cmod) {
            if ($cmod['href'] == $module) {
                if (uac($cmod['access']) === true) {
                    if (file_exists($pdir . '/account_settings/' . $api_method . '.php')) {
                        include($pdir . '/account_settings/' . $api_method . '.php');
                    }
                } else {
                    apiDie('access denied', 403);
                }
            }
        }
        apiDie(['no such module ' . $module, $pluginConf['account_settings_modules']], 404);
    }
}

apiDie('try again, bucko.', 400);