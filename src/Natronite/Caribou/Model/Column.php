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
    private $notNull = false;

    /** @var  bool */
    private $first = false;

    /** @var  string */
    private $comment;

    function __construct($name, $definition)
    {
        $this->name = $name;

        if (array_key_exists('first', $definition)) {
            $this->first = $definition['first'];
        }
        if (array_key_exists('after', $definition)) {
            $this->after = $definition['after'];
        }
        if (array_key_exists('Default', $definition) && $definition['Default'] !== '') {
            $this->default = $definition['Default'];
        }
        if (array_key_exists('Null', $definition)) {
            if ($definition['Null'] === 'NO' || $definition['Null'] === false) {
                $this->notNull = true;
            }
        }
        if (array_key_exists('Type', $definition)) {
            $this->type = $definition['Type'];
        }
        if (array_key_exists('Extra', $definition) && $definition['Extra'] != "") {
            $this->extra = strtoupper($definition['Extra']);
        }
        if (array_key_exists('Comment', $definition) && $definition['Comment'] != "") {
            $this->comment = $definition['Comment'];
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
        if ($this->notNull === true) {
            $query .= " NOT NULL";
        }
        if (isset($this->default)) {
            if (is_numeric($this->default) || $this->default === 'CURRENT_TIMESTAMP' || $this->default === 'NULL') {
                $query .= " DEFAULT " . $this->default;
            } else {
                $query .= " DEFAULT '" . $this->default . "'";
            }
        }
        if (isset($this->extra)) {
            $query .= " " . $this->extra;
        }

        if (isset($this->comment)) {
            $query .= " COMMENT '" . $this->comment . "'";
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
        if ($this->notNull) {
            $definition['Null'] = false;
        }
        if (isset($this->type)) {
            $definition['Type'] = $this->type;
        }
        if (isset($this->extra)) {
            $definition['Extra'] = $this->extra;
        }
        if (isset($this->comment)) {
            $definition['Comment'] = $this->comment;
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
