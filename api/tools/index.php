<?php

if (!accessMatch($_SESSION['id'], 'user:user') and $api_path[3] != "ping") { apiDie('no access.', 403); }

if (empty($api_path[3])) { apiDie('specify endpoint', 400); }

$mod = $api_path[2];
$tool = $api_path[3];

if (file_exists(__DIR__ . '/' . $tool . '/index.php')) {
    include(__DIR__ . '/' . $tool . '/index.php');
} elseif (file_exists(__DIR__ . '/' . $tool . '/' . $api_method . '.php')) {
    include(__DIR__ . '/' . $tool . '/' . $api_method . '.php');
} else {
    apiDie('no such thing /' . $mod . '/' . $tool, 404);
}