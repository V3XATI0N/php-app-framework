<?php 
if (!isset($_SESSION['userdata'])) { returnToHome(); }
if (isset($api_path[2]) and !empty($api_path[2])) {
    if ($api_path[2] == "plugin") {
        $modName = $api_path[4];
    } else {
        $modName = $api_path[2];
    }
} else {
    $modName = "settings";
}
if (isset($_SESSION['auth_source'])) {
    $pluginUser = true;
} else {
    $pluginUser = false;
}
$pluginMod = false;
openTemplate("account");
?>
<div class="pageHeader">Account Settings</div>
<div class="gridTop">
    <div class="gridLeft nav narrow-padding">
        <?php
        foreach (["settings" => "Profile", "theme" => "Theme Settings"] as $mod => $modConf) {
            if ($mod == "settings" and isset($oset['hide_profile_settings']) and $oset['hide_profile_settings'] === true) {
                continue;
            }
            $hrefClass = "user navItem";
            if ($mod == $modName) {
                $hrefClass .= " activePage";
            }
            if (isset($oset['user_themes']) and $oset['user_themes'] === false and $mod == "theme") { continue; }
            ?>
            <a class="<?= $hrefClass ?>" href="/account/<?= $mod ?>">
                <img class="navItemIcon" src="/resource/account_<?= $mod ?>.png">
                <?= $modConf ?>
            </a>
            <?php
        }
        foreach ($plugins as $pluginName => $pluginConf) {
            if (isset($pluginConf['account_settings_modules'])) {
                foreach ($pluginConf['account_settings_modules'] as $mod) {
                    if (!isset($mod['access']) or !accessMatch($_SESSION['id'], $mod['access'])) { continue; }
                    if (!empty($mod['usertypes'])) {
                        if ($_SESSION['userdata']['type'] == "plugin") {
                            if (!in_array("plugin", $mod['usertypes'])) {
                                continue;
                            }
                        } else {
                            if (!in_array("core", $mod['usertypes'])) {
                                continue;
                            }
                        }
                    }
                    $hrefClass = "user navItem";
                    if ($modName == $mod['href']) {
                        $pluginMod = $pluginName;
                        $hrefClass .= " activePage";
                    }
                    ?>
                    <a class="<?= $hrefClass ?>" href="/account/plugin/<?= $pluginName ?>/<?= $mod['href'] ?>">
                        <img class="navItemIcon" src="/resource/plugins/<?= $pluginName ?>/assets/<?= $mod['icon'] ?>">
                        <?= $mod['name'] ?>
                    </a>
                    <?php
                }
            }
        }
        ?>
    </div>
    <div class="gridRight content">
        <div class="adminContentCatch">
            <div id="userSettingsContent">
            </div>
            <div id="userSettingsSubmitTools" class="adminNewObjectToolbar align-right no-flex-grow">
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
$(document).ready(function () {
    loadUserSettingsModule('<?= $modName ?>'<?php if ($pluginMod !== false) { echo ", '" . $pluginMod . "'"; } ?>);
});
</script>
<?php closeTemplate("account"); ?>
