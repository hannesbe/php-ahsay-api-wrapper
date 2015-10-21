<?php

require 'ahsay-api-wrapper.php';

define('BACKUPSERVER_ADDRESS', 'http://ahsay.server.com');
define('BACKUPSERVER_ADMINUSER', 'adminuser');
define('BACKUPSERVER_ADMINPASSWORD', 'password');

$api = new AhsayApiWrapper(BACKUPSERVER_ADDRESS, BACKUPSERVER_ADMINUSER, BACKUPSERVER_ADMINPASSWORD);

$api->debug(true);

$user = 'user01'; // Ahsay username
$backupSet = '1317401234567'; // Ahsay numeric backupset ID

$lastJobID = $api->getMostRecentBackupJob($user, $backupSet);
$lastJobDetailArray = $api->getUserBackupJobDetails($user, $backupSet, $lastJobID)['@attributes'];

//var_dump($lastJobDetailArray);
printf('BackupJobStatus: '.$lastJobDetailArray['BackupJobStatus']."\n");
printf('EndTime: '.$lastJobDetailArray['EndTime']);
