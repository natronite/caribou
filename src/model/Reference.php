<?php
/**
 * Created by IntelliJ IDEA.
 * User: natronite
 * Date: 19/07/14
 * Time: 23:38
 */

namespace Natronite\Caribou\Model;


class Reference
{

    /** @var  string */
    private $name;

    /** @var  string */
    private $column;

    /** @var  string */
    private $referencedTable;

    /** @var  string */
    private $referencedColumn;

    /** @var  string */
    private $updateRule;

    /** @var  string */
    private $deleteRule;

    function __construct($name, $column, $referencedColumn, $referencedTable, $updateRule = null, $deleteRule = null)
    {
        $this->column = $column;
        $this->name = $name;
        $this->referencedColumn = $referencedColumn;
        $this->referencedTable = $referencedTable;

        if ($updateRule != "RESTRICT") {
            $this->updateRule = $updateRule;
        }

        if ($deleteRule != "RESTRICT") {
            $this->deleteRule = $deleteRule;
        }
    }

    /**
     * @return string
     */
    public function getColumn()
    {
        return $this->column;
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
    public function getReferencedColumn()
    {
        return $this->referencedColumn;
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


}