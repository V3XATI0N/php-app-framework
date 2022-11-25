<?php

$userIndex = getUsersAndGroups();
$userList = $userIndex['users'];
$return = [];
$userModel = buildUserGroupModel()['user'];

if (empty($api_path[4])) { apiDie('specify user.', 400); }
$target = $api_path[4];
$found = false;
foreach ($userList as $userName => $userConf) {
    if ($userConf['id'] == $target) {
        $found = true;
    } else {
        $return[$userName] = $userConf;
    }
}

if ($found === true) {
    $userIndex['users'] = $return;
    if (saveUsersAndGroups($userIndex)) {
        apiDie('ok', 200);
    } else {
        apiDie('error storing data', 500);
    }
} else {
    apiDie("no such user.", 404);
}
