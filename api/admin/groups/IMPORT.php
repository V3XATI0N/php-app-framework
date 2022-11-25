<?php

if (!is_array($api_data) or count($api_data) == 0) { apiDie('invalid data.', 400); }

$resp = [];

$list = $coreUsers['groups'];

foreach ($api_data as $newItem) {
    $includeItem = true;
    foreach ($list as $iName => $iConf) {
        if ($iConf['id'] == $newItem['id']) { $includeItem = false; }
        if ($iName == $newItem['custname']) { $includeItem = false; }
    }
    if ($includeItem === false) {
        $resp[$newItem['custname']] = "skipped, already exists in system.";
        continue;
    }
    if (preg_match('/^http.*$/', $newItem['url'])) {
        $newUrl = parse_url($newItem['url'])['host'];
    } else {
        $newUrl = $newItem['url'];
    }
    $list[$newItem['custname']] = [
        'id' => $newItem['id'],
        'rank' => 'user',
        'url' => $newUrl,
        'users' => []
    ];
    $resp[$newItem['custname']] = 'added, we hope.';
}

$coreUsers['groups'] = $list;
if (saveUsersAndGroups($coreUsers)) {
    apiDie($resp, 200);
} else {
    apiDie('data error', 500);
}
