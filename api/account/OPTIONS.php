<?php

switch($api_path[3]) {
    case "theme":
        $optReturn = [];
        foreach ($oset_schema as $optName => $optConf) {
            if ($optConf['group'] == "presentation") {
                $optReturn[$optName] = $optConf;
            }
        }
        apiDie($optReturn, 200);
        break;
    case "plugins":
        $optReturn = [];
        if (isset($plugins[$api_path[4]])) {
            $pluginConf = $plugins[$api_path[4]];
            $pluginDir = $pluginConf['file_root'];
            if (file_exists($pluginDir . '/account_settings/' . $api_method . '.php')) {
                include($pluginDir . '/account_settings/' . $api_method . '.php');
            } else {
                apiDie('bad. no.', 404);
            }
        }
    default:
        apiDie('no such module', 404);
}