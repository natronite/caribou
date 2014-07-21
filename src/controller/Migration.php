<?php
/**
 * 
 * @author: Nathanaël Mägli <nate@sidefyn.ch>
 */
 

namespace Natronite\Caribou\Controller;


use Natronite\Caribou\Utils\Connection;
use Natronite\Caribou\Utils\Loader;

class Migration {

    /** @var  string */
private $version;

    /** @var  array */
    private $tables = [];

    function __construct($version)
    {
        $this->version = $version;
    }

    public function migrate(){
        $dir = Loader::dirForVersion($this->version);
        $files = glob($dir . "*.php");

        $tableClasses = array_map(
            function ($element) {
                return basename($element, ".php");
            },
            $files
        );


        // Load tables
        $tables = [];
        foreach ($tableClasses as $table) {
            Loader::loadModelVersion($table, $this->version);
            $class = Loader::classNameForVersion($table, $this->version);

            $this->tables = new $class;
        }

        $current = Connection::getTableNames();
        $removed = array_diff($current, $tables);
        Connection::begin();

        $this->doTables();
        $this->dropIndexes();
        $this->dropReferences();

        $this->createIndexes();
        $this->createReferences();

        // Delete removed tables
        Connection::dropTables($removed);

        Connection::commit();
    }

    protected function doTables(){
        /** @var TableMigration $table */
        foreach($this->tables as $table){
            $table->migrateTable();
        }
    }

    protected function createIndexes(){

    }

    protected function createReferences(){

    }

    /**
     * Drops unneeded indexes for every table
     */
    protected function dropIndexes(){
        /** @var TableMigration $table */
        foreach($this->tables as $table){
            $table->dropIndexes();
        }
    }

    protected function dropReferences(){

    }
}