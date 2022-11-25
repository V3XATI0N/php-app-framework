<?php
if (!empty($oset['audit_log_file'])) {
    $logFile = str_replace('__app_root__', $oset['file_root'], $oset['audit_log_file']);
} else {
    $logFile = $oset['file_root'] . '/data/audit_log.json';
}
//apiDie($logFile, 500);

if (!file_exists($logFile)) { apiDie('no log data', 404); }

use \JsonMachine\JsonMachine;

$logDataReturn = [];
$eventCount = 0;
$logData = JsonMachine::fromFile($logFile);
foreach($logData as $event) {
    if (!empty($api_path[4])) {
        if ($api_path[4] == $event['log_id']) {
            apiDie($event, 200);
        }
    } else {
        $eventCount++;
        $includeEvent = false;
        if (isset($url_query['start'])) {
            if ($eventCount >= $url_query['start']) {
                $includeEvent = true;
            }
            if (isset($url_query['end']) and $eventCount > $url_query['end']) {
                $includeEvent = false;
            }
        } else {
            if ($eventCount <= 25) {
                $includeEvent = true;
            }
        }
        if ($includeEvent === true or 1 == 1) {
            array_push($logDataReturn, [
                'id' => $event['log_id'],
                'time' => $event['time'],
                'method' => $event['method'],
                'path' => $event['path'],
                'user' => $event['user']
            ]);
        }
    }
}

if (!empty($api_path[4])) { apiDie('no such event.', 404); }

apiDie($logDataReturn, 200);
