<?php

$return = [true];
$users = $coreUsers['users'];
$emailList = [];

foreach ($users as $uname => $uconf) {
    if (empty($uconf['email'])) {
        $return = [
            false,
            [
                "reason" => $uname . " does not have an email address."
            ]
        ];
    } else {
        if (in_array($uconf['email'], $emailList)) {
            $return = [
                false,
                [
                    "reason" => $uconf['email'] . " is not a unique email address."
                ]
            ];
        }
        array_push($emailList, $uconf['email']);
    }
}

return $return;