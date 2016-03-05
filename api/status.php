<?php

$info = new stdClass;
$info->diskspace = diskfreespace(__DIR__);
$info->total_diskspace = disk_total_space(__DIR__);

die(json_encode($info));