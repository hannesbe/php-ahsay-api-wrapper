<?php

/*

    Ahsay OBS API functions wrapper.  Version 1.00

    Copyright (c) Richard Bishop (ahsayapi@uchange.co.uk) 2008-2009.

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.


    Note:

    Some API calls are known to be missing, I have no requirement for these
    and will add them when I have time.  You are welcome to write the code
    for these functions yourself and submit the necessary code to me for
    inclusion in future releases of this library.


    You will require a copy of clsParseXML.php - this turns the XML returned
    by OBS into multi-dimension PHP associative array structures and makes
    things much easier to parse.  Unfortunately there doesn't seem to be
    anywhere to download this from anymore - try searching Google for a copy.

*/

require_once 'clsParseXML.php';

class AhsayApiFunctions
{
    public $server_name;
    public $server_port;
    public $server_user;
    public $server_pass;
    public $debug;
    public $error;

   /*
   Note:
   All times (user added, backupset last run, completed etc) are in the form of Unix timestamps.  In the case of Java this is the
   number of milliseconds since Jan 1st 1970; though PHP counts this as seconds since Jan 1st 1970.  The solution is to disregard
   the final 3 digits of the value output by OBS
   */

   // Constructor
   public function AhsayApiFunctions($server, $port, $username, $password)
   {
       $this->server_name = $server;
       $this->server_port = $port;
       $this->server_user = $username;
       $this->server_pass = $password;
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

       $url = '/obs/api/AuthUser.do?';
       $url .= 'LoginName='.$username.'&Password='.$password;
       $result = $this->__runQuery($url);

      // If that didn't happen
      if (substr($result, 0, 4) == 'Err:') {
          $this->debuglog("Authenticate user failed $result");
          $this->error = $result;

          return false;
      } else {
          return 'OK';
      }
   }

   // Get a particular user
   public function getUser($username)
   {
       $this->debuglog("Getting user '$username'");

       $url = "/obs/api/GetUser.do?LoginName=$username";
       $result = $this->__runQuery($url);

      // If that didn't happen
      if (substr($result, 0, 4) == 'Err:') {
          $this->debuglog("No user details found for '$username'");
          $this->error = $result;

          return false;
      } else {
          return $this->xmlToArray($result);
      }
   }

   // Get an array of all users
   public function getUsers()
   {
       $this->debuglog('Getting user list');

       $url = '/obs/api/ListUsers.do';
       $result = $this->__runQuery($url);

      // If that didn't happen
      if (substr($result, 0, 4) == 'Err:') {
          $this->debuglog("Doing getUsers() failed $result");
          $this->error = $result;

          return false;
      } else {
          return $this->xmlToArray($result);
      }
   }

   // Get all backup sets for a particular user
   public function getUserBackupSets($username)
   {
       $this->debuglog("Getting backup sets for user '$username'");

       $url = "/obs/api/ListBackupSets.do?LoginName=$username";
       $result = $this->__runQuery($url);

      // If that didn't happen
      if (substr($result, 0, 4) == 'Err:') {
          $this->debuglog("Problem during getUserBackupSets() for '$username'");
          $this->error = $result;

          return false;
      } else {
          return $this->xmlToArray($result);
      }
   }

   // Get storage statistics for a particular user
   public function getUserStorageStats($username, $date)
   {
       $this->debuglog("Getting backup jobs for user '$username'");

       $url = "/obs/api/GetUserStorageStat.do?LoginName=$username&YearMonth=$date";
       $result = $this->__runQuery($url);

      // If that didn't happen
      if (substr($result, 0, 4) == 'Err:') {
          $this->debuglog("Problem during getUserStorageStats() for '$username'");
          $this->error = $result;

          return false;
      } else {
          return $this->xmlToArray($result);
      }
   }

   // Get all backup jobs for a particular user
   public function getUserBackupJobs($username)
   {
       $this->debuglog("Getting backup jobs for user '$username'");

       $url = "/obs/api/ListBackupJobs.do?LoginName=$username";
       $result = $this->__runQuery($url);

      // If that didn't happen
      if (substr($result, 0, 4) == 'Err:') {
          $this->debuglog("Problem during getUserBackupJobs() for '$username'");
          $this->error = $result;

          return false;
      } else {
          return $this->xmlToArray($result);
      }
   }

   // Get all backup jobs for a particular user, limited to a particular backup set
   public function getBackupJobsForSet($username, $backupset)
   {
       $this->debuglog("Getting backup jobs for user '$username', for backup set with id '$backupset'");

       $url = "/obs/api/ListBackupJobs.do?LoginName=$username";
       $result = $this->__runQuery($url);

      // If that didn't happen
      if (substr($result, 0, 4) == 'Err:') {
          $this->debuglog("Problem during getBackupJobsForSet() for '$username', for backup set with id '$backupset'");
          $this->error = $result;

          return false;
      } else {
          $data = $this->xmlToArray($result);

          foreach ($data['BACKUPSETS'][0]['BACKUPSET'] as $set) {

            // If this is the backupset we are interested in
            if ($set['ATTRIBUTES']['ID'] == $backupset) {
                return $set;
            }
          }

         // If we get to here then that backup set obviously doesn't exist!
         $this->debuglog("Problem doing getBackupJobsForSet() - looks like set '$backupset' doesn't exist");

          return false;
      }
   }

   // Get the IDs of each backup job for this set in reverse order
   public function getBackupSetJobIds($username, $backupset, $rev = false)
   {
       $backup_sets = array();

       $this->debuglog("Getting list of backup job ids for user '$username', for backup set with id '$backupset'");

      // Get a list of all backup jobs for this backup set
      $jobs = $this->getBackupJobsForSet($username, $backupset);
       if ($jobs == false) {
           $this->debuglog("Could not run getUserBackupJobsForSet() in getBackupSetJobIds() for backup set id '$id'");

           return false;
       }

      // Go through each job id
      foreach ($jobs['BACKUPJOB'] as $job) {
          $backup_sets[] = $job['ATTRIBUTES']['ID'];
      }

      // Sort in reverse?
      if ($rev != false) {
          rsort($backup_sets);
      } else {
          sort($backup_sets);
      }

       return $backup_sets;
   }

   // Get the ID of the most recent job for this backup set
   public function getMostRecentBackupJob($username, $backupset)
   {
       $this->debuglog("Running getMostRecentBackupJob() for backup set with id '$backupset'");

      // Get a list of all backup jobs for this backup set (in reverse order)
      $jobs = $this->getBackupSetJobIds($username, $backupset, true);
       if ($jobs == false) {
           $this->debuglog("Could not run getBackupSetJobIds() in getMostRecentBackupJob() for backup set id '$id'");

           return false;
       }

      // Return just the most recent
      return $jobs[0];
   }

   // Get all backup jobs for a particular user
   public function getUserBackupJobDetails($username, $backupset, $backupjob)
   {
       $this->debuglog("Getting backup job details for user '$username', job id '$backupjob'");

       $url = "/obs/api/GetBackupJobReport.do?LoginName=$username&BackupSetID=$backupset&BackupJobID=$backupjob";
       $result = $this->__runQuery($url);

      // If that didn't happen
      if (substr($result, 0, 4) == 'Err:') {
          $this->debuglog("Problem during getUserBackupJobDetails() for '$username', job id '$backupjob'");
          $this->error = $result;

          return false;
      } else {
          return $this->xmlToArray($result);
      }
   }

   // Get details on a particular backup set
   public function getUserBackupSet($username, $setid)
   {
       $this->debuglog("Getting details for backup set with id '$setid' for user '$username'");

       $url = "/obs/api/GetBackupSet.do?LoginName=$username&BackupSetID=$setid";
       $result = $this->__runQuery($url);

      // If that didn't happen
      if (substr($result, 0, 4) == 'Err:') {
          $this->debuglog("Problem during getUserBackupSet() for $username");
          $this->error = $result;

          return false;
      } else {
          return $this->xmlToArray($result);
      }
   }

   // Run an API query against OBS
   public function __runQuery($url)
   {

      // If this URL already has a query string
      if (strstr($url, '?')) {
          $url .= '&SysUser='.$this->server_user.'&SysPwd='.$this->server_pass;
      } else {
          $url .= '?SysUser='.$this->server_user.'&SysPwd='.$this->server_pass;
      }

      // Generate HTTP headers for this request
      $headers = 'GET '.$url." HTTP/1.1\r\n";
       $headers .= 'Host: '.$this->server_name."\r\n";
       $headers .= "Connection: close\r\n";
       $headers .= "\r\n\r\n";

       $this->debuglog('Attempting connection to '.$this->server_name.' on port '.$this->server_port);

      // Try to connect to the server
      if (!($fp = @fsockopen($this->server_name, $this->server_port, $errno, $errstr, 30))) {
          $this->debuglog('Connection failed. Error: '.$errstr.'('.$errno.')');

          return 'Err: ConnectionFail';
      }

      // Drop the HTTP headers to the server
      $this->debuglog('Sending HTTP headers to server');
       fwrite($fp, $headers);

      // Keep getting data back
      $this->debuglog('Reading data from server');
       while (!feof($fp)) {
           $data = fgets($fp, 128);
           $result .= $data;
       }

      // Was there any data
      if (!$result) {
          $this->debuglog('Server sent empty response');

          return 'Err: NoDataReceived';
      }

       $this->debuglog('Got all data from server');

      // Strip the headers from the reply
      $b_start = strpos($result, "\r\n\r\n");
       $result = trim(substr($result, $b_start));

      // Was there an error?
      if (substr($result, 0, 5) == '<err>') {
          $this->debuglog('Server sent error: '.$result);

          return 'Err: '.$result;
      }

      // Return whatever the server said
      return $result;
   }

   // Convert XML to an array
   public function xmlToArray($xml)
   {
       $xmlparse = &new ParseXML();
       $data = $xmlparse->GetXMLDataTree($xml);

       return $data;
   }

   // Debug logging
   public function debuglog($message)
   {
       if ($this->debug) {
           printf("%s\n", $message);
       }
   }
}

?>
