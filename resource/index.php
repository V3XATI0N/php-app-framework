<?php

$uri_path = explode('/', $api_uri);
$uri_file = __DIR__ . '/' . join('/', array_slice($uri_path, 1));

if ($api_path[2] == "plugins") {
    //$plugins = buildPluginRegistry(true);
    $pluginName = $api_path[3];
    $pluginFile = basename($api_uri);
    $pluginConf = $plugins[$pluginName];
    $pluginState = $pluginConf['enabled'];
    if ($pluginState === false) {
        $uriCheck = join('/', $api_path);
        if ($uriCheck != $pluginConf['logo_image']) {
            apiDie('not found', 404);
        }
    }
    $resType = $api_path[4];
    switch ($resType) {
        case "assets":
            $fileType = pathinfo($pluginFile, PATHINFO_EXTENSION);
            $pathLead = "";
            $pathScan = 5;
            while ($pathScan < count($api_path)) {
                $pathSegment = $api_path[$pathScan];
                $pathLead .= "/" . $pathSegment;
                $pathScan++;
            }
            $filePath = $pluginConf['file_root'] . '/resource/' . $pathLead;
            if (file_exists($filePath)) {
                switch ($fileType) {
                    case "php":
                    case "htm":
                    case "html":
                        apiDie("no.", 403);
                        break;
                    case "svg":
                        ob_start();
                        include($filePath);
                        $file_contents = ob_get_clean();
                        flush();
                        header('Content-Type: image/svg+xml;charset=UTF-8');
                        die($file_contents);
                        break;
                    case "jpg":
                    case "png":
                    case "gif":
                    case "pdf":
                        serveLocalFile($filePath);
                        break;
                    default:
                        $fileExt = pathinfo($filePath, PATHINFO_EXTENSION);
                        switch ($fileExt) {
                            case "css":
                                $mime = "text/css";
                                $disp = "Content-Disposition: inline";
                                break;;
                            default:
                                $mime = mime_content_type($filePath);
                                $disp = "Content-Disposition: attachment; filename=download." . $fileExt;
                        }
                        ob_start();
                        include($filePath);
                        $file_contents = ob_get_clean();
                        flush();
                        header('Content-Type: ' . $mime);
                        header($disp);
                        die($file_contents);
                        break;
                }
            } else {
                apiDie('no such file ' . $pathLead, 404);
            }
            break;
        case "scripts":
        case "styles":
            if (isset($plugins[$pluginName]) and isset($plugins[$pluginName][$resType])) {
                $scripts = $plugins[$pluginName][$resType];
                foreach ($scripts as $script) {
                    $scriptName = basename($script['source']);
                    $scriptPath = $pluginConf['file_root'] . '/' . $script['source'];
                    if ($scriptName == $pluginFile) {
                        if (file_exists($scriptPath)) {
                            $file_ext = pathinfo($scriptPath, PATHINFO_EXTENSION);
                            if (!isset($script['access']) or accessMatch($_SESSION['id'], $script['access'])) {
                                if ($file_ext == "css") {
                                    header('Content-Type: text/css;charset=UTF-8');
                                    header('Cache-Control: max-age=600');
                                    ob_start();
                                    include(__DIR__ . '/theme_colors.php');
                                    include(__DIR__ . '/core_css_vars.php');
                                    include($scriptPath);
                                    $content = ob_get_clean();
                                    flush();
                                    die(minimizeCSSsimple($content));
                                } elseif($file_ext == "svg") {
                                    include(__DIR__ . '/theme_colors.php');
                                    header('Content-Type: image/svg+xml;charset=UTF-8');
                                    header('Cache-Control: max-age=600');
                                    ob_start();
                                    include($scriptPath);
                                    $content = ob_get_clean();
                                    flush();
                                    die($content);
                                } elseif ($file_ext == "js") {
                                    if (isset($script['eval']) and $script['eval'] === true) {
                                        header('Content-Type: text/javascript');
                                        ob_start();
                                        include($scriptPath);
                                        $content = ob_get_clean();
                                        flush();
                                        die($content);
                                    } else {
                                        serveLocalFile($scriptPath);
                                    }
                                } else {
                                    serveLocalFile($scriptPath);
                                }
                                die();
                            } else {
                                logError($_SERVER['REMOTE_ADDR'] . ' tried to get ' . implode('/', $api_path));
                                apiDie('no access ' . $script['access'], 401);
                            }
                        } else {
                            apiDie('no can do buckaroo', 404);
                        }
                    }
                }
                apiDie('dang it', 404);
            } else {
                apiDie('none', 404);
            }
            break;
        default:
            apiDie('nope.', 404);
    }
}

if (file_exists($uri_file)) {
    $file_name = basename($api_uri);
    switch (pathinfo($file_name, PATHINFO_EXTENSION)) {
        case "css":
            header('Content-Type: text/css;charset=UTF-8');
            header('Cache-Control: max-age=600');
            ob_start();
            include(__DIR__ . '/theme_colors.php');
            include(__DIR__ . '/core_css_vars.php');
            include($uri_file);
            $content = ob_get_clean();
            flush();
            die(minimizeCSSsimple($content));
            break;
        case "svg":
            include(__DIR__ . '/theme_colors.php');
            header('Content-Type: image/svg+xml;charset=UTF-8');
            header('Cache-Control: max-age=600');
            ob_start();
            include($uri_file);
            $content = ob_get_clean();
            flush();
            die($content);
            break;
        case "png":
        case "jpg":
        case "gif":
        case "jpeg":
        case "js":
        case "txt":
        case "eot":
        case "ttf":
        case "woff":
        case "woff2":
            serveLocalFile($uri_file);
            break;
        default:
            header("Location: /");
    }
} else {
    header("Location: /");
}