<?php
/*

PHP API wrapper for AhsayOBS. Version 1.20

Copyright (C)  2016
Hannes Van de Vel (h@nnes.be),
Richard Bishop (ahsayapi@uchange.co.uk),
Christophe Aubry (christophe.aubry1984@gmail.com).

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

*/
class AhsayApiWrapper
{
  public $serverAddress;
  public $serverAdminUsername;
  public $serverAdminPassword;
  public $serverVersion;
  public $debug;
  public $error;

  /*
  Note:
  All times (user added, backupset last run, completed etc) are in the form
  of Unix timestamps.  In the case of Java this is the number of milliseconds
  since Jan 1st 1970; though PHP counts this as seconds since Jan 1st 1970.
  The solution is to disregard the final 3 digits of the value output by OBS
  */

  // Constructor
  public function __construct($address, $username, $password, $version)
  {
    $this->serverAddress = rtrim($address, '/'); // Remove trailing slash
    $this->serverAdminUsername = $username;
    $this->serverAdminPassword = $password;
    $this->serverVersion = $version; // Choose between Ahsay OBS v6 or v7
    $this->debug;
  }

  // Enable/disable debugging
  public function debug($which)
  {
    $this->debug = $which;
  }

  // Authenticate a user against OBS
  public function authenticateUser($username, $password)
  {
    $this->debuglog("Authenticate user $username");

    $url = "/AuthUser.do?LoginName=$username&Password=$password";
    $result = $this->runQuery($url);

    // If that didn't happen
    $this->errorHandler($result, "Authenticate user failed.");

    return 'OK';
  }

  // Get a particular user
  public function getUser($username)
  {
    $this->debuglog("Getting user '$username'");

    $url = "/GetUser.do?LoginName=$username";
    $result = $this->runQuery($url);

    // If that didn't happen
    $this->errorHandler($result, "No user details found for '$username'.");

    return $this->decodeResult($result);
  }

  // Get an array of all users
  public function getUsers()
  {
    $this->debuglog("Getting user list");

    $url = "/ListUsers.do";
    $result = $this->runQuery($url);

    // If that didn't happen
    $this->errorHandler($result, "Problem during getUsers().");

    return $this->decodeResult($result);
  }

  // Get all backup sets for a particular user
  public function getUserBackupSets($username)
  {
    $this->debuglog("Getting backup sets for user '$username'");

    $url = "/ListBackupSets.do?LoginName=$username";
    $result = $this->runQuery($url);

    // If that didn't happen
    $this->errorHandler($result, "Problem during getUserBackupSets() for '$username'.");

    return $this->decodeResult($result);
  }

  // Get storage statistics for a particular user
  public function getUserStorageStats($username, $date)
  {
    $this->debuglog("Getting storage stats for user '$username'");

    $url = "/GetUserStorageStat.do?LoginName=$username&YearMonth=$date";
    $this->debuglog($url);
    $result = $this->runQuery($url);

    // If that didn't happen
    $this->errorHandler($result, "Problem during getUserStorageStats() for '$username'.");

    return $this->decodeResult($result);
  }

  // Get all backup jobs for a particular user
  public function getUserBackupJobs($username)
  {
    $this->debuglog("Getting backup jobs for user '$username'");

    $url = "/ListBackupJobs.do?LoginName=$username";
    $result = $this->runQuery($url);

    // If that didn't happen
    $this->errorHandler($result, "Problem during getUserBackupJobs() for '$username'.");

    return $this->decodeResult($result);
  }

  // Get all backup jobs for a particular user, limited to a particular backup set
  public function getBackupJobsForSet($username, $backupset)
  {
    $this->debuglog("Getting backup jobs for user '$username', for backup set with id '$backupset'");

    $url = "/ListBackupJobs.do?LoginName=$username";
    $result = $this->runQuery($url);

    // If that didn't happen
    $this->errorHandler($result, "Problem during getBackupJobsForSet() for '$username', for backup set with id '$backupset'.");

    $data = $this->decodeResult($result);

    if ($this->serverVersion == '6') {
      foreach ($data->children() as $set) {
        // If this is the backupset we are interested in
        if ($set['ID'] == $backupset) {
          $this->debuglog(sizeof($set)." job(s) found for set '$backupset'");
          // Go through each job id
          foreach ($set->BackupJob as $job) {
            $backupJobs[] = (string) $job['ID'];
          }

          return $backupJobs;
        }
      }
    } elseif ($this->serverVersion == '7') {
      foreach ($data->Data as $set) {
        // If this is the backupset we are interested in
        if ($set->BackupSetID == $backupset) {
          $this->debuglog(sizeof($set->BackupJob)." job(s) found for set '$backupset'");

          return $set->BackupJob;
        }
      }
    }
    // If we get to here then that backup set obviously doesn't exist!
    $this->errorHandler($result, "Problem doing getBackupJobsForSet() - looks like set '$backupset' doesn't exist");
  }

  // Get the IDs of each backup job for this set in reverse order
  public function getBackupSetJobIds($username, $backupset, $rev)
  {
    if(isset($rev) === false) {
      $rev = false;
    }

    $this->debuglog("Getting list of backup job ids for user '$username', for backup set with id '$backupset'");

    // Get a list of all backup jobs for this backup set
    $jobs = $this->getBackupJobsForSet($username, $backupset);

    if (sizeof($jobs) <= 0) {
      throw new Exception("Could not run getUserBackupJobsForSet() in getBackupSetJobIds() for backup set id '$backupset'.");
    }

    // Sort in reverse?
    if ($rev) {
      rsort($jobs);
    }
    if (!$rev) {
      sort($jobs);
    }

    return $jobs;
  }

  // Get the ID of the most recent job for this backup set
  public function getMostRecentBackupJob($username, $backupset)
  {
    $this->debuglog("Running getMostRecentBackupJob() for backup set with id '$backupset'");

    // Get a list of all backup jobs for this backup set (in reverse order)
    $jobs = $this->getBackupSetJobIds($username, $backupset, true);
    if (!$jobs) {
      throw new Exception("Could not run getBackupSetJobIds() in getMostRecentBackupJob() for backup set id '$backupset'.");
    }

    // Return just the most recent
    return $jobs[0];
  }

  // Get all backup jobs for a particular user
  public function getUserBackupJobDetails($username, $backupset, $backupjob)
  {

    $this->debuglog("Getting backup job details for user '$username', job id '$backupjob'");

    $destinationid = $this->getDestinationID($username, $backupset, $backupjob);

    if(isset($destinationid) === false) {
      $destinationid='0';
    }

    $this->debuglog("Getting backup job details for user '$username', job id '$backupjob'");

    $url = "/GetBackupJobReport.do?LoginName=$username&BackupSetID=$backupset&BackupJobID=$backupjob&DestinationID=$destinationid";
    $result = $this->runQuery($url);

    // If that didn't happen
    $this->errorHandler($result, "Problem during getUserBackupJobDetails() for '$username', job id '$backupjob'. $result");

    return $this->decodeResult($result);
  }

  // Get details on a particular backup set
  public function getUserBackupSet($username, $backupset)
  {
    $this->debuglog("Getting details for backup set with id '$setid' for user '$username'");

    $url = "/GetBackupSet.do?LoginName=$username&BackupSetID=$backupset";
    $result = $this->runQuery($url);

    // If that didn't happen
    $this->errorHandler($result, "Problem during getUserBackupSet() for $username. $result");

    return $this->decodeResult($result);
  }

  // Get status of a user backup job for a particular date in (yyyy-MM-dd format)
  public function listBackupJobStatus($username, $date)
  {
    if (isset($date) === false) {
      $date = date("Y-m-d");
    }

    $this->debuglog("Getting status for backup job that run on the '$date' for user '$username'");

    $url = "/ListBackupJobStatus.do?LoginName=$username&BackupDate=$date";
    $result = $this->runQuery($url);

    // If that didn't happen
    $this->errorHandler($result, "Problem during listBackupJobStatus() for $username. $result");

    return $this->decodeResult($result);

  }

  // Retrieve the DestinationID of a backup job for a particular backupset of a given user
  public function getDestinationID($username, $backupset, $backupjob)
  {

    $date = substr($backupjob, 0, 10);

    $this->debuglog("Getting the destination of the backup job '$backupjob' for user '$username'");

    $data = $this->listBackupJobStatus($username, $date);

    if ($this->serverVersion === '6') {
      foreach($data->children() as $backupjobstatus) {
        if (strval($backupjobstatus['BackupSetID']) === $backupset && strval($backupjobstatus['ID']) === $backupjob) {
          $this->debuglog("Destination found for backup set '$backupset' and backup job '$backupjob'");
          $result = $backupjobstatus['DestinationID'];
        }

        return $result;
      }
    } elseif ($this->serverVersion === '7') {
      foreach($data->Data as $backupjobstatus) {
        if ($backupjobstatus->BackupSetID === $backupset && $backupjobstatus->ID === $backupjob) {
          $this->debuglog("Destination found for backup set '$backupset' and backup job '$backupjob'");
          $result = $backupjobstatus->DestinationID;
        }
      }

      return $result;
    }
  }

  // Run an API query against OBS
  public function runQuery($url)
  {
    try {
      if ($this->serverVersion == '6') {
        $url = $this->serverAddress.'/obs/api'.$url;

        // If this URL already has a query string
        if (strstr($url, '?')) {
          $url .= '&SysUser='.$this->serverAdminUsername.'&SysPwd='.$this->serverAdminPassword;
        }
        if (!strstr($url, '?')) {
          $url .= '?SysUser='.$this->serverAdminUsername.'&SysPwd='.$this->serverAdminPassword;
        }
        $this->debuglog("Trying $url");
        $result = file_get_contents($url);

      } elseif ($this->serverVersion == '7') {
        if (strstr($url, '?')) {
          $args=explode('&', substr($url, strpos($url, '?') +1));
          $url = $this->serverAddress.'/obs/api/json'.strstr($url, '?', TRUE);

          foreach ($args as $arg) {
            list($k, $v) = explode('=', $arg);
            $postData[$k] = $v;
          }
        } elseif (!strstr($url, '?')) {
          $url = $this->serverAddress.'/obs/api/json'. $url;
          $postData = array();
        }

        $postData['SysUser'] = $this->serverAdminUsername;
        $postData['SysPwd'] = $this->serverAdminPassword;

        $curlreq = curl_init($url);
        curl_setopt_array($curlreq, array (
          CURLOPT_POST  =>  TRUE,
          CURLOPT_RETURNTRANSFER =>  TRUE,
          CURLOPT_HTTPHEADER => array (
            'Authorization: Ahsay OBS JSON API v7.X',
            'Content-Type: application/json'
          ),
          CURLOPT_POSTFIELDS  =>  json_encode($postData)
          )
        );

        $this->debuglog("Trying $url");
        $result = curl_exec($curlreq);

      } else {
        throw new Exception("Please specify server version between 6 or 7 !\r\n");
      }

      return $result;
    }

    catch (Exception $e) {
      throw new Exception("Error accessing API: ".$e->GetMessage());
    }
  }

  // Decode XML from Ahsay OBS 6.X API and JSON from Ahsay version 7.X API
  public function decodeResult($result)
  {
    try {
      if ($this->serverVersion == '6') {
        return simplexml_load_string($result);
      } elseif ($this->serverVersion == '7') {
        return json_decode($result);
      } else {
        throw new Exception("Please specify server version between 6 or 7 !\e\n");
      }
    }
    catch (Exception $e) {
      throw new Exception("Error accessing API: ".$e->GetMessage());
    }
  }

  // Handle error returned from the OBS API
  public function errorHandler($result, $message)
  {
    try {
      if ($this->serverVersion == '6') {
        if (substr($result, 1, 3) == 'err') {
          throw new Exception($message . "\r\n" . $result . "\r\n");
        }
      } elseif ($this->serverVersion == '7') {
        $status=json_decode($result, TRUE);
        if ($status['Status'] == 'Error') {
          throw new Exception($message . "\r\n" . $result . "\r\n");
        }
      } else {
        throw new Exception("Please specify server version between 6 or 7 !\r\n");
      }
    }
    catch (Exception $e) {
      throw new Exception("Error accessing API: ".$e->GetMessage());
    }
  }

  public function formatBytes($bytes, $precision = 2)
  {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    // Uncomment one of the following alternatives
    $bytes /= pow(1024, $pow);
    // $bytes /= (1 << (10 * $pow));

    return round($bytes, $precision).' '.$units[$pow];
  }

  // Debug logging
  public function debuglog($message)
  {
    if ($this->debug) {
      printf("%s\n", $message);
    }
  }
}
