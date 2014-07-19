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

class Table
{

    private $name;
    private $columns = [];
    private $sql;

    function __construct($name, array $columns)
    {
        $this->name = $name;
        $this->columns = $columns;
    }

    public static function fromSQL($sql)
    {
        $table = null;

        preg_match('|^CREATE TABLE `(.*)`|', $sql, $matches);

        if (count($matches) > 1) {
            $name = $matches[1];
            $columns = [];

            $lines = explode(PHP_EOL, $sql);
            /** @var Column $previousColumn */
            $previousColumn = false;
            foreach ($lines as $line) {
                preg_match('|^`(.*)`|', trim($line), $matches);
                if (count($matches) > 1) {
                    $column = Column::fromSQL($line);
                    if ($previousColumn) {
                        $column->setAfter($previousColumn->getName());
                    } else {
                        $column->setFirst(true);
                    }
                    $columns[$matches[1]] = $column;
                    $previousColumn = $column;
                }
            }
            $table = new Table($name, $columns);
            $table->setSql($sql);
        } else {
            throw new \Exception("Can't read mysql create syntax.\n" . $sql);
        }
        return $table;
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

        if (!empty($alterLines)) {
            return "ALTER TABLE `" . $new->getName() . "`\n\t" . implode(",\n\t", $alterLines);
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
     * @return mixed
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
     * @param $name
     * @param Column $column
     */
    public function addColumn($name, Column $column)
    {
        $this->columns[$name] = $column;
    }

    /**
     * @return string
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * @param string $sql
     */
    public function setSql($sql)
    {
        $this->sql = $sql;
    }

}