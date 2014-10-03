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
            $content = scandir($this->migrationsDir, SCANDIR_SORT_DESCENDING);
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
     * Increases a version string by its smallest increment (0.0.2 -> 0.0.3)
     *
     * @param string $version
     * @return string
     */
    private function increaseVersion($version)
    {
        $v = explode('.', $version);
        $v[count($v) - 1]++;
        return implode('.', $v);
    }

    public function run(&$output = null)
    {
        if ($output != null) {
            $output .= "\nRunning Caribou MySQL migration";
        }

        $versionFile = ".caribou";
        $currentVersion = "";
        if (is_file($versionFile)) {
            $currentVersion = trim(file_get_contents($versionFile));
        }

        if (!is_dir($this->migrationsDir)) {
            // Nothing to do
            if ($output == null) {
                exit(0);
            } else {
                return;
            }
        }

        // apply migrations one after the other
        $content = scandir($this->migrationsDir, SCANDIR_SORT_ASCENDING);

        $version = $currentVersion;
        foreach ($content as $c) {
            $versionDir = $this->migrationsDir . DIRECTORY_SEPARATOR . $c;
            if (is_dir($versionDir) && $c != "." && $c != ".." && $currentVersion < $c) {

                if ($output != null) {
                    if ($version != "") {
                        $output .= "\n$version -> $c";
                    } else {
                        $output .= "\n$c";
                    }
                }

                $migration = new Migration($c);
                $migration->migrate();
                $version = $c;
            }
        }

        if ($version != $currentVersion) {
            if ($output != null) {
                $output .= "\nMigrated";
                if ($currentVersion != "") {
                    $output .= " from " . $currentVersion;
                }
                $output .= "to $version";
            }
            file_put_contents($versionFile, $version);
        } else {
            if ($output != null) {
                $output .= "\nNothing to migrate";
            }
        }
    }
}