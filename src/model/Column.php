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

class Column
{
    /** @var  string */
    private $name;

    /** @var  string */
    private $type;

    /** @var  string */
    private $default;

    /** @var  string */
    private $length;

    /** @var  string */
    private $after;

    /** @var bool */
    private $notNull = false;

    /** @var  bool */
    private $first = false;

    function __construct($name, $definition, $sql = null)
    {
        $this->name = $name;

        if (array_key_exists('after', $definition)) {
            $this->after = $definition['after'];
        }
        if (array_key_exists('default', $definition)) {
            $this->default = $definition['default'];
        }
        if (array_key_exists('first', $definition)) {
            $this->first = $definition['first'];
        }
        if (array_key_exists('length', $definition)) {
            $this->length = $definition['length'];
        }

        if (array_key_exists('notNull', $definition)) {
            $this->notNull = $definition['notNull'];
        }
        if (array_key_exists('type', $definition)) {
            $this->type = $definition['type'];
        }
    }


    public static function fromSQL($sql)
    {
        $sql = trim(trim($sql), ',');

        preg_match(
            "/^`(?<name>.*)` (?<type>\w*)(?<length>\(.+\))?\s?(?<notNull>NOT NULL)?.?(?:DEFAULT (?<default>'?\w*'?))?/",
            $sql,
            $matches
        );

        if (array_key_exists('notNull', $matches)) {
            $matches['notNull'] = true;
        } else {
            $matches['notNull'] = true;
        }

        if (array_key_exists('length', $matches) && $matches['length'] == "") {
            unset($matches['length']);
        }

        $column = new Column($matches['name'], $matches, $sql);
        return $column;
    }

    /**
     * @param $old
     * @param $new
     * @return bool|string
     */
    public static function computeAlter(Column $old, Column $new)
    {
        if ($old->getSql() == $new->getSql()) {
            return false;
        }
        return "MODIFY COLUMN " . $new->getSql();
    }

    /**
     * @return string
     */
    public function getSql()
    {
        $sql = "`" . $this->name . "` " . $this->type;

        if (isset($this->length)) {
            $sql .= $this->length;
        }

        if ($this->notNull) {
            $sql .= " NOT NULL";
        }

        if (isset($this->default)) {
            $sql .= " DEFAULT " . $this->default;
        }

        return $sql;
    }

    public function getDescription()
    {
        $desc = get_object_vars($this);
        unset($desc['sql']);
        unset($desc['name']);

        $result = [];
        foreach ($desc as $name => $col) {
            if (isset($col) && !is_bool($col)) {
                $result[$name] = $col;
                continue;
            }
            if (is_bool($col) && $col === true) {
                $result[$name] = $col;
                continue;
            }
        }

        return $result;
    }

    /**
     * @return boolean
     */
    public function isFirst()
    {
        return $this->first;
    }

    /**
     * @param boolean $first
     */
    public function setFirst($first)
    {
        $this->first = $first;
    }

    /**
     * @return boolean
     */
    public function isNotNull()
    {
        return $this->notNull;
    }

    /**
     * @param boolean $notNull
     */
    public function setNotNull($notNull)
    {
        $this->notNull = $notNull;
    }

    /**
     * @return boolean
     */
    public function getAfter()
    {
        return $this->after;
    }

    /**
     * @param boolean $after
     */
    public function setAfter($after)
    {
        $this->after = $after;
    }

    /**
     * @return boolean
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param string $default
     */
    public function setDefault($default)
    {
        $this->default = $default;
    }

    /**
     * @return boolean
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param mixed $length
     */
    public function setLength($length)
    {
        $this->length = $length;
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
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    public function __toString()
    {
        return $this->getSql();
    }
}  