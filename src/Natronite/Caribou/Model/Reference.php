<?php
/**
 * Created by IntelliJ IDEA.
 * User: natronite
 * Date: 19/07/14
 * Time: 23:38
 */

namespace Natronite\Caribou\Model;


class Reference implements Descriptor
{

    /** @var  string */
    private $name;

    /** @var  array */
    private $columns = [];

    /** @var  string */
    private $referencedTable;

    /** @var  array */
    private $referencedColumns = [];

    /** @var  string */
    private $updateRule;

    /** @var  string */
    private $deleteRule;

    function __construct($name, $columns, $referencedTable, $referencedColumns, $updateRule = null, $deleteRule = null)
    {
        $this->columns = $columns;
        $this->name = $name;
        $this->referencedColumns = $referencedColumns;
        $this->referencedTable = $referencedTable;

        if ($updateRule != "RESTRICT") {
            $this->updateRule = $updateRule;
        }

        if ($deleteRule != "RESTRICT") {
            $this->deleteRule = $deleteRule;
        }
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @return array
     */
    public function getReferencedColumns()
    {
        return $this->referencedColumns;
    }

    /**
     * @return string
     */
    public function getDeleteRule()
    {
        return $this->deleteRule;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getReferencedTable()
    {
        return $this->referencedTable;
    }

    /**
     * @return string
     */
    public function getUpdateRule()
    {
        return $this->updateRule;
    }

    /**
     * @return string The sql statement to create the object
     */
    public function getCreateSql()
    {
        $query = "ADD CONSTRAINT `" . $this->name . "` FOREIGN KEY ";
        $query .= "(`" . implode("`, `", $this->columns) . "`)";
        $query .= " REFERENCES `" . $this->referencedTable . "` (`" . implode("`, `", $this->referencedColumns) . "`)";

        if (isset($this->deleteRule)) {
            $query .= " ON DELETE " . $this->deleteRule;
        }

        if (isset($this->updateRule)) {
            $query .= " ON UPDATE " . $this->updateRule;
        }

        return $query;
    }

}