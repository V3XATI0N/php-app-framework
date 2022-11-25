<?php
if (empty($api_path[4])) {
    apiDie('specify user', 400);
}
$user = $api_path[4];
$userProfile = getUserProfile($user);
$user_group = $userProfile['group'];
if ($userProfile === false) {
    apiDie('no such user ' . $user, 404);
}
foreach ($api_data as $api_key => $api_val) {
    switch ($api_key) {
        case "password":
            if ($api_val != "") {
                $api_val = password_hash($api_val, PASSWORD_DEFAULT);
                $userProfile[$api_key] = $api_val;
            }
            break;
        case "group":
            $target_group = $api_val;
            $groupMove = moveUserToGroup($user, $target_group);
            if ($groupMove[0] === false) {
                apiDie([$do, 'failed to store group data'], 500);
            }
            $user_group = $groupMove[2];
//            error_log(json_encode($userProfile, JSON_PRETTY_PRINT));
            break;
        default:
            $userProfile[$api_key] = $api_val;
    }
}
foreach ($coreUsers['users'] as $userName => $userConf) {
    if ($userConf['id'] == $user) {
        $userProfile['group'] = $user_group;
        $coreUsers['users'][$userName] = $userProfile;
    }
}
/*
if ($user == $_SESSION['id']) {
    apiDie($_SESSION);
}
*/
if (saveUsersAndGroups($coreUsers)) {
    apiDie($coreUsers['users'][$userName], 200);
} else {
    apiDie('failed to store data', 500);
}