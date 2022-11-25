<?php

if (!is_array($api_data) or count($api_data) == 0) { apiDie('invalid data.', 400); }

$resp = [];
$list = $coreUsers['users'];
$groups = $coreUsers['groups'];

foreach ($api_data as $newItem) {
    $includeItem = true;
    foreach ($list as $iName => $iConf) {
        if ($iName == $newItem['username']) { $includeItem = false; }
        if ($iConf['id'] == $newItem['id']) { $includeItem = false; }
    }
    if ($includeItem === false) {
        $resp[$newItem['username']] = "skipped, already exists.";
        continue;
    }
    $groupId = $newItem['customer'];
    $list[strtolower($newItem['username'])] = [
        'id' => $newItem['id'],
        'rank' => 'admin',
        'group' => $groupId,
        'fullname' => $newItem['fullname'],
        'password' => $newItem['password'],
        'email' => $newItem['email']
    ];
    foreach ($groups as $groupName => $groupConf) {
        if ($groupConf['id'] == $groupId) {
            array_push($coreUsers['groups'][$groupName]['users'], $newItem['id']);
        }
    }
}
$coreUsers['users'] = $list;
if (saveUsersAndGroups($coreUsers)) {
    apiDie($resp, 200);
} else {
    apiDie('data error', 500);
}
