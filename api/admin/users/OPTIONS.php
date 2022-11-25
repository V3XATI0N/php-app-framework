<?php

$userModel = buildUserGroupModel()['user'];

apiDie($userModel, 200);