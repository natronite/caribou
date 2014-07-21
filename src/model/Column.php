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

class Column implements Descriptor
{
    /** @var  string */
    private $name;

    /** @var  string */
    private $type;

    /** @var  string */
    private $default;

    /** @var  string */
    private $after;

    /** @var  string */
    private $extra;

    /** @var bool */
    private $null = true;

    /** @var  bool */
    private $first = false;

    function __construct($name, $definition)
    {
        $this->name = $name;

        if (array_key_exists('first', $definition)) {
            $this->first = $definition['first'];
        }
        if (array_key_exists('after', $definition)) {
            $this->after = $definition['after'];
        }
        if (array_key_exists('Default', $definition) && $definition['Default'] != "") {
            $this->default = $definition['Default'];
        }
        if (array_key_exists('Null', $definition)) {
            $this->null = $definition['Null'] == ("YES" || true) ? true : false;
        }
        if (array_key_exists('Type', $definition)) {
            $this->type = $definition['Type'];
        }
        if (array_key_exists('Extra', $definition) && $definition['Extra'] != "") {
            $this->extra = strtoupper($definition['Extra']);
        }
    }

    /**
     * @return boolean
     */
    public function isFirst()
    {
        return $this->first;
    }

    /**
     * @return string
     */
    public function getAfter()
    {
        return $this->after;
    }

    /**
     * @param $old
     * @param $new
     * @return bool|string
     */
    public static function computeAlter(Column $old, Column $new)
    {
        if ($old->getCreateSql() == $new->getCreateSql()) {
            return false;
        }
        return "MODIFY COLUMN " . $new->getCreateSql();
    }

    /**
     * @return string The sql statement to create the object
     */
    public function getCreateSql()
    {
        $query = "`" . $this->name . "` ";
        $query .= $this->type;
        if (!$this->null) {
            $query .= " NOT NULL";
        }
        if (isset($this->default)) {
            $query .= " DEFAULT " . $this->default;
        }
        if (isset($this->extra)) {
            $query .= " " . $this->extra;
        }

        return $query;
    }

    public function getDescription()
    {
        $definition = [];
        if ($this->first) {
            $definition['first'] = true;
        }
        if (isset($this->after)) {
            $definition['after'] = $this->after;
        }
        if (isset($this->default)) {
            $definition['Default'] = $this->default;
        }
        if (!$this->null) {
            $definition['Null'] = false;
        }
        if (isset($this->type)) {
            $definition['Type'] = $this->type;
        }
        if (isset($this->extra)) {
            $definition['Extra'] = $this->extra;
        }

        return $definition;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    public function __toString()
    {
        return $this->getCreateSql();
    }

}  