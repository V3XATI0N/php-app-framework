<?php

if (!isset($api_path[4]) or $api_path[4] == "") {
    apiDie('specify plugin.', 400);
}
$plugins = buildPluginRegistry(true);
$plugin = $api_path[4];
if (!isset($plugins[$plugin])) {
    apiDie('not found', 404);
}

$pluginConf = $plugins[$plugin];
$pluginFile = $pluginConf['file_root'] . '/plugin.json';
$pluginFileConf = parse_file($pluginFile);

$pluginState = $pluginFileConf['enabled'];
if ($pluginState === true) {
    apiDie('already enabled.', 409);
}
$pluginFileConf['enabled'] = true;
$scriptContent = null;
if (emit_file($pluginFile, $pluginFileConf)) {
    $plugins[$plugin]['enabled'] = true;
    /*
    COMPOSER AUTOMATION
    Whenever a plugin is enabled/disabled, the Composer subsystem will refresh
    all Composer packages. This removes any packages specified (only) by a
    plugin when it's disabled, and adds them when it's enabled.
    */
    updateComposerPackages();
    if (file_exists($pluginConf['file_root'] . '/plugin_enable.js')) {
        $scriptContent = str_replace(["\r", "\n"], '', file_get_contents($pluginConf['file_root'] . '/plugin_enable.js'));
    }
    apiDie([
        'success' => true,
        'script' => $scriptContent
    ], 200);
} else {
    apiDie([
        'success' => false,
        'error' => 'error saving plugin configuration.'
    ], 500);
}
