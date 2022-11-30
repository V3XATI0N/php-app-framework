<?php

$myId = $_SESSION['id'];
$myData = getUserProfile($myId);
$keys = [];

if (!empty($myData['api_key'])) {
    $keyData = $myData['api_key'];
    if (is_array($keyData)) {
        $keyCount = count($keyData);
        foreach ($keyData as $kk) {
            if (is_array($kk)) {
                array_push($keys, [
                    "label" => $kk['label'],
                    "id" => $kk['id'],
                    "hash" => $kk['hash']
                ]);
            } else {
                $kl = explode('|', $kk)[0];
                array_push($keys, ["label" => $kl]);
            }
        }
        // apiDie(count($keyData), 200);
    } else {
        // apiDie(1, 200);
        $keyCount = 1;
        array_push($keys,["label", "Unlabeled API Key"]);
    }
} else {
    $keyCount = 0;
}

apiDie([$keyCount, $keys], 200);
