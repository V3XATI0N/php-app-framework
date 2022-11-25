<?php

$state = $api_data['state'];
if (empty($state)) { $state = false; }
if (!is_bool($state)) { return [false, ['reason'=>'invalid instructions: state is not bool value']]; }
switch ($state) {
    case true:
        $userGroupData = getUsersAndGroups();
        // create tables if not there already
        $createUserTable = getDb("CREATE TABLE IF NOT EXISTS `users` (`context` VARCHAR(32) UNIQUE, `data` LONGTEXT);");
        if ($createUserTable[0] === false) {
            return [false, ['reason'=>'failed to create users table']];
        }
        $oset['sql_backend'] = true;
        $sdata = saveUsersAndGroups($userGroupData);
        if ($sdata === false) {
            return [false, ['reason'=>'failed to save user/group data']];
        }
        /* $oset[] can probably stay file-based, at least for now...
        $createOsetTable = getDb("CREATE TABLE IF NOT EXISTS `oset` (`index` VARCHAR(32) UNIQUE, `oset` LONGTEXT, `oreg` LONGTEXT);");
        if ($createOsetTable[0] === false) {
            return [false, ['reason'=>'failed to create settings table]];
        }
        $odata = buildSystemSettings(true);
        $oset_data = base64_encode(json_encode($odata[1]));
        $oset_schema = base64_encode(json_encode($odata[0]));
        $osetsave = getDb("INSERT INTO `oset` (`index`, `oset`, `oreg`) VALUES (1, '{$oset_data}', '{$oset_schema}') ON DUPLICATE KEY UPDATE `oset` = '{$oset_data}', `oreg` = '{$oset_schema}';");
        if ($osetsave[0] === false) {
            return [false, ['reason'=>'failed to save settings registry data']];
        }
        */
        return [true];
        break;
    case false:
    default:
        $userGroupFile = $oset['file_root'] . '/data/users.json';
        $userGroupData = getUsersAndGroups();
        $oset['sql_backend'] = false;
        $save = saveUsersAndGroups($userGroupData);
        if ($save === false) {
            return [false, ['reason'=>'unable to save user/group data file']];
        }
        return [true];
}

return [false, ['reason'=>'testing', 'data'=>$api_data]];

echo 'fart';