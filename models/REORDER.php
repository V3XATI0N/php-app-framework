<?php

if (empty($api_models[$api_path[2]])) { apiDie('no such model.', 404); }

$saveOrder = [];
$items = getModelItems($api_path[2], true);
foreach ($api_data as $item_id) {
    foreach ($items as $item) {
        if ($item['id'] == $item_id) {
            array_push($saveOrder, $item);
        }
    }
}
foreach ($items as $item) {
    if (!in_array($item['id'], $api_data)) {
        array_push($saveOrder, $item);
    }
}
$store = $api_models[$api_path[2]]['store'];

if (emit_file($store, $saveOrder)) {
    apiDie('ok', 200);
} else {
    apiDie('error', 500);
}
