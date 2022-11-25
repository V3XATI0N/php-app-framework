<?php

if (empty($api_path[4])) {
    apiDie($api_models, 200);
} else {
    if (empty($api_models[$api_path[4]])) {
        apiDie('no such model ' . $api_path[4], 404);
    } else {
        apiDie([$api_path[4] => $api_models[$api_path[4]]], 200);
    }
}