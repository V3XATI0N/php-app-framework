<?php
if (!isset($_SESSION['id'])) {
    startSession();
    if (isset($_SERVER['HTTP_X_TOXAPI_AUTH'])) {
        $tryKey = $_SERVER['HTTP_X_TOXAPI_AUTH'];
        $auth = verifyLogin($oset['api_tmp'], $tryKey);
        if ($auth['success'] === true) {
            $_SESSION = $auth;
            session_write_close();
        } else {
            session_destroy();
            apiDie($auth, 403);
        }
    } elseif (isset($_SERVER['PHP_AUTH_USER']) and isset($_SERVER['PHP_AUTH_PW'])) {
        $user = $_SERVER['PHP_AUTH_USER'];
        $pass = $_SERVER['PHP_AUTH_PW'];
        $auth = verifyLogin($user, $pass);
        if ($auth['success'] === true) {
            $_SESSION = $auth;
            session_write_close();
        } else {
            session_destroy();
            apiDie($auth, 403);
        }
    }
}
if (file_exists(__DIR__ . '/' . $api_method . '.php')) {
    include(__DIR__ . '/' . $api_method . '.php');
} else {
    apiDie('no.', 405);
}