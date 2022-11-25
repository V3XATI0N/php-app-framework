<?php

startSession();

if (isset($oset['max_idle_time'])) {
    $idleTime = $oset['max_idle_time'];
} else {
    $idleTime = 900;
}

if (isset($api_path[3]) and $api_path[3] == "timeout") {
    if (isset($_SESSION['loginTime'])) {
        $timeout = time() - $_SESSION['loginTime'];
        if ($timeout > $idleTime) {
            session_destroy();
            apiDie(["status"=>"expired", "remaining"=>0], 410);
        }
        if ($_SESSION['id'] !== null) {
            $userActions = loadUserActions($_SESSION['id']);
            clearUserActions($_SESSION['id']);
        } else {
            $userActions = [];
        }
        apiDie(["status"=>"current","actions"=>$userActions,"remaining"=>$idleTime - $timeout], 200);
    } else {
        apiDie(["status"=>"none","remaining"=>$idleTime], 200);
    }
} else {
    if (file_exists(__DIR__ . '/' . $api_method . '.php')) {
        include(__DIR__ . '/' . $api_method . '.php');
    } elseif (file_exists(__DIR__ . '/' . $api_path[3] . '/' . $api_method . '.php')) {
        include(__DIR__ . '/' . $api_path[3] . '/' . $api_method . '.php');
    } else {
        apiDie('no such method', 405);
    }
}