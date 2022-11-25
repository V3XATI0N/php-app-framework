<?php

if (isset($api_path[2]) and $api_path[2] != "") {
    $model_name = $api_path[2];
    if (isset($api_models[$model_name])) {
        $modelData = $api_models[$model_name];
        if (!isset($_SESSION['userdata'])) {
            $requestAllowed = false;
            if (isset($modelData['access'])) {
                if (isset($modelData['access'][$api_method])) {
                    if ($modelData['access'][$api_method] == "public:public") {
                        $requestAllowed = true;
                    }
                }
            }
            if ($requestAllowed === false) { apiDie('access denied.', 401); }
        }
        if (isset($url_query['override_owner']) and ($url_query['override_owner'] == "true" or $url_query['override_owner'] == 1) and accessMatch($_SESSION['id'], 'admin:user')) {
            $modelItems = getModelItems($model_name, true);
        } else {
            $modelItems = getModelItems($model_name);
        }
        
        if (isset($api_path[3]) and $api_path[3] != "") {
            foreach ($modelItems as $item) {
                if ($item['id'] == $api_path[3]) {
                    $itemName = $item['name'];
                    if (isset($api_path[4]) and $api_path[4] != "") {
                        foreach ($item as $key => $value) {
                            if ($key == $api_path[4]) {
                                foreach ($modelData['fields'] as $fdata) {
                                    if ($fdata['name'] == $key) {
                                        if (!empty($fdata['access']) and !empty($fdata['access']['GET'])) {
                                            if (!accessMatch($_SESSION['id'], $fdata['access']['GET'])) {
                                                apiDie('permission denied.', 403);
                                            }
                                        }
                                        $valType = $fdata['type'];
                                        switch ($valType) {
                                            case 'file':
                                                if (!empty($fdata['disposition'])) {
                                                    $contentDisposition = $fdata['disposition'];
                                                } else {
                                                    $contentDisposition = "inline";
                                                }
                                                $fileData = explode(':', $value);
                                                if (isset($fdata['inline_store']) and $fdata['inline_store'] === true) {
                                                    $fileContents = base64_decode($fileData[0]);
                                                    $fileMime = $fileData[1];
                                                    $fileLength = mb_strlen($fileContents);
                                                    header("content-type: {$fileData[1]}");
                                                    die(base64_decode($fileData[0]));
                                                } else {
                                                    $fileUri = $fileData[0];
                                                    $fileSave = $fileData[1];
                                                    $fileMime = $fileData[2];
                                                    $fileContents = file_get_contents($fileSave);
                                                    $fileLength = filesize($fileSave);
                                                }
                                                $fileExt = mime2ext($fileMime);
                                                $getFileName = $itemName . '.' . $fileExt;
                                                header("content-type: {$fileMime}");
                                                header("content-length: {$fileLength}");
                                                header("content-disposition: {$contentDisposition}; filename=\"${getFileName}\"");
                                                die($fileContents);
                                            default:
                                                apiDie($value, 200);
                                        }
                                    }
                                }
                                apiDie(["error" => "object {$model_name} has no such field '{$key}'"], 404);
                            }
                        }
                        apiDie(["error" => "object {$model_name} has no such field '{$key}'"], 404);
                    }
                    apiDie($item, 200);
                }
            }
            apiDie('no such item', 404);
        } else {
            $out = [];
            $matchOut = [];
            foreach ($modelItems as $item) {
                if (!empty($url_query['skipInvalid']) and ($url_query['skipInvalid'] == "1" or $url_query['skipInvalid'] == "true")) {
                    $now_time = new DateTime();
                    $now_dt = $now_time->format('Y-m-d H:i:s');
                    if (!empty($item['valid_from'])) {
                        $obj_dt = new DateTime($item['valid_from']);
                        if ($obj_dt > $now_dt) { continue; }
                    }
                    if (!empty($item['valid_until'])) {
                        $obj_dt = new DateTime($item['valid_until']);
                        if ($obj_dt < $now_dt) { continue; }
                    }
                }
                if (!empty($url_query['fieldMatch'])) {
                    $rejected = [];
                    $includeItem = false;
                    $matches = explode(';', $url_query['fieldMatch']);
                    foreach ($matches as $match) {
                        $includeItem = $includeItem;
                        $matchKey = explode(':', $match)[0];
                        $matchVal = urldecode(explode(':', $match)[1]);
                        $negateVal = false;
                        if (substr($matchVal, 0, 1) == '!') {
                            $negateVal = true;
                            $matchVal = substr($matchVal, 1);
                        }
                        if (!empty($item[$matchKey]) || (empty($item[$matchKey]) and $negateVal === true)) {
                            $valMatch = false;
                            if ($item[$matchKey] == $matchVal or (is_array($item[$matchKey]) and in_array_tox($matchVal, $item[$matchKey]))) {
                                if ($negateVal === false) {
                                    $valMatch = true;
                                }
                            } elseif ($negateVal === true) {
                                $valMatch = true;
                            }
                            if ($valMatch === true and !in_array($item['id'], $rejected)) {
                                if (!empty($url_query['details']) || !empty($url_query['fields'])) {
                                    if (!empty($url_query['fields'])) {
                                        $matchOutFields = [];
                                        $rfields = explode(',', $url_query['fields']);
                                        foreach ($rfields as $rf) {
                                            if (!empty($item[$rf])) {
                                                $matchOutFields[$rf] = $item[$rf];
                                            } else {
                                                $matchOutFields[$rf] = null;
                                            }
                                        }
                                        $includeItem = $matchOutFields;
                                    } else {
                                        $includeItem = $item;
                                    }
                                } else {
                                    $includeItem = $item['id'];
                                }
                            } else {
                                array_push($rejected, $item['id']);
                                $includeItem = false;
                            }
                        }
                    }
                    if ($includeItem === false) {
                        continue;
                    } else {
                        array_push($matchOut, $includeItem);
                    }
                } else {
                    if (isset($url_query['details']) and $url_query['details'] == "true") {
                        array_push($out, $item);
                    } elseif (!empty($url_query['fields'])) {
                        $rfields = explode(',', $url_query['fields']);
                        $rfieldVals = [];
                        foreach ($rfields as $rf) {
                            if (!empty($item[$rf])) {
                                $rfieldVals[$rf] = $item[$rf];
                            } else {
                                $rfieldVals[$rf] = null;
                            }
                        }
                        array_push($out, $rfieldVals);
                    } else {
                        array_push($out, $item['id']);
                    }
                }
            }
            if (!empty($url_query['fieldMatch'])) {
                $out = [];
                foreach ($matchOut as $ii) {
                    array_push($out, $ii);
                }
            }
            if (count($out) > 0) {
                apiDie($out, 200);
            } else {
                apiDie(null, 404);
            }
        }
    } else {
        apiDie('no such model ' . $model_name, 404);
    }
} else {
    $model_names = [];
    foreach ($api_models as $modelName => $modelConf) {
        array_push($model_names, $modelName);
    }
    apiDie($model_names, 200);
}