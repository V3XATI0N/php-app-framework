<?php

// $schema = parse_file($oset['file_root'] . '/data/usermodel.json')['user'];
$schema = buildUserGroupModel()['user'];
foreach ($api_data as $userName => $userConf) {
    $userName = str_replace(" ", '', strtolower($userName));
    $userStore = [];
    foreach ($coreUsers['users'] as $coreUserName => $coreUserConf) {
        if ($coreUserName == $userName) {
            apiDie('user ' . $userName . ' already exists.', 400);
        }
    }
    foreach ($schema['fields'] as $fieldConf) {
        $fieldName = $fieldConf['name'];
        if (isset($fieldConf['required']) and $fieldConf['required'] === true and (!isset($userConf[$fieldName]) or $userConf[$fieldName] == "")) {
            if ($fieldConf['auto'] !== true) {
                apiDie($fieldName . ' is required', 400);
            }
        }
        if (isset($fieldConf['auto']) and $fieldConf['auto'] === true) {
            $fieldValue = generateRandomString(10);
        } elseif (isset($fieldConf['hash']) and $fieldConf['hash'] === true) {
            if (isset($userConf[$fieldName]) and $userConf[$fieldName] != "") {
                $fieldValue = password_hash($userConf[$fieldName], PASSWORD_DEFAULT);
            }
        } elseif (isset($fieldConf['unique']) and $fieldConf['unique'] === true) {
            foreach ($coreUsers['users'] as $coreUserName => $coreUserConf) {
                if ($coreUserConf[$fieldName] == $userConf[$fieldName]) {
                    apiDie('A user already exists with that ' . $fieldName . ' value.', 400);
                }
            }
            $fieldValue = $userConf[$fieldName];
        } else {
            $fieldValue = $userConf[$fieldName];
        }
        $userStore[$fieldName] = $fieldValue;
    }
    $coreUsers['users'][$userName] = $userStore;
}
if (saveUsersAndGroups($coreUsers)) {
    apiDie($coreUsers['users'], 201);
} else {
    apiDie('error storing data', 500);
}
