<?php
require 'ahsay-api-wrapper.php';

const  BACKUPSERVER_ADDRESS       = 'http://ahsay.server.com';
const  BACKUPSERVER_ADMINUSER     = 'adminuser';
const  BACKUPSERVER_ADMINPASSWORD = 'password';

try {
    $api = new AhsayApiWrapper(BACKUPSERVER_ADDRESS, BACKUPSERVER_ADMINUSER, BACKUPSERVER_ADMINPASSWORD);

    $api->debug(true);

    $user = 'user01'; // Ahsay username
    $backupSet = '1317401234567'; // Ahsay numeric backupset ID

    $lastJobID = $api->getMostRecentBackupJob($user, $backupSet);
    $lastJobDetailArray = $api->getUserBackupJobDetails($user, $backupSet, $lastJobID)['@attributes'];

    //var_dump($lastJobDetailArray);
    printf('BackupJobStatus: '.$lastJobDetailArray['BackupJobStatus']."\n");
    printf('EndTime: '.$lastJobDetailArray['EndTime']);
} catch (Exception $e) {
    echo $e->GetMessage();
}
