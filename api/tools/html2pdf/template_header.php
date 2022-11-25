<?php
if (isset($api_data['options']['core_css']) and $api_data['options']['core_css'] === true) {
    $oset['dark_theme'] = false;
    ?>
    <link rel="stylesheet" type="text/css" href="<?= $oset['app_url'] ?>/resource/core.css?darktheme=false" id="core_stylesheet">
    <?php
    foreach ($plugins as $pluginName => $pluginConf) {
        if (isset($pluginConf['styles'])) {
            foreach ($pluginConf['styles'] as $script) {
                $script_file = basename($script['source']);
                if (!isset($script['access']) or accessMatch($_SESSION['id'], $script['access'])) {
                    ?>
                    <link rel="stylesheet" type="text/css" href="<?= $oset['app_url'] ?>/resource/plugins/<?= $pluginName ?>/styles/<?= $script_file ?>">
                    <?php
                }
            }
        }
    }
}
if (!isset($api_data['options']['hide_header']) or $api_data['options']['hide_header'] === false) {
    ?>
    <div class="exportHeader">
        <img src="<?= $oset['app_url'] ?><?= $oset['app_icon'] ?>" class="exportHeaderIcon">
        <span class="exportHeaderTitle"><?= $oset['app_name'] ?></span>
    </div>
    <?php
}
?>