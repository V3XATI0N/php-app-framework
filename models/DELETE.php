<?php

$model = $api_path[2];
$item = $api_path[3];
if (empty($model) or empty($item)) {
    apiDie(['wow you messed up lol'], 400);
}
if (!isset($api_models[$model])) {
    apiDie(['no such model'], 404);
}

$doTheDelete = deleteModelItem($model, $item);
apiDie($doTheDelete[2], $doTheDelete[1]);

/*
$store = $api_models[$model]['store'];
$enforce_group = false;

// this forces users of non-admin groups to abide by the assign_to_group model parameter.
if (isset($api_models[$model]['assign_by_group']) and $api_models[$model]['assign_by_group'] === true) {
    if (!accessMatch($_SESSION['id'], "admin:user")) {
        $enforce_group = true;
    }
}
$items = getModelItems($model, true);
$save = [];
foreach ($items as $n) {
    if ($n['id'] == $item) {
        if ($enforce_group === true and $n['owner_group'] != $_SESSION['userdata']['group']) {
            apiDie(['you have no permission to do this.'], 401);
        }
    }
    if ($n['id'] != $item) {
        array_push($save, $n);
    }
}

/*if (emit_file($store, $save)) {
    apiDie(['ok'], 200);
} else {
    apiDie(['error storing data.'], 500);
}*/