<?php
if (empty($api_path[4])) {
    apiDie('specify group ID', 400);
}
$group = $api_path[4];
$groupProfiles = $coreUsers['groups'];
if (isset($api_data['users'])) {
    if (!is_array($api_data['users'])) {
        $api_data['users'] = json_decode($api_data['users']);
    }
}
if (isset($api_data['users']) and count($api_data['users']) > 0) {
    $patchUsers = $api_data['users'];
}
foreach ($groupProfiles as $groupName => $groupConf) {
    if ($groupConf['id'] == $group) {
        $group_name = $groupName;
        $group_users = $groupConf['users'];
        foreach ($api_data as $api_key => $api_val) {
            switch ($api_key) {
                case "users":
                    foreach ($api_val as $user_id) {
                        moveUserToGroup($user_id, $groupConf['id']);
                    }
                default:
                    $groupConf[$api_key] = $api_val;
            }
        }
    }
    $groupProfiles[$groupName] = $groupConf;
}
$coreUsers['groups'] = $groupProfiles;
if (saveUsersAndGroups($coreUsers)) {
    apiDie($groupProfiles[$group_name], 200);
} else {
    apiDie('error storing data.', 500);
}