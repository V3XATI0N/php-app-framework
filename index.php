<?php
/*
    ridiculous php app framework that exists for no reason
    Copyright (C) 2022  Big Tex's Shitty Code and Massage Emporium

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/
//apiDie(file_get_contents('php://input'), 500);
require_once(__DIR__ . '/utils/functions.php');
require_once(__DIR__ . '/utils/classes.php');

$version_data = parse_file(__DIR__ . '/utils/version.json');
if (!isset($_SESSION)) {
    session_name("TOXSESSIONID");
}

startSession();

// settings / index data
$systemSchema = buildSystemSettings(true);
$oset = $systemSchema[1];
$oset_schema = $systemSchema[0];
$coreUsers = getUsersAndGroups();
$app_title = $oset['app_name'];

header("Content-Security-Policy: " . $oset['csp_header']);
header("X-Powered-By: " . $oset['x_powered_by'] . " " . $version_data['version']);

if (isset($oset['disable_iframe_embedding']) and $oset['disable_iframe_embedding'] === true) {
    header("X-Frame-Options: SAMEORIGIN");
}

// get plugin stuff
$plugins = buildPluginRegistry();
$api_models = getModels();

// api stuff
$url_query = parse_url($_SERVER['REQUEST_URI']);
$api_method = $_SERVER['REQUEST_METHOD'];
$api_path = explode('/', $url_query['path']);
$api_uri = implode('/', array_slice($api_path, 1));

$logEvent = [
    'path' => $api_path,
    'method' => $api_method,
    'url_query' => $url_query,
    'client' => $_SERVER['REMOTE_ADDR']
];
//apiDie($logEvent, 500);
logEvent($logEvent, 3);

switch ($api_method) {
    case "HEAD":
        apiDie('no.', 405);
        break;
}

if (isset($url_query['query'])) {
    parse_str($url_query['query'], $url_query);
}
if (isset($_POST) and is_array($_POST) and count($_POST) > 0) {
    $api_data = $_POST;
    $api_files = $_FILES;
} else {
    $content_type = explode(';', $_SERVER['CONTENT_TYPE'])[0];
    switch ($content_type) {
        case "multipart/form-data":
            # stupid PHP, stop assuming you know how I am writing this app.
            # this (hopefully) allows us to PUT/PATCH model objects containing file input types
            # thanks to https://stackoverflow.com/questions/9464935/php-multipart-form-data-put-request/9469615#9469615
            $raw_data = file_get_contents('php://input');
            $boundary = substr($raw_data, 0, strpos($raw_data, "\r\n"));
            $parts = array_slice(explode($boundary, $raw_data), 1);
            $api_data = [];
            foreach ($parts as $part) {
                if ($part == "--\r\n") break;
                $part = ltrim($part, "\r\n");
                list($raw_headers, $body) = explode("\r\n\r\n", $part, 2);
                $raw_headers = explode("\r\n", $raw_headers);
                $headers = [];
                foreach ($raw_headers as $header) {
                    list($name, $value) = explode(':', $header);
                    $headers[strtolower($name)] = ltrim($value, ' ');
                }
                if (isset($headers['content-disposition'])) {
                    $filename = null;
                    preg_match(
                        '/^(.+); *name="([^"]+)"(; *filename="([^"]+)")?/',
                        $headers['content-disposition'],
                        $matches
                    );
                    list(, $type, $name) = $matches;
                    isset($matches[4]) and $filename = $matches[4];
                    switch ($name) {
                        case 'userfile':
                            file_put_contents($filename, $body);
                            break;
                        default:
                            $api_data[$name] = substr($body, 0, strlen($body) - 2);
                            break;
                    }
                }
            }
            break;
        default:
            $api_data = json_decode(file_get_contents('php://input'), true);
    }
}

if (isset($_SESSION['loginTime'])) {
    
    $userActions = loadUserActions($_SESSION['id']);
    if (isset($userActions['logout']) and $userActions['logout'] === true) {
        clearUserActions($_SESSION['id']);
        session_destroy();
        returnToHome();
    }
    
    if (implode("/", $api_path) != "/api/account/timeout") {
        if (time() - $_SESSION['loginTime'] > $oset['max_idle_time']) {
            session_destroy();
            returnToHome();
        }
        $_SESSION['loginTime'] = time();
    }

} else {
    $_SESSION['id'] = null;
}

foreach ($plugins as $pluginName => $pluginConf) {
    if (isset($pluginConf['includes'])) {
        foreach ($pluginConf['includes'] as $include) {
            if (file_exists($pluginConf['file_root'] . '/' . $include)) {
                require_once($pluginConf['file_root'] . '/' . $include);
            }
        }
    }
    if (file_exists($pluginConf['file_root'] . '/settings_override.json')) {
        $settingsOverride = parse_file($pluginConf['file_root'] . '/settings_override.json');
        foreach ($settingsOverride as $setKey => $setVal) {
            $oset[$setKey] = $setVal;
        }
    }
}

$oset['file_root'] = __DIR__;
$oset['api_tmp'] = generateRandomString(25);

if (!empty($oset['custom_login_page'])) {
    $login_page = $oset['custom_login_page'];
} else {
    $login_page = "login";
}

/*
COMPOSER AUTOMATION
Composer's autoload.php is initialized here. If that file doesn't exist for
some reason, then all required Composer packages will be reinstalled, which
will have a noticeable impact on whichever request causes that. So don't
delete /composer/vendor/autoload.php if you know what's good for you.

You can also set the disable_composer flag in the Admin Settings to avoid
this whole mess, but then some things will probably not work lol.
*/
if (!isset($oset['disable_composer']) || $oset['disable_composer'] === false) {
    if (empty($oset['composer_path'])) {
        $oset['composer_path'] = "/usr/local/bin/composer";
    }
    if (file_exists(__DIR__ . '/composer/vendor/autoload.php')) {
        require_once(__DIR__ . '/composer/vendor/autoload.php');
    } else {
        updateComposerPackages();
        if (file_exists($oset['file_root'] . '/composer/vendor/autoload.php')) {
            require_once($oset['file_root'] . '/composer/vendor/autoload.php');
        }
        chdir($oset['file_root']);
    }
}

$checkLoginExceptions = getModelItems('skip_login_page');
$loginExceptions = [];
if (count($checkLoginExceptions) > 0) {
    foreach ($checkLoginExceptions as $le) {
        array_push($loginExceptions, $le['name']);
    }
}

if (isset($_SESSION['MFA_IN_PROGRESS']) and $_SESSION['MFA_IN_PROGRESS'] === true) {
    header('Location: /validate_login');
    die();
}

if (!empty($api_path[1])) {
    if (!empty($oset['custom_login_page']) and empty($_SESSION['id']) and $api_path[1] != "resource" and $api_path[1] != "api" and $api_path[1] != "models") {
        if (count($loginExceptions) == 0 or !in_array($api_path[1], $loginExceptions)) {
            if ($api_path[1] == $oset['custom_login_page']) {
                include(__DIR__ . '/login.php');
                die();
            } elseif (!empty($oset['require_login']) and $oset['require_login'] === true) {
                header("Location: /" . $oset['custom_login_page']);
                die();
            }
        }
    }
    if (file_exists(__DIR__ . '/' . $api_path[1] . '.php') and $api_path[1] != "index") {
        if ($api_path[1] == "login" and !empty($oset['custom_login_page']) and $oset['custom_login_page'] != "login") {
            returnToHome('/');
        }
        include(__DIR__ . '/' . $api_path[1] . '.php');
    } elseif (file_exists(__DIR__ . '/init/' . $api_path[1] . '/index.php')) {
        include(__DIR__ . '/init/' . $api_path[1] . '/index.php');
    } elseif (file_exists(__DIR__ . '/' . $api_path[1] . '/index.php')) {
        include(__DIR__ . '/' . $api_path[1] . '/index.php');
    } elseif ((count($loginExceptions) == 0 or !in_array($api_path[1], $loginExceptions)) and isset($oset['require_login']) and $oset['require_login'] === true and !isset($_SESSION['userdata']['userRank']) and $api_path[1] != "login") {
        returnToHome();
    } else {
        foreach ($plugins as $pluginName => $pluginConf) {
            $pluginRoot = $pluginConf['file_root'];
            if (file_exists($pluginRoot . '/init/' . $api_path[1] . '/' . $api_method . '.php')) {
                if (file_exists($pluginRoot . '/init/' . $api_path[1] . '/access.json')) {
                    $access_page = false;
                    $access = parse_file($pluginRoot . '/init/' . $api_path[1] . '/access.json');
                    if (isset($access['methods'])) {
                        if (isset($access['methods'][$api_method])) {
                            if ($access['methods'][$api_method] === "public:public" or accessMatch($_SESSION['id'], $access['methods'][$api_method]) === true) {
                                $access_page = true;
                            } else {
                                header('Location: /' . $login_page);
                                die();
                            }
                        }
                    }
                    if (isset($access['skip_template'])) { $skip_template = $access['skip_template']; }
                    if (isset($access['direct_output']) and is_bool($access['direct_output'])) {
                        $direct_output = $access['direct_output'];
                    } else {
                        $direct_output = false;
                    }
                }
                if (!isset($access_page) or $access_page === true) {
                    if (file_exists($pluginRoot . '/init/' . $api_path[1] . '/' . $api_method . '_SETUP.php')) {
                        ob_start();
                        include($pluginRoot . '/init/' . $api_path[1] . '/' . $api_method . '_SETUP.php');
                        $pageSetup = ob_get_clean();
                        flush();
                    }
                    if ($api_method == "GET" and $direct_output === false) { openTemplate(); }
                    include($pluginRoot . '/init/' . $api_path[1] . '/' . $api_method . '.php');
                    if ($api_method == "GET" and $direct_output === false) { closeTemplate(); }
                    die();
                } else {
                    returnToHome(403);
                }
            } elseif (isset($oset['plugin_direct_fileserver']) and $oset['plugin_direct_fileserver'] === true and isset($pluginConf['serve_direct']) and $pluginConf['serve_direct'] === true) {
                $ruri = $_SERVER['REQUEST_URI'];
                if (isset($pluginConf['direct_fileserver_options'])) {
                    $dfmo = $pluginconf['direct_fileserver_options'];
                } else {
                    $dfmo = null;
                }

                /*
                PRIVILEGED FOLDERS.
                    despite serving resources directly, some key folders will
                    continue to behave as they do for standard plugins, and
                    others are specifically meant to provide a place for
                    client-inaccessible locations (for php includes, etc).

                    -   /priv
                        this folder is explicitly forbidden to clients. put
                        php includes and other things you want to keep
                        out of bounds for browsers in here

                    -   /resource
                        this folder works like it does for other plugins.
                        by default, uploaded files will be stored here and
                        will be accessible to web clients (based on access
                        control rules)

                    -   /api
                        this folder can be used to build a REST API just
                        like a standard plugin. there is specific logic for
                        the /api folder that controls how resources are
                        treated.

                    -   /init
                        resources in the /init folder will be treated like
                        they are in standard plugins and cannot be served
                        directly.

                    the folders below continue to be off-limits to DFM plugins
                    because they are reserved for core system use. requests for
                    anything inside these top-level folders will never reach a
                    plugin.
                    
                    note that the /resource and /api folders can still
                    be used inside the plugin, but their contents are used
                    according to standard plugin rules, not served directly.
                    
                    -   /account
                    -   /admin
                    -   /models
                    -   /plugin
                    -   /plugins
                    -   /resource
                    -   /api
                */
                switch ($api_path[1]) {
                    case "priv":
                    case "init":
                    case "api":
                    case "resource":
                    case "account":
                    case "admin":
                    case "models":
                        returnToHome();
                        die();
                }
                if (file_exists($pluginRoot . $ruri) or file_exists($pluginRoot . $ruri . '.html') or file_exists($pluginRoot . $ruri . '.php')) {
                    if (empty($_SESSION)) {
                        $_SESSION = [
                            'id' => null
                        ];
                    }
                    if (file_exists($pluginRoot . $ruri . '.html')) {
                        $rfname = $ruri . '.html';
                    } elseif (file_exists($pluginRoot . $ruri . '.php')) {
                        $rfname = $ruri . '.php';
                    } else {
                        $rfname = $ruri;
                    }
                    $rbname = pathinfo($ruri, PATHINFO_FILENAME);
                    $rfile = $pluginRoot . $rfname;
                    $rext = pathinfo($rfile, PATHINFO_EXTENSION);

                    /* okay FINE we will have access rules, jesus */
                    if (file_exists($pluginRoot . "/access.json")) {
                        $acc = parse_file($pluginRoot . "/access.json");
                    }
                    if (!empty($acc['direct_fileserver_rules'])) {
                        $dsrules = $acc['direct_fileserver_rules'];
                        if (!empty($dsrules[$ruri])) {
                            $ruri_acc = $dsrules[$ruri];
                        } else {
                            if (!empty($dsrules['__default__']) and !empty($dsrules['__default__']['methods'])) {
                                $ruri_acc = $dsrules['__default__'];
                            } else {
                                $ruri_acc = [
                                    'methods' => [
                                        "GET" => "user:user",
                                        "DELETE" => "admin:user",
                                        "POST" => "admin:user",
                                        "PUT" => "admin:user",
                                        "OPTIONS" => "user:user"
                                    ]
                                ];
                            }
                        }
                    }
                    if (empty($ruri_acc['methods'][$api_method])) {
                        $ruri_acc['methods'][$api_method] = "admin:user";
                    }
                    $req_acc = $ruri_acc['methods'][$api_method];
                    //apiDie([$dsrules, $api_method, $ruri_acc, $req_acc], 400);
                    if (accessMatch($_SESSION['id'], $req_acc) === false) {
                        returnToHome();
                    }

                    switch ($rext) {
                        case "json":
                            /* we are not going to let randos on the internet read the plugin config files, sorry */
                            switch ($rbname) {
                                case "plugin":
                                case "settings_override":
                                case "settings_schema":
                                case "models":
                                    returnToHome();
                                default:
                                    apiDie(parse_file($rfile), 200, "application/json");
                            }
                            break;
                        case "yaml":
                        case "yml":
                            /* no, not even if they are in yaml for some reason */
                            switch ($rbname) {
                                case "plugin":
                                case "models":
                                case "settings_schema":
                                    returnToHome();
                                default:
                                    $yaml_data = parse_file($rfile);
                                    if (is_array($yaml_data)) {
                                        if (!isset($oset['upgrade_yaml_files']) or $oset['upgrade_yaml_files'] === false) {
                                            apiDie(file_get_contents($rfile), 200, 'text/plain');
                                        } else {
                                            $yaml_out = yaml_emit($yaml_data);
                                            header('Content-Type: text/plain');
                                            http_response_code(200);
                                            die($yaml_out);
                                        }
                                    } else {
                                        returnToHome();
                                    }
                            }
                            break;
                        case "htm":
                        case "html":
                            apiDie(file_get_contents($rfile), 200, "text/html");
                        case "css":
                            if ($dfmo !== null and isset($dfmo['eval_css']) and $fmo['eval_css'] === true) {
                                ob_start();
                                include($rfile);
                                $fc = ob_get_clean();
                            } else {
                                $fc = file_get_contents($rfile);
                            }
                            if (isset($oset['minimize_css_files']) and $oset['minimize_css_files'] === false) {
                                apiDie($fc, 200, "text/css");
                            } else {
                                apiDie(minimizeCSSsimple($fc), 200, "text/css");
                            }
                            break;
                        case "js":
                            if ($dfmo !== null and isset($dfmo['eval_javascript']) and $dfmo['eval_javascript'] === true) {
                                ob_start();
                                include($rfile);
                                $fc = ob_get_clean();
                            } else {
                                $fc = file_get_contents($rfile);
                            }
                            apiDie($dc, 200, "application/javascript");
                        case "php":
                            include($rfile);
                            die();
                        default:
                            serveLocalFile($rfile);
                    }
                }
            }
        }
        if (!isset($_SESSION['id'])) {
            if (!empty($oset['custom_login_page']) and $api_path[1] == $oset['custom_login_page']) {
                include(__DIR__ . '/login.php');
            }
            if (!empty($oset['redirect_404'])) {
                returnToHome($oset['redirect_404']);
            } else {
                returnToHome('login');
            }
        } else {
            returnToHome(404);
        }
    }
} else {
    returnToHome();
}