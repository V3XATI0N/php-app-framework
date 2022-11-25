<?php

$content_type = explode(';', $_SERVER['CONTENT_TYPE'])[0];
if ($content_type == "multipart/form-data") {
    $api_data = $_POST;
    $api_files = $_FILES;
    if (isset($api_data['__method__']) and $api_data['__method__'] == 'PATCH') {
        $api_method = "PATCH";
    }
}

if (file_exists(__DIR__ . '/' . $api_path[3] . '/access.json')) {
    $accessRules = parse_file(__DIR__ . '/' . $api_path[3] . '/access.json')['methods'];
    if (isset($accessRules[$api_method])) {
        $rule = $accessRules[$api_method];
    } else {
        $rule = "admin:moderator";
    }
} else {
    $rule = "admin:moderator";
}
if (!accessMatch($_SESSION['id'], $rule)) {
    apiDie(["unauthorized", $rule], 401);
}

$target = $api_path[3];
$adminModuleDefinition = $api_models['admin_modules'];
$adminModuleItems = getModelItems('admin_modules');

if (file_exists(__DIR__ . '/' . $target . '/' . $api_method . '.php')) {
    include(__DIR__ . '/' . $target . '/' . $api_method . '.php');
} elseif (file_exists(__DIR__ . '/' . $api_method . '.php')) {
    include(__DIR__ . '/' . $api_method . '.php');
} else {
    if ($api_path[3] == "plugin") {
        $plugin = $api_path[4];
        $controller_path = $oset['file_root'] . '/plugins/' . $plugin . '/admin/' . $api_path[5] . '/' . $api_method . '.php';
        if (file_exists($controller_path)) {
            include($controller_path);
        } else {
            apiDie('plugin config error: no such file ' . $controller_path, 500);
        }
    }
    apiDie('no.', 405);
}