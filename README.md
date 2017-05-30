# php-ahsay-api-wrapper
[![GitHub license](https://img.shields.io/github/license/hannesbe/php-ahsay-api-wrapper.svg)](https://raw.githubusercontent.com/hannesbe/php-ahsay-api-wrapper/master/LICENSE)

[![GitHub release](https://img.shields.io/github/release/hannesbe/php-ahsay-api-wrapper.svg)](https://github.com/hannesbe/php-ahsay-api-wrapper/releases) [![GitHub commits](https://img.shields.io/github/commits-since/hannesbe/php-ahsay-api-wrapper/1.1.1.svg)](https://github.com/hannesbe/php-ahsay-api-wrapper/commits/1.1.1)

PHP API wrapper for AhsayOBS

## Example usage

[ahsay-api-wrapper-example.php](ahsay-api-wrapper-example.php)
```php
require 'ahsay-api-wrapper.php';

const  BACKUPSERVER_ADDRESS       = 'http://ahsay.server.com';
const  BACKUPSERVER_ADMINUSER     = 'adminuser';
const  BACKUPSERVER_ADMINPASSWORD = 'password';
const  BACKUPSERVER_VERSION        = 'OBSversion';

try {
    $api = new AhsayApiWrapper(BACKUPSERVER_ADDRESS, BACKUPSERVER_ADMINUSER, BACKUPSERVER_ADMINPASSWORD, BACKUPSERVER_VERSION);

    $api->debug(true);
   
    $user = 'user01'; // Ahsay username
    $backupSet = '1317401234567'; // Ahsay numeric backupset ID

    $lastJobID = $api->getMostRecentBackupJob($user, $backupSet);
    $lastJobDetailArray = $api->getUserBackupJobDetails($user, $backupSet, $lastJobID);
    $DestinationID = $api->getDestinationID($user, $backupSet, $backupJob);

    //var_dump($api->getUser($user));
    //var_dump($api->getUSerBackupSet($user, $backupSet));
    //var_dump($api->getUserStorageStats($user, $date));
    //var_dump($api->getBackupJobsForSet($user, $backupSet));
    //var_dump($api->getBackupSetJobIds($user, $backupSet));
    //var_dump($api->listBackupJobStatus($user, $date));
    //var_dump($api->getUserBackupJobs($user));
    //var_dump($api->getMostRecentBackupJob($user, $backupset));

    printf ('DestinationID: ' . $DestinationID . "\n\r");
    printf('BackupJobStatus: '. $lastJobDetailArray->Data->BackupJobStatus . "\n");
    printf('EndTime: '. $lastJobDetailArray->Data->EndTime . "\n");

} catch (Exception $e) {
    echo $e->GetMessage();
}
?>
```
## Known issues / limitations
Some API calls are known to be missing.  You are welcome to write the code for these functions yourself and submit the necessary code to me for inclusion in future releases of this library.

## License
[![GitHub license](https://img.shields.io/github/license/hannesbe/php-ahsay-api-wrapper.svg)](https://raw.githubusercontent.com/hannesbe/php-ahsay-api-wrapper/master/LICENSE)

See the [LICENSE](LICENSE) file for more details.
