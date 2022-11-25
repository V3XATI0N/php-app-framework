<?php

if (empty($api_path[4])) {
    apiDie('specify a valid model name', 400);
}

$modName = $api_path[4];

$modData = parse_file($oset['file_root'] . '/utils/model_defs.json');
if (empty($modData['models'])) {
    $modList = [];
} else {
    $modList = $modData['models'];
}

$mod_found = false;
$modSave = [];

foreach ($modList as $m) {
    if ($m['name'] != $modName) {
        array_push($modSave, $m);
    } else {
        $mod_found = true;
    }
}

if ($mod_found === true) {
    $modData['models'] = $modSave;
    emit_file($oset['file_root'] . '/utils/model_defs.json', $modData) or apiDie('data error', 500);
    apiDie('ok', 200);
} else {
    apiDie('no such model ' . $modName, 404);
}