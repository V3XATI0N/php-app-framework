<?php

if (!isset($_SESSION['id'])) {
    if (isset($_SESSION['userdata'])) {
        $_SESSION = $_SESSION['userdata'];
    } else {
        apiDie('did not find session data :(', 500);
    }
}

if (!isset($api_path[3]) or empty($api_path[3])) {
    apiDie([$_SESSION['id'] => getUserProfile($_SESSION['id'])], 200);
}
if (file_exists(__DIR__ . '/' . $api_path[3] . '/GET.php')) {
    include(__DIR__ . '/' . $api_path[3] . '/GET.php');
} else {
    apiDie('invalid module', 400);
}
