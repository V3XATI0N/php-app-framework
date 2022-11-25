<?php

if (!isset($_SESSION) or !isset($_SESSION['userdata']['userRank'])) {
    apiDie('no access here', 403);
}

if (!isset($api_path[3]) or empty($api_path[3])) {
    apiDie('specify module', 400);
}

if (empty($api_data) or !is_array($api_data)) {
    apiDie('no such luck', 400);
}

$userFields = parse_file($oset['file_root'] . '/data/usermodel.json')['user']['fields'];

$userdata = $_SESSION['userdata'];
foreach ($api_data as $key => $val) {
    if (file_exists(__DIR__ . '/' . $api_path[3] . '/PATCH.php')) {
        $userid = $_SESSION['id'];
        $data = getUsersAndGroups(true)['users'];
        foreach ($data as $userName => $userConf) {
            if ($userConf['id'] == $userid) {
                include(__DIR__ . '/' . $api_path[3] . '/PATCH.php');
            }
        }
        apiDie('that is not your account. naughty naughty.', 404);
    } else {
        apiDie("invalid module", 400);
    }
}