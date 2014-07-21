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


use Natronite\Caribou\Controller\TableMigration;
use Natronite\Caribou\Controller\Template;
use Natronite\Caribou\Model\Column;
use Natronite\Caribou\Model\Index;
use Natronite\Caribou\Model\Reference;
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

        /** @var Table $table */
        foreach ($tables as $table) {
            $this->generateTable($version, $table);
        }
    }

    private function generateTable($version, Table $table)
    {
        $tableName = Loader::classNameForVersion($table->getName(), $version);
        $template = new Template('table');
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
        $template->set('engine', $table->getEngine());
        $template->set('charset', $table->getCharset());
        $template->set('collation', $table->getCollation());
        if ($table->getAutoIncrement() != null) {
            $template->set(
                'autoIncrement',
                "\n\t\t\t" . '$this->setAutoIncrement("' . $table->getAutoIncrement() . '");'
            );
        } else {
            $template->set('autoIncrement', "");
        }

        /** @var Index $index */
        $indexes = [];
        foreach ($table->getIndexes() as $index) {
            $c = $linePrefix
                . "new Index("
                . "\"" . $index->getName() . "\","
                . "['" . implode('\', \'', $index->getColumns()) . "']";
            if ($index->isUnique()) {
                $c .= ", true";
            }
            $c .= ")";
            $indexes[] = $c;
        }
        $template->set('indexes', "\n" . implode(",\n", $indexes));

        /** @var Reference $reference */
        $references = [];
        foreach ($table->getReferences() as $reference) {
            $r = $linePrefix
                . "new Reference('"
                . $reference->getName() . "', '"
                . $reference->getColumn() . "', '"
                . $reference->getReferencedTable() . "', '"
                . $reference->getReferencedColumn() . "'";

            if ($reference->getUpdateRule()) {
                $r .= ", '" . $reference->getUpdateRule() . "'";
            }
            if ($reference->getDeleteRule()) {
                $r .= ", '" . $reference->getDeleteRule() . "'";
            }
            $r .= ")";

            $references[] = $r;
        }

        if (count($references)) {
            $template->set(
                'references',
                "\n\t\t\t" . '$table->setReferences(' . "\n" . $linePrefix . implode(",\n", $references) . "\n);"
            );
        } else {
            $template->set('references', "");
        }


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
            $currentVersion = trim(file_get_contents($versionFile));
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
                if ($version != "") {
                    echo "$version -> $c\n";
                } else {
                    echo "$c\n";
                }
                $this->toVersion($c);
                $version = $c;
            }
        }

        if ($version != $currentVersion) {
            echo "Migrated";
            if ($currentVersion != "") {
                echo " from " . $currentVersion;
            }
            echo " to $version\n";
            file_put_contents($versionFile, $version);
        } else {
            echo "Nothing to migrate\n";
        }
    }

    public function toVersion($version)
    {
        $dir = Loader::dirForVersion($version);
        $files = glob($dir . "*.php");

        $tables = array_map(
            function ($element) {
                return basename($element, ".php");
            },
            $files
        );

        $current = Connection::getTableNames();
        Connection::begin();

        // Alter tables
        foreach ($tables as $table) {
            Loader::loadModelVersion($table, $version);
            $class = Loader::classNameForVersion($table, $version);

            /** @var TableMigration $model */
            $model = new $class;
            $model->morph();
        }

        // Delete removed tables
        Connection::dropTables(array_diff($current, $tables));

        Connection::commit();
    }


}