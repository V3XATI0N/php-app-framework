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

// model permissions are enforced here.

if (empty($api_path[2])) {
    $allReturn = [];
    foreach ($api_models as $modName => $modConf) {
        if (empty($_SESSION['id'])) {
            if (!empty($modConf['access'])) {
                if (explode(':', $modConf['access'][$api_method])[0] == "public") {
                    if (!empty($url_query['details']) and $url_query['details'] == "true") {
                        $allReturn[$modName] = $modConf;
                    } else {
                        array_push($allReturn, $modName);
                    }
                }
            }
        } else {
            if (!empty($url_query['details']) and $url_query['details'] == "true") {
                $allReturn[$modName] = $modConf;
            } else {
                array_push($allReturn, $modName);
            }
        }
    }
    apiDie($allReturn, 200);
}
$model_name = $api_path[2];
if (!isset($api_models[$model_name])) { apiDie('no such model ' . $model_name, 404); }
$model_def = $api_models[$api_path[2]];
if (!isset($model_def['access'])) {
    $model_access = [
        "GET" => "user:user",
        "PATCH" => "moderator:admin",
        "POST" => "moderator:admin",
        "DELETE" => "moderator:admin"
    ];
} else {
    $model_access = $model_def['access'];
}
if (!isset($model_access[$api_method])) {
    $model_access[$api_method] = "admin:admin";
}

if (!accessMatch($_SESSION['id'], $model_access[$api_method])) {
    logError([$_SESSION['id'] . ' attempted to ' . $api_method . ' a ' . $model_name]);
    apiDie('no permission.', 401);
}

if (!empty($model_def['storeType']) and $model_def['storeType'] == "controller") {
    if (file_exists($model_def['store'] . '/' . $api_method . '.php')) {
        $api_models = getModels();
        include($model_def['store'] . '/' . $api_method . '.php');
    } else {
        apiDie('method not allowed', 405);
    }
} else {
    if (file_exists(__DIR__ . '/' . $api_method . '.php')) {
        $api_models = getModels();
        include(__DIR__ . '/' . $api_method . '.php');
    } else {
        apiDie('method not allowed', 405);
    }
}