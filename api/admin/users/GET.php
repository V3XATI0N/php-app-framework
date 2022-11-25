<?php

$userIndex = getUsersAndGroups();
$userModel = buildUserGroupModel()['user'];
$modelFields = $userModel['fields'];

$userList = $userIndex['users'];
$groupList = $userIndex['groups'];

$return = [];

foreach ($userList as $userName => $userData) {
    $userReturn = [];
    foreach ($userData as $key => $val) {
        foreach ($modelFields as $fc => $fieldConf) {
            if ($fieldConf['name'] == $key) {
                if (!isset($fieldConf['hash']) or $fieldConf['hash'] !== true) {
                    $userReturn[$key] = $val;
                }
            }
        }
    }
    if ((!isset($api_path[4]) or $api_path[4] == "") or ($userReturn['id'] == $api_path[4] or $userName == $api_path[4])) {
        $return[$userName] = $userReturn;
    }
}

if (count($return) == 0) {
    apiDie('not found', 404);
} else {
    ksort($return);
    if (!empty($url_query['out'])) {
        switch ($url_query['out']) {
            case 'csv':
                $returnCsv = "";
                if (!empty($url_query['groupnames'])) {
                    $groupnames = true;
                    $groups = $coreUsers['groups'];
                } else {
                    $groupnames = false;
                }
                foreach ($return as $iname => $idata) {
                    if (!empty($url_query['names'])) {
                        $istr = '"' . $iname . '",';
                    } else {
                        $istr = '';
                    }
                    if (!empty($url_query['fields'])) {
                        $fields = explode(',', $url_query['fields']);
                        foreach ($fields as $field) {
                            if (!empty($idata[$field])) {
                                $istr .= '"' . $idata[$field] . '",';
                            } else {
                                $istr .= '"",';
                            }
                        }
                    } else {
                        foreach ($idata as $idn => $idv) {
                            $istr .= '"' . $idv . '",';
                        }
                    }
                    if ($groupnames === true) {
                        $groupId = $idata['group'];
                        foreach ($groups as $gn => $gd) {
                            if ($gd['id'] == $groupId) {
                                $istr .= '"' . $gn . '",';
                            }
                        }
                    }
                    $returnCsv .= rtrim($istr, ',') . "\n";
                }
                apiDie($returnCsv, 200, 'text/csv');
                break;
            case 'json':
            default:
                apiDie($return, 200);
                break;
        }
    }
    apiDie($return, 200);
}