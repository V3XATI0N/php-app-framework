<?php

$requiredFieldParams = [
    'name',
    'type',
    'display'
];
$defaultFieldParams = [
    'required' => true
];
$requiredParams = [
    'name',
    'humanname',
    'fields'
];
$defaultParams = [
    'access' => [
        'OPTIONS' => 'user:user',
        'GET' => 'user:user',
        'POST' => 'moderator:user',
        'PATCH' => 'moderator:admin',
        'DELETE' => 'admin:user'
    ],
    'admin_manage' => true,
    'hide_on_admin_page' => false,
    'assign_by_group' => false
];
$saveMods = parse_file($oset['file_root'] . '/utils/model_defs.json');

if (empty($api_path[4])) {

    if (!empty($api_data['append'])) {
        // do this eventually i guess
    }
    if (!empty($api_data['models'])) {
        if (empty($saveMods['models'])) {
            $saveMods['models'] = [];
        }
        foreach ($api_data['models'] as $mod) {
            $modName = $mod['name'];
            foreach ($api_models as $xmodname => $xmodconf) {
                if ($xmodname == $modName) {
                    apiDie('There is already a model called ' . $modName, 409);
                }
            }
            foreach ($saveMods['models'] as $smod) {
                if ($smod['name'] == $modName) {
                    apiDie('A model called ' . $modName . ' has already been defined.', 409);
                }
            }
            foreach ($requiredParams as $param) {
                if (!isset($mod[$param]) or ($mod[$param] == "" and !is_bool($mod[$param]))) {
                    apiDie($modName . ' requres the ' . $param . ' parameter.', 400);
                }
            }
            foreach ($defaultParams as $param => $default) {
                if (!isset($mod[$param]) or ($mod[$param] == "" and !is_bool($mod[$param]))) {
                    $mod[$param] = $default;
                }
            }
            if (empty($mod['store'])) {
                $mod['store'] = '/data/models/' . $modName . '.json';
            }
            $mod['source'] = 'core';
            foreach ($mod['fields'] as $mf) {
                foreach ($requiredFieldParams as $rp) {
                    if (empty($mf[$rp])) {
                        apiDie('Missing required field parameter ' . $rp . ' in model ' . $modName, 400);
                    }
                }
                foreach ($defaultFieldParams as $dp => $dd) {
                    if (empty($mf[$dp])) {
                        $mf[$dp] = $dd;
                    }
                }
                switch ($mf['type']) {
                    case "multi":
                    case "select":
                        if (empty($mf['options']) and empty($mf['option_src'])) {
                            apiDie('Missing options or option_src in field ' . $mf['name'] . ' in model ' . $modName, 400);
                        }
                        break;
                }
            }
            array_push($saveMods['models'], $mod);
        }
        emit_file($oset['file_root'] . '/utils/model_defs.json', $saveMods);
        apiDie($saveMods, 201);
    }

} else {
    if (empty($api_models[$api_path[4]])) {
        apiDie('no such model ' . $api_path[4], 404);
    }
    if (empty($api_path[5])) {
        apiDie('specify an attribute to add something to.', 400);
    }
    $schema = $api_models[$api_path[4]];
    switch ($api_path[5]) {
        case 'fields':
            $modFields = $schema['fields'];
            foreach ($api_data as $field) {
                foreach ($modFields as $mf) {
                    if ($mf['name'] == $field['name']) {
                        apiDie('There is already a field called ' . $field['name'], 400);
                    }
                }
                foreach ($requiredFieldParams as $rp) {
                    if (empty($field[$rp])) {
                        apiDie('You must specify the ' . $rp . ' field parameter.', 400);
                    }
                }
                foreach ($defaultFieldParams as $dp => $dd) {
                    if (empty($field[$dp])) {
                        $field[$dp] = $dd;
                    }
                }
                array_push($modFields, $field);
            }
            $schema['fields'] = $modFields;
            break;
        case 'access':
            $modAccess = $schema['access'];
            foreach ($api_data as $m => $a) {
                if (count(explode(':', $a) != 2)) {
                    apiDie('Invalid access parameter format.', 400);
                }
                foreach ($modAccess as $am => $aa) {
                    if ($am == $m) {
                        apiDie('The ' . $m . ' method is already configured.', 409);
                    }
                }
                $modAccess[$m] = $a;
            }
            $schema['access'] = $modAccess;
            break;
        default:
            apiDie('that is not a valid attribute.', 400);
    }
    $modsOut = [$schema];
    foreach ($saveMods['models'] as $saveMod) {
        if ($saveMod['name'] != $schema['name']) {
            array_push($modsOut, $saveMod);
        }
    }
    $dataSave = ["models" => $modsOut];
    emit_file($oset['file_root'] . '/utils/model_defs.json', $dataSave) or apiDie('error', 500);
    apiDie($schema, 201);
}
apiDie('no instructions given', 400);