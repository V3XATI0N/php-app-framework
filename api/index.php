<?php

if (!isset($_SESSION['id'])) {
    startSession();
    if (isset($_SERVER['HTTP_X_TOXAPI_AUTH'])) {
        $tryKey = $_SERVER['HTTP_X_TOXAPI_AUTH'];
        $auth = verifyLogin($oset['api_tmp'], $tryKey);
        if ($auth['success'] === true) {
            $_SESSION = $auth;
            session_write_close();
        } else {
            session_destroy();
            apiDie($auth, 403);
        }
    } elseif (isset($_SERVER['PHP_AUTH_USER']) and isset($_SERVER['PHP_AUTH_PW'])) {
        if (isset($oset['disable_api_basic_auth']) and $oset['disable_api_basic_auth'] === true) {
            apiDie('no.', 401);
        }
        $user = $_SERVER['PHP_AUTH_USER'];
        $pass = $_SERVER['PHP_AUTH_PW'];
        $auth = verifyLogin($user, $pass);
        if ($auth['success'] === true) {
            $_SESSION = $auth;
            session_write_close();
        } else {
            session_destroy();
            apiDie($auth, 403);
        }
    }
}

if (!isset($api_path[2]) or $api_path[2] == "") {
    returnToHome();
}

if (file_exists(__DIR__ . '/' . $api_path[2] . '/index.php')) {
    include(__DIR__ . '/' . $api_path[2] . '/index.php');
    die();
} elseif (file_exists(__DIR__ . '/' . $api_path[2] . '/' . $api_method . '.php')) {
    include(__DIR__ . '/' . $api_path[2] . '/' . $api_method . '.php');
    die();
} else {
    $apiControllerPath = NULL;
    foreach ($plugins as $pluginName => $pluginConf) {
        $pluginRoot = $pluginConf['file_root'];
        if (file_exists($pluginRoot . '/api/' . $api_path[2] . '/index.php')) {
            $apiControllerPath = $pluginRoot . '/api/' . $api_path[2] . '/index.php';
        } elseif (file_exists($pluginRoot . '/api/' . $api_path[2] . '/' . $api_method . '.php')) {
            $apiControllerPath = $pluginRoot . '/api/' . $api_path[2] . '/' . $api_method . '.php';
        }
        if ($apiControllerPath != NULL) {
            if (isset($pluginConf['api']) and isset($pluginConf['api'][$api_path[2]])) {
                $epConf = $pluginConf['api'][$api_path[2]];
                if (!isset($epConf['access']) or !isset($epConf['access'][$api_method])) {
                    logError('no access rules found for api/' . $api_path[2] . '/' . $api_method);
                    $epConf['access'] = [
                        $api_method => "admin:admin"
                    ];
                }
                if (!accessMatch($_SESSION['id'], $epConf['access'][$api_method])) {
                    apiDie('nothing to see here ...', 404);
                }
            } else {
                if (!isset($_SESSION['userdata'])) { apiDie('no access', 401); }
            }
            include($apiControllerPath);
            die();
        }
    }
    apiDie('no such endpoint ' . $api_path[2], 404);
}
