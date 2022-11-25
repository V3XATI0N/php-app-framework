<?php

$userIndex = getUsersAndGroups();
$userList = $userIndex['groups'];
$return = [];
$userModel = buildUserGroupModel()['group'];

if (empty($api_path[4])) { apiDie('specify group.', 400); }
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
    $userIndex['groups'] = $return;
    if (saveUsersAndGroups($userIndex)) {
        apiDie(['ok'], 200);
    } else {
        apiDie(['error storing data'], 500);
    }
} else {
    apiDie("no such group.", 404);
}
