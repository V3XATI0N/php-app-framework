<!DOCTYPE html>
<?php

if ($context == "login" or (!empty($oset['custom_login_page']) and !empty($api_path) and $oset['custom_login_page'] == $api_path[1])) {
    $oset['vertical_layout'] = false;
}

?>
<html style="display: transparent; background: <?= greyBright($oset['theme_color']) ?>; transition-property: all; transition-duration: 150ms; transition: ease-in-out 150ms;">
    <?php insertHtmlHead($context); ?>
    <body>
        <?php if (!isset($skip_template) or $skip_template === false) { ?>
        <?php insertPageHeader($context); ?>
        <div id="core_template_base" <?php if(isset($oset['vertical_layout']) and $oset['vertical_layout'] === true){ ?>class="vertical_layout"<?php } ?>>
        <?php } ?>