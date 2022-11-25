<?php 
if (!isset($_SESSION['userdata']) or !accessMatch($_SESSION['id'], "moderator:user")) { returnToHome(); }
openTemplate("account");
$pluginTarget = "";
$pluginTrigger = "";
$pluginTriggerTarget = "";
$modules = getModelItems('admin_modules');
$validMods = [];
foreach ($modules as $mod) {
    array_push($validMods, $mod['module']);
}
if (isset($api_path[2]) and !empty($api_path[2]) and in_array($api_path[2], $validMods)) {
    $modLoad = $api_path[2];
} elseif ($api_path[2] == "plugin") {
    $modLoad = $api_path[2] . '/' . $api_path[3] . '/' . $api_path[4];
    $pluginTarget = "true";
    $pluginTrigger = $api_path[3];
    $pluginTriggerTarget = $api_path[4];
} else {
    $modLoad = "settings";
}
?>
<div class="gridTop">
    <div class="gridLeft nav narrow-padding">
        <div class="pageHeader">Admin</div>
        <?php
        $modCount = 0;
        foreach ($modules as $modConf) {
            if (!isset($modConf['access']) or !accessMatch($_SESSION['id'], $modConf['access'])) { continue; }
            $modCount++;
            if ($modConf['module'] == $modLoad) {
                $addClass = " activePage";
            } else {
                $addClass = "";
            }
            if (explode('/', $modConf['module'])[0] == "plugin") {
                $addClass .= " pluginAdminItem";
                if (isset($modConf['icon'])) {
                    $itemIconSrc = $modConf['icon'];
                } else {
                    $itemIconSrc = "/resource/tools_admin_navitem.png";
                }
                $appendAttrs = ' plugin_name="' . explode('/', $modConf['module'])[1] . '" mod_target="' . explode('/', $modConf['module'])[2] . '" ';
            } else {
                $itemIconSrc = "/resource/" . $modConf['module'] . "_admin_navitem.png";
                $appendAttrs = ' mod_target="' . $modConf['module'] . '" ';
            }
            ?>
            <a
                href="/admin/<?= $modConf['module'] ?>"
                class="admin navItem<?= $addClass ?>"
                access="<?= $modConf['access'] ?>"
                <?= $appendAttrs ?>
                >
                    <img class="navItemIcon" src="<?= $itemIconSrc ?>">
                    <?= $modConf['name'] ?>
            </a>
            <?php
        }
        if ($modCount == 0) { returnToHome(); }
        ?>
    </div>
    <div class="gridRight content">
        <div id="adminContent"></div>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function () {
        if ('<?= $pluginTarget ?>' == "true") {
            loadPluginAdminPageModule('<?= $pluginTrigger ?>', '<?= $pluginTriggerTarget ?>');
            // $('.pluginAdminItem[plugin_name="<?= $pluginTrigger ?>"][mod_target="<?= $pluginTriggerTarget ?>"]').click();
        } else {
            loadAdminPageModule('<?= $modLoad ?>');
        }
    });
</script>
<?php closeTemplate("account"); ?>