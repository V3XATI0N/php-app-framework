<?php

$modName = $api_path[2];
if (!isset($api_models[$modName])) {
    apiDie('no such model', 404);
}
$modConf = $api_models[$modName];

/* this needs to move higher in the logic tree but whatevs */
if (!isset($modConf['access']) or !is_array($modConf['access'])) {
    $modConf['access'] = [
        "GET" => "user:user",
        "POST" => "admin:admin",
        "PATCH" => "admin:admin",
        "DELETE" => "admin:admin"
    ];
}

$itemId = $api_path[3];
$modItems = getModelItems($modName, true);
$fields = [];
foreach ($modConf['fields'] as $fieldConf) {
    $fields[$fieldConf['name']] = $fieldConf;
}
foreach ($modItems as $item) {
    if ($item['id'] == $itemId) {
        if (isset($modConf['assign_by_group']) and $modConf['assign_by_group'] === true) {
            if ($item['owner_group'] != $_SESSION['userdata']['group']) {
                if (!accessMatch($_SESSION['id'], 'admin:moderator')) {
                    apiDie('no such item', 404);
                }
            }
        }
        foreach ($api_data as $keyName => $keyVal) {
            if (isset($fields[$keyName]) and !empty($keyVal) and $keyVal != "undefined") {
                if (isset($fields[$keyName]['auto']) and $fields[$keyName]['auto'] === true) {
                    continue;
                }
                if ($keyName == "owner_group") {
                    continue;
                }
                switch ($fields[$keyName]['type']) {
                    case 'multi':
                        $item[$keyName] = json_decode($keyVal, true);
                        break;
                    case 'bool':
                        switch (strtolower($keyVal)) {
                            case 'true':
                            case '1':
                            case 'yes':
                                $keyVal = true;
                                break;
                            case 'false':
                            case '0':
                            case 'no':
                                $keyVal = false;
                        }
                        $item[$keyName] = $keyVal;
                        break;
                    default:
                        $item[$keyName] = $keyVal;
                }
            }
        }
        if (isset($api_files) and count($api_files) > 0) {
            foreach ($api_files as $uFileName => $uFileData) {
                $uploadMime = $uFileData['type'];
                foreach ($fields as $fieldName => $fieldConf) {
                    if ($fieldName == $uFileName) {
                        $uploadFile = $uFileData['tmp_name'];
                        $uploadMime = $uFileData['type'];
                        $fileContents = base64_encode(file_get_contents($uploadFile));
                        if (!empty($fieldConf['inline_store']) and $fieldConf['inline_store'] === true) {
                            $item[$uFileName] = $fileContents . ':' . $uploadMime;
                        } else {
                            $saveFileData = explode(':', $item[$uFileName]);
                            $filePublishPath = $saveFileData[0];
                            $fileSavePath = $saveFileData[1];
                            $fileSaveMime = $saveFileData[2];
                            move_uploaded_file($uploadFile, $fileSavePath);
                            $item[$uFileName] = $filePublishPath . ':' . $fileSavePath . ':' . $fileSaveMime;
                        }
                    }
                }
            }
        }
        $exec = patchModelItem($modName, $itemId, $item);
        apiDie($exec[2], $exec[1]);
    }
}
apiDie('item not found', 404);
apiDie([$modConf, $modItems], 200);