<?php

$userIndex = getUsersAndGroups();
$groupList = $userIndex['groups'];
$return = [];

foreach ($groupList as $groupName => $groupData) {
    $groupReturn = [];
    foreach ($groupData as $key => $val) {
        $groupReturn[$key] = $val;
    }
    if (!empty($api_path[4]) and $groupData['id'] == $api_path[4]) {
        apiDie([$groupName => $groupReturn], 200);
    }
    $return[$groupName] = $groupReturn;
}

if (!empty($api_path[4])) {
    apiDie('not found', 404);
} else {
    ksort($return);
    if (!empty($url_query['out'])) {
        switch ($url_query['out']) {
            case 'csv':
                $returnCsv = "";
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
                                if (is_array($idata[$field])) {
                                    //$istr .= '"[' . implode(',', $idata[$field]) . ']",';
                                    $istr .= json_encode($idata[$field]) . ',';
                                } else {
                                    $istr .= '"' . $idata[$field] . '",';
                                }
                            } else {
                                $istr .= '"",';
                            }
                        }
                    } else {
                        foreach ($idata as $idn => $idv) {
                            if (is_array($idv)) {
                                $istr .= json_encode($idv) . ',';
                            } else {
                                $istr .= '"' . $idv . '",';
                            }
                        }
                    }
                    $returnCsv .= rtrim($istr, ',') . "\n";
                }
                apiDie($returnCsv, 200, 'text/csv');
                break;
            case 'json':
            default:
                break;
        }
    }
}

if (isset($url_query['keyvaluelist'])) {
    if ($url_query['keyvaluelist'] == "true") {
        $xreturn = [];
        foreach ($return as $gn => $gd) {
            $xreturn[$gn] = $gd['id'];
        }
        apiDie($xreturn, 200);
    }
}

apiDie($return, 200);