<?php

if (empty($api_path[5])) { apiDie(false, 400); }

$plugin = $api_path[4];
$module = $api_path[5];

foreach ($plugins as $pluginName => $pluginConf) {
    if ($pluginName == $plugin) {
        if (isset($pluginConf['account_settings_modules'])) {
            foreach ($pluginConf['account_settings_modules'] as $mod) {
                if ($mod['href'] == $module) {
                    if (file_exists($pluginConf['file_root'] . '/account_settings/' . $module . '/' . $api_method . '.php')) {
                        include($pluginConf['file_root'] . '/account_settings/' . $module . '/' . $api_method . '.php');
                    }
                }
            }
        }
    }
}
apiDie('invalid plugin module ' . $plugin . '/' . $module, 404);