<?php

/*
  +------------------------------------------------------------------------+
  | Caribou                                                            |
  +------------------------------------------------------------------------+
  | Copyright (c) 2014 natronite                                 |
  +------------------------------------------------------------------------+
  | This source file is subject to the New BSD License that is bundled     |
  | with this package in the file LICENSE                                  |
  |                                                                        |
  | If you did not receive a copy of the license and are unable to         |
  | obtain it through the world-wide-web, please send an email             |
  | to natronite@gmail.com so I can send you a copy immediately.           |
  +------------------------------------------------------------------------+
  | Authors: Nate Maegli <natronite@gmail.com>                             |
  +------------------------------------------------------------------------+
*/

namespace Natronite\Caribou;

use Natronite\Caribou\Controller\Migration;
use Natronite\Caribou\Util\Connection;
use Natronite\Caribou\Util\Generator;
use Natronite\Caribou\Util\Loader;

class Caribou
{
    private $migrationsDir;

    function __construct($config, $migrationsDir)
    {
        $this->migrationsDir = $migrationsDir;

        $mysqli = new \mysqli($config['host'], $config['username'], $config['password'], $config['dbname']);
        if ($mysqli->connect_errno) {
            throw new \Exception("Failed to connect to MySQL: " . $mysqli->connect_error);
        }

        Connection::setMysqli($mysqli);
        Loader::setMigrationsDir($migrationsDir);
    }

    public function generate()
    {
        echo "Generating Caribou MySQL migration\n";

        $version = "0.0.0";
        if (!is_dir($this->migrationsDir)) {
            echo "Creating migrations directory\n";
            mkdir($this->migrationsDir);
        } else {
            $content = scandir($this->migrationsDir);
            natsort($content);
            $content = array_reverse($content);
            foreach ($content as $c) {
                if (is_dir($this->migrationsDir . DIRECTORY_SEPARATOR . $c) && $c != "." && $c != "..") {
                    $version = $this->increaseVersion($c);
                    break;
                }
            }
        }

        Generator::generateVersion($version, $this->migrationsDir);
    }

    /**
     * Increases a version string by its smallest increment (0.0.2 -> 0.0.3, 0.0.9 -> 0.1.0)
     *
     * @param string $version
     * @return string
     */
    private function increaseVersion($version)
    {
        $v = explode('.', $version);
        $lastIndex = count($v) - 1;
        for ($index = $lastIndex; $index >= 0; $index = $index - 1) {
            $value = $v[$index];
            if ($index != 0 && $value >= 9) {
                $v[$index] = 0;
                $v[$index - 1]++;
            } else {
                if ($index == $lastIndex) {
                    $v[$index] = $value + 1;
                }
            }
        }
        return implode('.', $v);
    }

    public function run(array &$output = null)
    {
        if (isset($output)) {
            $output[] = "Running Caribou MySQL migration";
        }

        //create db_migration table if it doesn't exist
        $result = Connection::query("CREATE TABLE IF NOT EXISTS `db_migration`
          ( `db_migration_id` INT(11) NOT NULL AUTO_INCREMENT , `from` VARCHAR(5) NOT NULL ,
          `to` VARCHAR(5) NOT NULL , `status` VARCHAR(10) NOT NULL ,
          `timestamp_created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , `timestamp_edited` DATETIME NULL DEFAULT NULL ,
          PRIMARY KEY (`db_migration_id`)) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_bin");

        if($result === false){
          die("Couldn't create db migration table.");
        }

        //get current version of the database
        $result = Connection::query("SELECT * FROM `db_migration` WHERE 1 ORDER BY `db_migration_id` DESC LIMIT 1");
        if($result->num_rows == 0){
          $currentVersion = '0.0.0';
        }
        else{
          $lastMigration = $result->fetch_assoc();
          $currentVersion = $lastMigration['to'];
          if($lastMigration['status'] !== 'migrated'){
            // Nothing to do
            if (!isset($output)) {
                exit(0);
            } else {
                $output[] = "The last db_migration isn't finished migrating!";
                return;
            }
          }
        }

        if (!is_dir($this->migrationsDir)) {
            // Nothing to do
            if (!isset($output)) {
                exit(0);
            } else {
                return;
            }
        }

        // apply migrations one after the other
        $content = scandir($this->migrationsDir, SCANDIR_SORT_ASCENDING);
        natsort($content);

        $from = $currentVersion;
        $to = array_pop((array_slice($content, -1)));

        if($from === $to){
          //nothing to do
          if (!isset($output)) {
              exit(0);
          } else {
              $output[] = "Nothing to migrate";
              return;
          }
        }

        //insert current db migration into db_migration table within the database
        $result = Connection::query("INSERT INTO `db_migration`(`from`, `to`, `status`)
        VALUES ('".$from."', '".$to."', 'migrating')");
        if($result === false){
          die("Couldn't insert db migration into db_migration table");
        }
        $dbMigrationId = Connection::getInsertId();

        //cycle trough migrating and migrate database.
        $version = $currentVersion;

        foreach ($content as $c) {
            $versionDir = $this->migrationsDir . DIRECTORY_SEPARATOR . $c;
            if (is_dir($versionDir) && $c != "." && $c != ".." && strnatcmp($currentVersion, $c) < 0) {

                if (isset($output)) {
                    if ($version != "") {
                        $output[] = "$version -> $c";
                        echo "$version -> $c\n";
                    } else {
                        echo "$c\n";
                        $output[] = "$c";
                    }
                }

                $migration = new Migration($c);
                $migration->migrate();
                $version = $c;
            }
        }

        if (isset($output)) {
            $m = "Migrated";
            $m .= " from " . $from;
            $m .= " to $to";

            $output[] = $m;
        }

        //update status to migrated
        $result = Connection::query("UPDATE `db_migration`
          SET `status` = 'migrated', `timestamp_edited` = now() WHERE `db_migration_id` = '".$dbMigrationId."'");
        if($result === false){
          die("Couldn't update db_migration entry");
        }
    }
}
