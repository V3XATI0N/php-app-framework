<?php

switch ($api_path[2]) {
    case "plugin":
        $plugin = $api_path[3];
        $module = $api_path[4];
        if (!empty($plugins[$plugin])) {
            if (!empty($plugins[$plugin]['account_settings_modules'])) {
                $mods = $plugins[$plugin]['account_settings_modules'];
                $pdir = $plugins[$plugin]['file_root'];
                foreach ($mods as $mod) {
                    if ($mod['href'] == $module) {
                        if (!isset($mod['access']) or uac($mod['access']) === false) {
                            apiDie('denied', 403);
                        }
                        if (file_exists($pdir . '/account_settings/' . $module . '/' . $api_method . '.php')) {
                            include($pdir . '/account_settings/' . $module . '/' . $api_method . '.php');
                            die();
                        } else {
                            apiDie('invalid configuration', 500);
                        }
                    }
                }
                apiDie('no such module', 404);
            } else {
                apiDie('plugin not configured', 404);
            }
        } else {
            apiDie('no such plugin', 404);
        }
        break;
}

apiDie('invalid.', 499);
