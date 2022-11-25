<?php

if (isset($_SESSION['userdata']['auth_source'])) {
    $sessionUserData = getUserProfile($_SESSION['id']);
    $sessionUserData['isPluginUser'] = true;
    apiDie([
        "userdata" => $sessionUserData
    ], 200);
} else {
    apiDie($_SESSION, 200);
}