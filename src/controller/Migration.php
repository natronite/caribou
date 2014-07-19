<?php
/**
 * Created by IntelliJ IDEA.
 * User: natronite
 * Date: 19/07/14
 * Time: 19:18
 */

namespace Natronite\Caribou\Controller;


use Natronite\Caribou\Utils\Connection;
use Natronite\Caribou\Utils\Loader;

abstract class Migration
{

    private $tableNames;
    private $version;

    /**
     * @param array $tableNames
     */
    public function setTableNames(array $tableNames)
    {
        $this->tableNames = $tableNames;
    }

    public function run($up = true)
    {
        $this->morph();

        if ($up) {
            if (method_exists($this, 'up')) {
                $this->up();
            }
        } else {
            if (method_exists($this, 'down')) {
                $this->down();
            }
        }
    }

    private function morph()
    {
        $current = Connection::getTableNames();

        Connection::begin();

        // Alter tables
        foreach ($this->tableNames as $table) {
            Loader::loadModelVersion($table, $this->version);
            $class = Loader::classNameForVersion($table, $this->version);

            /** @var ModelMigration $model */
            $model = new $class;
            $model->morph();
        }

        // Delete removed tables
        Connection::dropTables(array_diff($current, $this->tableNames));

        Connection::commit();

    }

    /**
     * @param mixed $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }
} 