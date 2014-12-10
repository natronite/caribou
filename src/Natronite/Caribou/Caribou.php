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

    public function run(array &$output = null)
    {
        if (isset($output)) {
            $output[] = "Running Caribou MySQL migration";
        }

        $versionFile = ".caribou";
        $currentVersion = "";
        if (is_file($versionFile)) {
            $currentVersion = trim(file_get_contents($versionFile));
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

        if ($version != $currentVersion) {
            if (isset($output)) {
                $m = "Migrated";
                if ($currentVersion != "") {
                    $m  .= " from " . $currentVersion;
                }
                $m  .= " to $version";

                $output[] = $m;
            }
            file_put_contents($versionFile, $version);
        } else {
            if (isset($output)) {
                $output[] = "Nothing to migrate";
            }
        }
    }
}