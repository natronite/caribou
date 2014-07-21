<?php
/**
 * Created by IntelliJ IDEA.
 * User: natronite
 * Date: 19/07/14
 * Time: 23:38
 */

namespace Natronite\Caribou\Model;


class Index implements Descriptor
{

    /** @var  string */
    private $name;

    /** @var  array */
    private $columns;

    /** @var  bool */
    private $unique;

    /** @var  string */
    private $type;

    function __construct($name, $columns, $unique = false, $type = null)
    {
        $this->columns = $columns;
        $this->name = $name;
        $this->unique = $unique;
        if($type != "BTREE") {
            $this->type = $type;
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
     * @param array $columns
     */
    public function setColumns($columns)
    {
        $this->columns = $columns;
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
     * @return boolean
     */
    public function isUnique()
    {
        return $this->unique;
    }

    /**
     * @param boolean $unique
     */
    public function setUnique($unique)
    {
        $this->unique = $unique;
    }

    /**
     * @return string The sql statement to create the object
     */
    public function getCreateSql()
    {
        $type = "";
        if($this->unique){
            $type .= " UNIQUE";
        }
        if($this->type !== null){
            $type .= " " . $this->type;
        }
        $query = "ADD" . $type ." KEY `" . $this->name . "` ";
        if(isset($this->type)){
           $query .= "";
        }
        $query .= "(" . implode(",", $this->columns) . ")";

        return $query;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
} 