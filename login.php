<?php

startSession();

if (isset($_SESSION['userdata'])) {
    returnToHome();
}

if (!empty($oset['custom_login_page'])) {
    $login_page = $oset['custom_login_page'];
} else {
    $login_page = "login";
}

if (isset($_POST) and isset($_POST['username'])) {

    $verifyLogin = verifyLogin($_POST['username'], $_POST['password']);

    if (is_array($verifyLogin)) {
        if ($verifyLogin['success'] === true) {
            $_SESSION = $verifyLogin;
            $_SESSION['username'] = $_POST['username'];
            $_SESSION['loginTime'] = time();
            if (!empty($verifyLogin['validate_mfa']) and $verifyLogin['validate_mfa'] === true) {
                $_SESSION['MFA_IN_PROGRESS'] = true;
            }
            session_write_close();
        } else {
            incrementBadLoginCount($_POST['username'], $_SERVER['REMOTE_ADDR']);
            header("Location: /" . $login_page . "?error=" . urlencode($verifyLogin['error']));
            die();
        }
    } else {
        header("Location: /" . $login_page . "?error=Authentication+Error");
        die();
    }
    returnToHome();

}

openTemplate("login");
?>
        <div id="login_extensions">
            <?php
            foreach ($plugins as $pluginName => $pluginConf) {
                $file_root = $pluginConf['file_root'];
                if (isset($pluginConf['login_extensions'])) {
                    logError($pluginConf['login_extensions']);
                    foreach ($pluginConf['login_extensions'] as $ext) {
                        if (file_exists($file_root . '/' . $ext)) {
                            include($file_root . '/' . $ext);
                        }
                    }
                }
            }
            ?>
        </div>
        <div id="login_top">
            <br>
            <div id="login_status">
                <?php
                if (isset($url_query['error'])) {
                    echo urldecode($url_query['error']);
                } else {
                    echo "&nbsp;";
                }
                ?>
            </div>
            <br>
            <form id="login_form" action="/<?= $login_page ?>" method="POST">
                <input type="text" placeholder="Username" name="username" autocapitalize="none"><br>
                <input type="password" placeholder="Password" name="password" autocapitalize="none"><br>
                <button type="submit">Log in</button>
            </form>
        </div>
<?php
closeTemplate("login");
