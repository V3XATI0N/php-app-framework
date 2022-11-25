<?php

$vdata = parse_file($oset['file_root'] . '/utils/version.json');

apiDie($vdata, 200);