<?php

if (!isset($api_path[2]) or empty($api_path[2])) {
    apiDie('specify model.', 400);
}

$modName = $api_path[2];

if (!isset($api_models[$modName])) {
    apiDie('no such model', 404);
}

$modData = $api_models[$modName];


$modelFields = $api_models[$modName]['fields'];
$itemSave = [];
$modelStore = $api_models[$modName]['store'];
if (file_exists($modelStore)) {
    $modelData = parse_file($modelStore);
} else {
    $modelData = [];
}

$content_type = explode(';', $_SERVER['CONTENT_TYPE'])[0];
if ($content_type == "multipart/form-data") {
    $api_data = $_POST;
    $api_files = $_FILES;
    if (isset($api_data['__method__']) and $api_data['__method__'] == 'PATCH') {
        include(__DIR__ . '/PATCH.php');
        die();
    }
}

foreach ($modelFields as $field) {
    if (isset($api_files) and isset($api_files[$field['name']])) {
        if (!empty($field['inline_store']) and $field['inline_store'] === true) {
            $inlineStore = true;
        } else {
            $inlineStore = false;
        }
        $data = $api_files[$field['name']];
        $tmp = $data['tmp_name'];
        if ($inlineStore === true) {
            $fileContents = base64_encode(file_get_contents($tmp));
            $fileMime = $data['type'];
            $itemSave[$field['name']] = $fileContents . ':' . $fileMime;
        } else {
            $saveName = generateRandomString(10) . '.' . str_replace(' ', '', pathinfo($data['name'], PATHINFO_EXTENSION));
            if ($modData['source'] == "core") {
                $saveDir = $oset['file_root'] . '/' . $field['store'] . '/';
                $savePath = $saveDir . $saveName;
                move_uploaded_file($tmp, $savePath);
                $filePublishPath = "/resource" . $field['store'] . $saveName;
                $fileSavePath = $oset['file_root'] . '/resource' . $field['store'] . $saveName;
            } else {
                $saveDir = $oset['file_root'] . '/plugins/' . $modData['source'] . '/resource' . $field['store'] . '/';
                $savePath = $saveDir . $saveName;
                move_uploaded_file($tmp, $savePath);
                $filePublishPath = "/resource/plugins/" . $modData['source'] . '/assets' . $field['store'] . '/' . $saveName;
                $fileSavePath = $oset['file_root'] . '/plugins/' . $modData['source'] . '/resource' . $field['store'] . '/' . $saveName;
            }
            $itemSave[$field['name']] = $filePublishPath . ':' . $fileSavePath . ':' . $data['type'];
        }
    } else {
        if (isset($field['default'])) {
            if (explode(":", $field['default'])[0] == "session") {
                $defaultValue = $_SESSION['userdata'][explode(":", $field['default'])[2]];
            } else {
                $defaultValue = $field['default'];
            }
            if (isset($api_data[$field['name']]) and (!isset($field['default_override_access']) or accessMatch($_SESSION['id'], $field['default_override_access']))) {
                $defaultValue = $api_data[$field['name']];
            } else {
                $api_data[$field['name']] = $defaultValue;
            }
        }
        if (isset($field['required']) and $field['required'] === true) {
            if (!isset($api_data[$field['name']]) and (empty($field['auto']) or $field['auto'] !== true)) {
                apiDie($field['name'] . ' is required.', 400);
            }
        }
        if (isset($field['unique']) and $field['unique'] === true) {
            foreach ($modelData as $item) {
                if ($item[$field['name']] == $api_data[$field['name']]) {
                    apiDie($field['name'] . ' must be unique.', 409);
                }
            }
        }
        if (isset($field['file_from_url']) and $field['file_from_url'] === true and !empty($api_data[$field['name']])) {
            $url = $api_data[$field['name']];
            if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
                $file_field = $field['file_field'];
                $file_name = generateRandomString(16);
                $file_data = file_get_contents($url);
                if ($file_data !== false) {
                    if (file_put_contents($file_name, $file_data)) {
                        $file_mime = mime_content_type($file_name);
                        foreach ($modelFields as $sfield) {
                            if ($sfield['name'] == $file_field) {
                                if ($sfield['inline_store'] === true) {
                                    $file_save_data = base64_encode($file_data);
                                    $itemSave[$sfield['name']] = $file_save_data . ":" . $file_mime;
                                } else {
                                    $file_ext = mime2ext($file_mime);
                                    $file_save_name = $file_name . '.' . $file_ext;
                                    if ($modData['source'] == "core") {
                                        // this will never happen and it's my problem if it does so i'm being lazy and not implementing it yet.
                                    } else {
                                        $file_save_dir = $oset['file_root'] . '/plugins/' . $modData['source'] . '/resource' . $sfield['store'] . '/';
                                        $file_save_path = $file_save_dir . $file_save_name;
                                        $file_save_exec = file_put_contents($file_save_path, $file_data);
                                        $file_pub_path = '/resource/plugins/' . $modData['source'] . '/assets' . $sfield['store'] . '/' . $file_save_name;
                                        $itemSave[$sfield['name']] = $file_pub_path . ":" . $file_save_path . ":" . $file_mime;
                                    }
                                }
                            }
                        }
                        unlink($file_name);
                    } else {
                        apiDie('failed to store ' . $field['name'] . ' as a file', 400);
                    }
                } else {
                    apiDie('failed to fetch ' . $field['name'] . ' data', 400);
                }
            } else {
                apiDie($field['name'] . ' is not a valid URL.', 400);
            }
        }
        if (isset($api_data[$field['name']])) {
            switch ($field['type']) {
                case 'multi':
                    $itemSave[$field['name']] = json_decode($api_data[$field['name']], true);
                    break;
                default:
                    $itemSave[$field['name']] = $api_data[$field['name']];
            }
        }
    }
}

if (count($itemSave) > 0) {
    $itemSave['id'] = generateRandomString();
    $saveItem = addModelItem($modName, $itemSave);
    apiDie($saveItem, $saveItem[1]);
    /*
    array_push($modelData, $itemSave);
    if (emit_file($modelStore, $modelData)) {
        apiDie($modelData, 200);
    } else {
        apiDie('error saving data', 500);
    }
    */
}
