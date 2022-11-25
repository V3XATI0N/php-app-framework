<?php

$userModel = buildUserGroupModel()['group'];

apiDie($userModel, 200);