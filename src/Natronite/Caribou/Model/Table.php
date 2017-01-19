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

namespace Natronite\Caribou\Model;

class Table implements Descriptor
{

    /** @var  string */
    private $name;

    /** @var array */
    private $columns = [];

    /** @var array */
    private $indexes = [];

    /** @var array */
    private $references = [];

    /** @var  string */
    private $engine;

    /** @var  string */
    private $collate;

    /** @var  string */
    private $autoIncrement;

    function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @param Table $old
     * @param Table $new
     * @return bool|string
     */
    public static function computeAlter(Table $old, Table $new)
    {
        $oldColumnNames = array_keys($old->getColumns());
        $newColumnNames = array_keys($new->getColumns());

        $alterLines = [];

        // Check for changed columns
        $possiblyChangedColumnNames = array_intersect($oldColumnNames, $newColumnNames);
        foreach ($possiblyChangedColumnNames as $possiblyChangedColumnName) {
            $oldColumn = $old->getColumn($possiblyChangedColumnName);
            $newColumn = $new->getColumn($possiblyChangedColumnName);

            if ($oldColumn && $newColumn) {
                $alter = Column::computeAlter($oldColumn, $newColumn);
                if ($alter) {
                    $alterLines[] = $alter;
                }
            }
        }

        // check if columns have been added
        $addedColumnNames = array_diff($newColumnNames, $oldColumnNames);
        foreach ($addedColumnNames as $added) {
            $column = $new->getColumn($added);
            $query = "ADD COLUMN " . $column;
            if ($column->isFirst()) {
                $query .= " FIRST";
            } elseif ($column->getAfter() !== false) {
                $query .= " AFTER `" . $column->getAfter() . "`";
            }
            $alterLines[] = $query;
        }

        // check if columns have been removed
        $removedColumnNames = array_diff($oldColumnNames, $newColumnNames);
        foreach ($removedColumnNames as $removedColumnName) {
            $alterLines[] = "DROP COLUMN `" . $removedColumnName . "`";
        }

        $tableEngineChanged = $old->getEngine() !== $new->getEngine();

        if (!empty($alterLines) || $tableEngineChanged) {
            $sql =  "ALTER TABLE `" . $new->getName() . "`";
            if ($tableEngineChanged) {
                $sql .= " ENGINE=" . $new->getEngine();
            }
            if (!empty($alterLines)) {
                $sql .= "\n\t" . implode(",\n\t", $alterLines);
            }
            return $sql;
        }

        return false;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @param array $columns
     */
    public function setColumns($columns)
    {
        $this->columns = $columns;
    }

    /**
     * @param Column $column
     */
    public function addColumn(Column $column)
    {
        $this->columns[$column->getName()] = $column;
    }

    /**
     * @param string $name The name of the column to get
     * @return Column|false The requested Column or false
     */
    public function getColumn($name)
    {
        if (array_key_exists($name, $this->columns)) {
            return $this->columns[$name];
        }
        return false;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getAutoIncrement()
    {
        return $this->autoIncrement;
    }

    /**
     * @param string $autoIncrement
     */
    public function setAutoIncrement($autoIncrement)
    {
        $this->autoIncrement = $autoIncrement;
    }

    /**
     * @return string
     */
    public function getCollate()
    {
        return $this->collate;
    }

    /**
     * @param string $collate
     */
    public function setCollate($collate)
    {
        $this->collate = $collate;
    }

    /**
     * @return string
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * @param string $engine
     */
    public function setEngine($engine)
    {
        $this->engine = $engine;
    }

    /**
     * @return array
     */
    public function getIndexes()
    {
        return $this->indexes;
    }

    /**
     * @param array $indexes
     */
    public function setIndexes($indexes)
    {
        $this->indexes = $indexes;
    }

    /**
     * @return array
     */
    public function getReferences()
    {
        return $this->references;
    }

    /**
     * @param array $references
     */
    public function setReferences($references)
    {
        $this->references = $references;
    }


    public function getPrimary()
    {
        /** @var Index $index*/
        foreach ($this->indexes as $index) {
            if ($index->getName() == "PRIMARY") {
                return $index->getColumns();
            }
        }
        return false;
    }

    /**
     * @return string The sql statement to create the object
     */
    public function getCreateSql()
    {
        $query = "CREATE TABLE `" . $this->name . "` (";

        $columns = array_map(
            function ($val) {
                /** @var $val Column */
                return $val->getCreateSql();
            },
            $this->columns
        );

        $query .= "\n\t" . implode(",\n\t", $columns);

        if ($this->getPrimary()) {
            $query .= ",\n\tPRIMARY KEY (" . implode(", ", $this->getPrimary()) . ")";
        }

        $query .= "\n) ENGINE=" . $this->engine;

        if (isset($this->autoIncrement)) {
            $query .= " AUTO_INCREMENT=" . $this->autoIncrement;
        }

        if (isset($this->collate)) {
            $query .= " COLLATE=" . $this->collate;
        }

        return $query;
    }
}
