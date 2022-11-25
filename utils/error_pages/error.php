<?php

$err_path = implode('/', $api_path);

if (empty($context)) {
    $errno = 000;
    $errtitle = "Unknown Error :(";
} else {
    $errno = $context;
    switch ($errno) {
        case '404':
            $errtitle = "PAGE NOT FOUND";
            $errmsg = "ATTEMPTED TO LOAD <b>" . $err_path . "</b> BUT COULDN'T ACTUALLY FIND IT.";
            break;
        case '403':
            $errtitle = "PERMISSION DENIED";
            $errmsg = "ATTEMPTED TO LOAD <b>" . $err_path . "</b> BUT YOU ARE NOT ALLOWED TO DO THAT (naughty, naughty!).";
            break;
        case '500':
            $errtitle = "PERMISSION DENIED";
            $errmsg = "ATTEMPTED TO LOAD <b>" . $err_path . "</b> BUT SOMETHING WENT TERRIBLY WRONG.";
            break;
        default:
            $errtitle = "USELESS ERROR MESSAGE";
            $errmsg = "BEATS ME LOL!";
    }
}

openTemplate();
?>
<div class="page_error">
    <img class="page_err_icon" src="/resource/page_error.png">
    <div class="page_err_title"><?= $errtitle ?></div>
    <img class="page_err_icon" src="/resource/page_error.png">
</div>
<div class="page_error_content">
<b>WHAT HAPPENED?</b><br><?= $errmsg ?><br>
<b>ERROR NUMBER?</b><br><?= $errno ?>
<?php
if (isset($_SESSION['id']) and accessMatch($_SESSION['id'], 'admin:user')) {
global $api_models;
?>
<br><br><b>SERVER INFO?</b>
<?php
echo json_encode([
    "SERVER VARS" => $_SERVER,
    "REQUEST PATH" => $api_path,
    "SYSTEM SETTINGS" => $oset,
    "PLUGINS" => $plugins,
    "OBJECTS" => $api_models
], JSON_PRETTY_PRINT);
    }
    ?>
</div>
<div class="page_err_subtitle">Click <b><a href="/">here</a></b> to (hopefully) return to a page that works.</div>
<?php closeTemplate(); ?>
