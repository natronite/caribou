<?php
/**
 *
 * @author: Nathanaël Mägli <nate@sidefyn.ch>
 */


namespace Natronite\Caribou\Controller;


use Natronite\Caribou\Util\Connection;
use Natronite\Caribou\Util\Loader;

class Migration
{

    /** @var  string */
    private $version;

    /** @var  array */
    private $tables = [];

    function __construct($version)
    {
        $this->version = $version;
    }

    public function migrate()
    {
        $dir = Loader::dirForVersion($this->version);
        $files = glob($dir . "*.php");

        $tableClasses = array_map(
            function ($element) {
                return basename($element, ".php");
            },
            $files
        );

        // Load tables
        foreach ($tableClasses as $table) {
            Loader::loadModelVersion($table, $this->version);
            $class = Loader::classNameForVersion($table, $this->version);
            $this->tables[] = new $class;
        }

        $current = Connection::getTableNames();
        $removed = array_diff($current, $tableClasses);

        Connection::begin();

        $this->dropReferences();
        $this->dropIndexes();
        $this->doTables();

        $this->createIndexes();
        $this->createReferences();

        echo "Dropping tables\n";
        Connection::dropTables($removed);

        Connection::commit();
    }

    protected function doTables()
    {
        echo "Migrating indexes\n";
        /** @var TableMigration $table */
        foreach ($this->tables as $table) {
            $table->migrateTable();
        }
    }

    /**
     * Drops unneeded indexes for every table
     */
    protected function dropIndexes()
    {
        echo "Dropping indexes\n";
        /** @var TableMigration $table */
        foreach ($this->tables as $table) {
            $table->dropIndexes();
        }
    }

    protected function dropReferences()
    {
        echo "Dropping references\n";
        /** @var TableMigration $table */
        foreach ($this->tables as $table) {
            $table->dropReferences();
        }
    }

    protected function createIndexes()
    {
        echo "Creating indexes\n";
        /** @var TableMigration $table */
        foreach ($this->tables as $table) {
            $table->createIndexes();
        }
    }

    protected function createReferences()
    {
        echo "Creating references\n";
        /** @var TableMigration $table */
        foreach ($this->tables as $table) {
            $table->createReferences();
        }
    }
}