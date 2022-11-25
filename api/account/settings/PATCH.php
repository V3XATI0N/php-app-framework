<?php

$userProfile = getUserProfile($_SESSION['id']);
if (isset($userProfile['auth_source'])) {
    apiDie('Your profile is controlled by the ' . $userProfile['auth_source'] . ' plugin, so it can only be updated using the tools on that page.', 400);
}

$data = getUsersAndGroups(false)['users'];
foreach ($api_data as $key => $val) {
    foreach ($userFields as $field) {
        if ($field['name'] == $key) {
            if (isset($field['hash']) and $field['hash'] === true) {
                if (empty($val)) {
                    continue;
                }
                if (!checkPasswordStrength($val)) {
                    apiDie("Use a stronger password.", 400);
                }
                $val = password_hash($val, PASSWORD_DEFAULT);
            }
            if ($key == "email") {
                foreach ($data as $uName => $uConf) {
                    if ($uConf['id'] == $userid) { continue; }
                    if ($uConf['email'] == $val) {
                        apiDie("That email address already belongs to some one else.", 400);
                    }
                }
            }
            $userConf[$key] = $val;
        }
    }
}

foreach ($data as $uName => $uConf) {
    if ($uConf['id'] == $userid) {
        $uConf = $userConf;
        if (!empty($api_data['__login_name__'] and accessMatch($usrid, 'admin:user'))) {
            $uName = $api_data['__login_name__'];
        }
    }
    $data[$uName] = $uConf;
}

$saveFile = getUsersAndGroups();
$saveFile['users'] = $data;

if (saveUsersAndGroups($saveFile)) {
    foreach ($userConf as $kk => $vv) {
        if (isset($_SESSION['userdata'][$kk])) {
            $_SESSION['userdata'][$kk] = $vv;
        }
    }
    session_write_close();
    apiDie('ok', 200);
} else {
    apiDie('error!', 500);
}
