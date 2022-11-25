<?php

if (isset($api_path[5]) and !empty($api_path[5])) {
    $attr = $api_path[5];
    $target = $api_path[4];
    switch ($attr) {
        case "users":
            $do = moveUserToGroup(intval($api_data), intval($target));
            apiDie($do[2], $do[0]);
    }
} else {
    // $groupSchema = parse_file($oset['file_root'] . '/data/usermodel.json')['group'];
    $groupSchema = buildUserGroupModel()['group'];
    $groupAssignments = [];
    foreach ($api_data as $groupName => $groupConf) {
        $groupSave = [];
        if (empty($groupName)) { apiDie('invalid data.', 400); }
        foreach ($groupSchema['fields'] as $fieldConf) {
            switch ($fieldConf['type']) {
                case "bool":
                    if ($groupConf[$fieldConf['name']] == "on" or $groupConf[$fieldConf['name']] === true) {
                        $groupConf[$fieldConf['name']] = true;
                    } else {
                        $groupConf[$fieldConf['name']] = false;
                    }
                    break;
            }
            if (isset($fieldConf['required']) and $fieldConf['required'] === true) {
                if (empty($groupConf[$fieldConf['name']])) {
                    apiDie($fieldConf['name'] . ' is required.', 400);
                }
                $groupSave[$fieldConf['name']] = $groupConf[$fieldConf['name']];
            } elseif (isset($fieldConf['auto']) and $fieldConf['auto'] === true) {
                $fieldAutoVal = generateRandomString(10);
                if ($fieldConf['name'] == "id") {
                    $newGroupId = $fieldAutoVal;
                }
                $groupSave[$fieldConf['name']] = $fieldAutoVal;
            } elseif (!isset($groupConf[$fieldConf['name']]) or $groupConf[$fieldConf['name']] == null) {
                $groupSave[$fieldConf['name']] = null;
            } else {
                $groupSave[$fieldConf['name']] = $groupConf[$fieldConf['name']];
                if ($fieldConf['name'] == "users" and count($groupConf[$fieldConf['name']]) > 0) {
                    $groupAssignments[$newGroupId] = $groupConf[$fieldConf['name']];
                }
            }
        }
        $coreUsers['groups'][$groupName] = $groupSave;
    }
    if (saveUsersAndGroups($coreUsers)) {
        if (count($groupAssignments) > 0) {
            foreach ($groupAssignments as $group_id => $group_users) {
                apiDie($groupAssignments, 500);
                foreach ($group_users as $group_user) {
                    moveUserToGroup($group_user, $group_id);
                    usleep(300000);
                }
            }
        }
        apiDie(['ok', true], 201);
    } else {
        apiDie(['error', false], 500);
    }
    apiDie($coreUsers['groups']);
}