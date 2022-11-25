<?php
if (!empty($api_path[1])) {
    if (isset($access['page_title'])) {
        $titleStr = metaVars($access['page_title']);
    } else {
        $titleStr = metaVars('%API_PATH%');
    }
}
?>
<head style="visibility: hidden;">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Fira+Mono&family=Fira+Sans&display=swap" rel="stylesheet">
    <!--link rel="stylesheet" type="text/css" href="/resource/jquery/jquery-ui.min.css">
    <link rel="stylesheet" type="text/css" href="/resource/jquery/jquery-ui.structure.min.css">
    <link rel="stylesheet" type="text/css" href="/resource/jquery/jquery-ui.theme.min.css"-->
    <link rel="stylesheet" type="text/css" href="/resource/core.css" id="core_stylesheet">
    <link rel="stylesheet" href="/resource/ckeditor/plugins/codesnippet/lib/highlight/styles/default.css">
    <title><?= $oset['app_name'] ?> - <?= $titleStr ?></title>
    <script src="/resource/jquery/jquery.js"></script>
    <!--plugin scripts with load_first param-->
    <?php
    foreach ($plugins as $pluginName => $pluginConf) {
        if (!empty($pluginConf['scripts'])) {
            foreach ($pluginConf['scripts'] as $script) {
                if ((isset($script['load_first']) and $script['load_first'] === true) and (empty($script['access']) or accessMatch($_SESSION['id'], $script['access'])) and empty($script['ondemand'])) {
                    $sf = basename($script['source']);
                    if (file_exists($pluginConf['file_root'] . '/resource/' . $sf)) {
                        ?>
    <script type="text/javascript" src="/resource/plugins/<?= $pluginName ?>/scripts/<?= $sf ?>"></script>
                        <?php
                    }
                }
            }
        }
    }
    ?>
    <!--script src="/resource/jquery/jquery-ui.min.js"></script-->
    <script src="/resource/jquery/jquery.dragndrop.js"></script>
    <script src="/resource/arrive.js"></script>
    <script src="/resource/core_functions.js"></script>
    <script src="/resource/core_classes.js"></script>
    <script src="/resource/core.js"></script>
    <?php
    if (isset($oset['disable_iframe_embedding']) and $oset['disable_iframe_embedding'] === true) {
        ?>
    <script type="text/javascript">
        if (window.top !== window.self) { window.top.location.replace(window.self.location.href); }
    </script>
        <?php
    }
    if ($api_path[1] == "admin" and $api_path[2] == "settings") {
        $themeStringAppend = "&theme_preview=true";
    } else {
        $themeStringAppend = "&theme_preview=default";
    }
    if ($api_path[1] == "account") {
        ?>
    <script src="/resource/account.js"></script>
        <?php
    }
    if (isset($oset['enable_ckeditor']) and $oset['enable_ckeditor'] === true) {
        ?>
    <script id="script_enable_ckeditor" src="/resource/ckeditor/ckeditor.js"></script>
    <script id="script_enable_cke_codesnippet" src="/resource/ckeditor/plugins/codesnippet/lib/highlight/highlight.pack.js"></script>
        <?php
    }
    ?>
    <script type="text/javascript">
    themeString = "?darktheme=default<?= $themeStringAppend ?>";
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        themeString = "?darktheme=true<?= $themeStringAppend ?>";
    }
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
        if (event.matches) {
            reloadCss('?darktheme=true<?= $themeStringAppend ?>');
        } else {
            reloadCss('?darktheme=default<?= $themeStringAppend ?>');
        }
    });
    $(document).ready(function () {
        $('html').css({'visibility': 'visible'});
    });
    </script>
    <?php
    if (isset($_SESSION['id'])) {
        ?>
        <script src="/resource/user.js"></script>
        <?php
        if (accessMatch($_SESSION['id'], "moderator:admin")) {
            ?>
            <script src="/resource/admin.js"></script>
            <?php
        }
    }
    ?>
    <script type="text/javascript">
    $('#core_stylesheet').attr('href', '/resource/core.css' + themeString);
    </script>
    <link rel="icon" type="image/png" href="<?= $oset['app_icon'] ?>">
    <link rel="shortcut icon" type="image/png" href="<?= $oset['app_icon'] ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <?php
    $metaTags = [];
    if (!empty($access['html_meta'])) {
        foreach ($access['html_meta'] as $mtag => $mval) {
            array_push($metaTags, $mtag);
            $mval = metaVars($mval);
    ?><meta name="<?= $mtag ?>" content="<?= $mval ?>">
    <meta property="<?= $mtag ?>" content="<?= $mval ?>">
    <meta name="twitter:<?= $mtag ?>" content="<?= $mval ?>">
    <meta itemprop="<?= $mtag ?>" content="<?= $mval ?>">
    <?php
        }
    } else {
    ?><meta name="title" content="<?= $oset['app_name'] ?>">
    <meta property="og:title" content="<?= $oset['app_name'] ?>">
    <meta itemprop="name" content="<?= $oset['app_name'] ?>">
    <meta name="twitter:title" content="<?= $oset['app_name'] ?>">
    <meta name="image" content="<?= $oset['app_image'] ?>">
    <meta property="og:image" content="<?= $oset['app_image'] ?>">
    <meta itemprop="image" content="<?= $oset['app_image'] ?>">
    <meta name="twitter:image" content="<?= $oset['app_image'] ?>">
    <meta property="og:url" content="<?= $oset['app_url'] ?>">
    <meta itemprop="url" content="<?= $oset['app_url'] ?>">
    <meta name="twitter:url" content="<?= $oset['app_url'] ?>">
    <meta property="og:description" content="<?= $oset['app_description'] ?>">
    <meta itemprop="description" content="<?= $oset['app_description'] ?>">
    <meta name="twitter:description" content="<?= $oset['app_description'] ?>">
    <meta name="description" content="<?= $oset['app_description'] ?>">
    <?php
    }
    //logError([$oset['theme_color'], $_SESSION['userdata']['theme']]);
    $theme_color = $oset['theme_color'];
    if (isset($_SESSION['userdata']) and isset($_SESSION['userdata']['theme']) and (!isset($url_query['theme_preview']) or $url_query['theme_preview'] != "true")) {
        if (!isset($oset['user_themes']) or $oset['user_themes'] === true) {
            if (isset($_SESSION['userdata']['theme']['theme_color'])) {
                $theme_color = $_SESSION['userdata']['theme']['theme_color'];
            }
        }
    }
    ?>
    <meta name="theme-color" content="<?= $theme_color ?>">
    <?php
    foreach ($plugins as $pluginName => $pluginConf) {
        ?>
        <!--:: BEGIN PLUGIN INCLUDES: <?= $pluginName ?> :: <?= $pluginConf['description'] ?> ::-->
        <?php
        if (isset($pluginConf['html_meta'])) {
            foreach ($pluginConf['html_meta'] as $property => $content) {
                ?>
                <meta property="<?= $property ?>" content="<?= $content ?>">
                <?php
            }
        }
        if (isset($pluginConf['scripts'])) {
            foreach ($pluginConf['scripts'] as $script) {
                $script_file = basename($script['source']);
                if ((!isset($script['access']) or accessMatch($_SESSION['id'], $script['access'])) and (!isset($script['ondemand']) or $script['ondemand'] !== true) and empty($script['load_first'])) {
                    ?>
                    <script src="/resource/plugins/<?= $pluginName ?>/scripts/<?= $script_file ?>"></script>
                    <?php
                }
            }
        }
        if (isset($pluginConf['styles'])) {
            foreach ($pluginConf['styles'] as $script) {
                $script_file = basename($script['source']);
                if (!isset($script['access']) or accessMatch($_SESSION['id'], $script['access'])) {
                    ?>
                    <link rel="stylesheet" type="text/css" href="/resource/plugins/<?= $pluginName ?>/styles/<?= $script_file ?>">
                    <?php
                }
            }
        }
        ?><!--:: END PLUGIN includes: <?= $pluginName ?> ::--><?php
    }
    ?>
</head>
