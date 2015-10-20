# php-ahsay-api-wrapper
PHP API wrapper for AhsayOBS

> Some API calls are known to be missing.  You are welcome to write the code
> for these functions yourself and submit the necessary code to me for
> inclusion in future releases of this library.

Example usage;

```php
require 'ahsay-api-wrapper/ahsay-api-wrapper.php';

define('BACKUPSERVER_ADDRESS', 'ahsay.server.com');
define('BACKUPSERVER_PORT', '80');
define('BACKUPSERVER_ADMINUSER', 'adminuser');
define('BACKUPSERVER_ADMINPASSWORD', 'password');

$api = new AhsayApiWrapper(BACKUPSERVER_ADDRESS, BACKUPSERVER_PORT, BACKUPSERVER_ADMINUSER, BACKUPSERVER_ADMINPASSWORD);

$api->debug(true);

$user = 'user01'; // Ahsay username
$backupSet = '1317401234567'; // Ahsay numeric backupset ID

$lastJobID = $api->getMostRecentBackupJob($user, $backupSet);
$lastJobDetailArray = $api->getUserBackupJobDetails($user, $backupSet, $lastJobID)['@attributes'];

//var_dump($lastJobDetailArray);
printf('BackupJobStatus: '.$lastJobDetailArray['BackupJobStatus']."\n");
printf('EndTime: '.$lastJobDetailArray['EndTime']);

```
