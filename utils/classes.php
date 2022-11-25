<?php

/* classes go here. */

class CurlRequest {
    function __construct($url = null) {
        $this->method = 'GET';
        $this->data = null;
        $this->headers = [
            'Accept: application/json',
            'Content-Type: application/json'
        ];
        $co = [
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_RETURNTRANSFER => 1
        ];
        if ($url  !== null) {
            $this->url = $url;
        }
        $this->curl_opts = $co;
    }
    public function set_method($method) {
        $co = $this->curl_opts;
        switch ($method) {
            case 'GET':
                break;;
            case 'POST':
                $co[CURLOPT_POST] = 1;
            default:
                $co[CURLOPT_CUSTOMREQUEST] = $method;
        }
        $this->curl_opts = $co;
    }
    public function set_agent($agent) {
        $headers = $this->headers;
        array_push($headers, 'User-Agent: ' . $agent);
        $this->headers = $headers;
    }
    public function set_url($url) {
        $co = $this->curl_opts;
        $this->url = $url;
        $co[CURLOPT_URL] = $url;
        $this->curl_opts = $co;
    }
    public function set_headers($headers) {
        $co = $this->curl_opts;
        if (is_array($headers)) {
            $this->headers = $headers;
            $co[CURLOPT_HEADER] = $headers;
        }
        $this->curl_opts = $co;
    }
    public function set_data($data) {
        $co = $this->curl_opts;
        $json = json_encode($data);
        $this->data = $json;
        $co[CURLOPT_POSTFIELDS] = $json;
        $this->curl_opts = $co;
    }
    public function exec() {

        $co = $this->curl_opts;
        $co[CURLOPT_RETURNTRANSFER] = 1;
        $co[CURLOPT_HTTPHEADER] = $this->headers;

        if (empty($this->url)) {
            logError('CurlRequest: no URL defined', 'CurlRequest');
            return false;
        }
        $co[CURLOPT_URL] = $this->url;

        switch ($this->method) {
            case null:
            case false:
                logError('CurlRequest: no method defined', 'CurlRequest');
                return false;
                break;;
            case 'POST':
            case 'PATCH':
            case 'PUT':
                if (empty($this->data)) {
                    logError('CurlRequest: no data for method', 'CurlRequest');
                    return false;
                }
                $co[CURLOPT_POSTFIELDS] = $this->data;
                break;;
        }

        $c = curl_init();
        curl_setopt_array($c, $co);

        $r = curl_exec($c);
        curl_close($c);
        return json_decode($r, true);
    }
}

class ObjectModel {
    public $all_models;
    public $schema;
    public $store;
    public $all_items;
    public $modelName;
    function __construct($model = null, $override = false) {
        global $oset;
        global $api_models;
        $this->all_models = $api_models;
        if ($model !== null) {
            if (empty($api_models[$model])) {
                return false;
            }
            $this->modelName = $model;
            $this->schema = $api_models[$model];
            $this->store = getModelStore($model);
        }
    }
    public function add_model($data) {
        return false;
    }
    public function update_model($data) {
        return false;
    }
    public function del_model() {
        return false;
    }
    public function get_schema() {
        return $this->schema;
    }
    public function get_items($item = null) {
        $all_items = getModelItems($this->modelName);
        if ($item === null) {
            return $all_items;
        } else {
            foreach ($all_items as $ii) {
                if ($ii['id'] == $item) {
                    return $ii;
                }
            }
            return false;
        }
    }
    public function add_item($data, $owner = null) {
        $modelName = $this->modelName;
        $modelStore = $this->store;
        $modelItems = $this->get_items();
        try {
            $newItem = addModelItem($modelName, $data, $owner);
            return $newItem;
        } catch (exception $e) {
            $msg = $e->getMessage();
            $err = $msg[2];
            $code = $msg[1];
            return false;
        }
    }
    public function del_item($item) {
        $modelName = $this->modelName;
        $modelStore = $this->store;
        $delItem = $this->get_items($item);
        if ($delItem === false) {
            return false;
        }
        $save = deleteModelItem($modelName, $item);
        return $save;
    }
    public function update_item($item, $data) {
        $modelName = $this->modelName;
        $patchItem = $this->get_items($item);
        if ($patchItem === false) {
            return false;
        }
        $save = patchModelItem($modelName, $item, $data);
        return $save;
    }
    public function query($query) {
        // this only works for objects stored in a database.
        $modelSchema = $this->schema;
        if (preg_match('/^__.*db__:\/\/.*$/', $modelSchema['store'])) {
            global $oset;
            global $plugins;
            foreach ($plugins as $pluginName => $pluginConf) {
                if (isset($pluginConf['db_sources'])) {
                    foreach ($pluginConf['db_sources'] as $dbs => $dbc) {
                        if ($dbs == explode(':', $modelSchema['store'])[0]) {
                            $aa = [];
                            foreach (['host', 'name', 'user', 'pass'] as $authParam) {
                                if (isset($dbc[$authParam])) {
                                    $authParamString = $dbc[$authParam];
                                    if (explode(':', $authParamString)[0] == "oset") {
                                        array_push($aa, $oset[explode(':', $authParamString)[2]]);
                                    } else {
                                        array_push($aa, $authParamString);
                                    }
                                } else {
                                    array_push($aa, null);
                                }
                            }
                            $dbExec = getDb($query, $aa);
                            return $dbExec;
                        }
                    }
                }
            }
        } else {
            return false;
        }
    }
}
