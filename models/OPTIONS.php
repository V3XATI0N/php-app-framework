<?php

$models = getModels();
$return = [];
foreach ($models as $modName => $modConf) {
    $return[$modName] = $modConf['fields'];
}

if (!isset($api_path[2]) or empty($api_path[2])) {
    apiDie($return, 200);
} else {
    if (!isset($models[$api_path[2]])) {
        apiDie('no such model', 404);
    }
    $return = $return[$api_path[2]];
}
apiDie($return, 200);