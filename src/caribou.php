<?php

/*
  +------------------------------------------------------------------------+
  | Caribou                                             		           |
  +------------------------------------------------------------------------+
  | Copyright (c) 2014 natronite     					                   |
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
use Natronite\Caribou\Controller\Template;
use Natronite\Caribou\Model\Column;
use Natronite\Caribou\Model\Table;
use Natronite\Caribou\Utils\Connection;
use Natronite\Caribou\Utils\Loader;

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
        echo "Running Caribou MySQL migration\n";

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

        $this->generateVersion($version);
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

    private function generateVersion($version)
    {
        // create dir
        mkdir($this->migrationsDir . DIRECTORY_SEPARATOR . $version);
        // get tables
        $tables = Connection::getTables();

        $template = new Template('migration');
        $template->set('className', Loader::classNameForVersion('Migration', $version));

        $tableNames = [];
        /** @var Table $table */
        foreach ($tables as $table) {
            $tableNames[] = "'" . $table->getName() . "'";
            $this->generateTable($version, $table);
        }

        $template->set('tableNames', implode(", ", $tableNames));
        $template->set('version', $version);


        $file = Loader::fileForVersion('Migration', $version);
        file_put_contents($file, $template->getContent());
    }

    private function generateTable($version, Table $table)
    {
        $tableName = Loader::classNameForVersion($table->getName(), $version);
        $template = new Template('model');
        $template->set('className', $tableName);
        $template->set('tableName', $table->getName());

        $columns = [];
        $linePrefix = "\t\t";
        /** @var Column $column */
        foreach ($table->getColumns() as $column) {
            $c = $linePrefix;
            $c .= "'" . $column->getName() . "' => new Column(";
            $c .= "\n\t" . $linePrefix . "\"" . $column->getName() . "\",\n";
            $c .= $this->varExport($column->getDescription(), $linePrefix . "\t");
            $c .= "\n" . $linePrefix . ")";
            $columns[] = $c;
        }

        $template->set('columns', "\n" . implode(",\n", $columns));

        $template->set('create', $table->getSql());

        $file = Loader::fileForVersion($table->getName(), $version);
        file_put_contents($file, $template->getContent());
    }

    private function varExport(array $array, $linePrefix)
    {
        $lines = explode(PHP_EOL, var_export($array, true));
        $result = implode(PHP_EOL . $linePrefix, $lines);
        return $linePrefix . $result;
    }

    public function run()
    {
        echo "Running Caribou MySQL migration\n";

        $versionFile = ".caribou";
        $currentVersion = "";
        if (is_file($versionFile)) {
            $currentVersion = file_get_contents($versionFile);
        }

        if (!is_dir($this->migrationsDir)) {
            // Nothing to do
            exit(0);
        }

        // apply migrations one after the other
        $content = scandir($this->migrationsDir, SCANDIR_SORT_ASCENDING);

        $version = $currentVersion;
        foreach ($content as $c) {
            $versionDir = $this->migrationsDir . DIRECTORY_SEPARATOR . $c;
            if (is_dir($versionDir) && $c != "." && $c != ".." && $currentVersion < $c) {
                echo "$version -> $c\n";
                Loader::loadMigrationVersion($c);
                $class = Loader::classNameForVersion('Migration', $c);
                /** @var Migration $migration */
                $migration = new $class;
                $migration->run();
                $version = $c;
            }
        }

        if ($version != $currentVersion) {
            echo "Migrated from $currentVersion to $version\n";
            file_put_contents($versionFile, $version);
        } else {
            echo "Nothing to migrate\nls -al
            ";
        }
    }

}