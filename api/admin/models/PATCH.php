<?php

if (empty($api_path[4])) {
    apiDie('specify model.', 400);
}

$modName = $api_path[4];
$modFile = $oset['file_root'] . '/utils/model_defs.json';
$modData = parse_file($modFile);
$modSave = [];
$modFind = false;

if (empty($modData['models'])) {
    $modList = [];
} else {
    $modList = $modData['models'];
}

foreach ($modList as $mod) {
    if ($mod['name'] == $modName) {
        $modFind = true;
        foreach ($api_data as $p => $v) {
            $mod[$p] = $v;
        }
        array_push($modSave, $mod);
    } else {
        array_push($modSave, $mod);
    }
}

if ($modFind === true) {
    $modData['models'] = $modSave;
    emit_file($modFile, $modData);
    apiDie('ok', 200);
} else {
    apiDie('no such model ' . $modName, 404);
}