<?php

if (empty($api_data['label'])) {
    apiDie(['specify a label.'], 400);
}

$keys = [];
$ud = getUserProfile($_SESSION['id']);
$label = $api_data['label'];

if (!empty($ud['api_key'])) {
    if (is_array($ud['api_key'])) {
        foreach ($ud['api_key'] as $kk) {
            array_push($keys, $kk);
        }
    } else {
        array_push($keys, $ud['api_key']);
    }
}

$new_key = generateRandomString(128);
$key_hash = password_hash($new_key, PASSWORD_BCRYPT, ["cost"=>10]);
$key_id = generateRandomString(8);

array_push($keys, [
    "label" => $label,
    "id" => $key_id,
    "hash" => $key_hash
]);

$prof = getUserProfile($_SESSION['id']);
if ($prof === false) { apiDie('error', 500); }
$prof['api_key'] = $keys;

$updateProfile = saveUserProfile($_SESSION['id'], $prof);

if ($updateProfile === true) {
    apiDie([
        'status' => 'success',
        'label' => $label,
        'key' => $new_key
    ], 200);
} else {
    apiDie([
        'status' => 'failure',
        'error' => 'failed to store API key data.'
    ], 500);
}