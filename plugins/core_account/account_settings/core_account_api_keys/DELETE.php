<?php

if (empty($api_path[5])) { apiDie('specify index', 400); }

$myData = getUserProfile($_SESSION['id']);
if (empty($myData['api_key'])) {
    apiDie('ok', 200);
}
$idx = $api_path[5];
$keys = $myData['api_key'];
$keys_save = [];
if (is_array($keys)) {
    foreach ($keys as $kk) {
        if ($kk['id'] != $idx) {
            array_push($keys_save, $kk);
        }
    }
}

$myData['api_key'] = $keys_save;
$updateProfile = saveUserProfile($_SESSION['id'], $myData);
if ($updateProfile === true) {
    apiDie('ok', 200);
} else {
    apiDie('error', 500);
}
