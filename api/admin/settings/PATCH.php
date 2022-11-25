<?php

$schemaData = parse_file(__DIR__ . '/../../../utils/settings_schema.json');
foreach ($plugins as $pluginName => $pluginConf) {
    if (file_exists($pluginConf['file_root'] . '/settings_schema.json')) {
        $pSchemaData = parse_file($pluginConf['file_root'] . '/settings_schema.json');
        if (isset($pSchemaData['groups'])) {
            foreach ($pSchemaData['groups'] as $pGroupName => $pGroupConf) {
                if (!isset($schemaData['groups'][$pGroupName])) {
                    $schemaData['groups'][$pGroupName] = $pGroupConf;
                }
            }
        }
        if (isset($pSchemaData['core'])) {
            foreach ($pSchemaData['core'] as $pCoreName => $pCoreConf) {
                $schemaData['core'][$pCoreName] = $pCoreConf;
            }
        }
    }
}
$schema = $schemaData['core'];
$schemaGroups = $schemaData['groups'];
$set = $oset;
$option = $api_path[4];

if (!isset($api_data['state']) or !isset($api_data['source'])) {
    apiDie('insufficient information', 400);
}
if (!isset($schema[$option])) {
    apiDie('no such option ' . $option, 404);
}

$opt_type = $schema[$option]['type'];
$state = $api_data['state'];
$source = $api_data['source'];

$changes = getOptionChange($option, $state);

$set_group = $schema[$option]['group'];
$set_access = $schemaGroups[$set_group]['access'];
$access_check = accessMatch($_SESSION['id'], $set_access);

if ($access_check !== true) {
    apiDie('insufficient permissions', 401);
}

if (!isValidType($state, $opt_type)) {
    apiDie('invalid data type', 400);
}

if (file_exists($oset['file_root'] . '/utils/settings_validate/' . $option . '/validate.php')) {
    $validate_change = include($oset['file_root'] . '/utils/settings_validate/' . $option . '/validate.php');
} else {
    $validate_change = [true];
}

if ($validate_change[0] === false) {
    apiDie(['proposed change failed validation.',$validate_change], 400);
}

if ($opt_type == "color") {
    if ($option = "theme_color") {
        $state = makeHex($state);
    }
}

$scriptContent = "";

foreach ($changes as $changeName => $changeSet) {
    $scriptContent .= $changeSet['script'];
    setOptionChange($changeName, $changeSet);
}

apiDie( [
    "result" => "ok",
    "changes" => $changes
], 200);

if (file_exists(__DIR__ . '/../../../utils/settings_js/' . $option . '/update.js')) {
    $scriptContent .= str_replace(["\r", "\n"], '', file_get_contents(__DIR__ . '/../../../utils/settings_js/' . $option . '/update.js'));
}

if ($source == "core") {
    $set_key_file = $oset['file_root'] . '/utils/settings.json';
    $set_data = parse_file($set_key_file);
    $set_data[$option] = $state;
    foreach ($requires as $reqName => $reqVal) {
        $set_data[$reqName] = $reqVal;
    }
    $file_save_data = $set_data;
} else {
    if (!isset($plugins[$source])) {
        apiDie('invalid target', 400);
    }
    $source_dir = $plugins[$source]['file_root'];
    $set_key_file = $source_dir . '/settings_override.json';
    if (file_exists($set_key_file)) {
        $setData = parse_file($set_key_file);
    } else {
        $setData = [];
    }
    foreach ($requires as $reqName => $reqVal) {
        $setData[$reqName] = $reqVal;
    }
    $file_save_data = $set_data;
    if (file_exists($source_dir . '/settings_js/' . $option . '/update.js')) {
        $scriptContent .= str_replace(["\r", "\n"], '', file_get_contents($source_dir . '/settings_js/' . $option . '/update.js'));
    }
}

if (emit_file($set_key_file, $file_save_data)) {
    apiDie(["result"=>"ok", "script"=>$scriptContent, "requires"=>$requires, "change" => $changes], 200);
} else {
    apiDie("error", 500);
}
