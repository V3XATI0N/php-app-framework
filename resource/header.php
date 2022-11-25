<?php overlayUserSettings('header'); ?>
<div id="header" <?php if (isset($oset['vertical_layout']) and $oset['vertical_layout'] === true and $context != "login") { echo ' class="vertical_layout"'; } ?>>
    <div id="logo">
        <a href="/">
            <img src="<?= $oset['app_image'] ?>">
            <?php
            if (isset($oset['show_app_title']) and $oset['show_app_title'] === true) {
                ?><div id="headertitle"><?= $oset['app_name'] ?></div><?php
            }
            ?>
        </a>
    </div>
    <?php
    if (!isset($oset['require_login']) or $oset['require_login'] === false or isset($_SESSION['userdata']['userRank'])) {
        ?>

        <div id="quicklinks" <?php if(isset($oset['vertical_layout']) and $oset['vertical_layout'] === true and $context != "login"){ ?>class="vertical_layout"<?php } ?>>
            <?php
            $navLinks = getNavLinks();
            foreach ($navLinks as $link) {
                $linkClass = "navMenuLink";
                if ($link['href'] == "/" . $api_path[1]) { $linkClass .= " activePage"; }
                ?>
                <a source="<?= $link['plugin'] ?>" id="<?= $link['id'] ?>" class="<?= $linkClass ?>" href="<?= $link['href'] ?>">
                    <img src="<?= $link['icon'] ?>" title="<?= $link['text'] ?>">
                    <span class="navMenuLinkText"><?= $link['text'] ?></span>
                </a>
                <?php
            }
            ?>
        </div>
        
        <div id="sessionLinks">
            <?php
            if ($context != "login" and $api_path[1] != "login") {
                ?>
                <div class="navMenuLink sticky" id="sessionOptionToggle">
                    <img src="/resource/menu.png">
                </div>
                <?php
                $sessionLinks = getSessionLinks();
                foreach ($sessionLinks as $s_link) {
                    ?>
                    <a class="navMenuLink" href="<?= $s_link['href'] ?>">
                        <img src="<?= $s_link['icon'] ?>">
                        <span class="navMenuLinkText"><?= $s_link['text'] ?></span>
                    </a>
                    <?php
                }
            }
            ?>
        </div>
        <?php
    }
    ?>
</div>
