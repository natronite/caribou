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
    private $sql;

    /** @var  string */
    private $after;

    /** @var bool */
    private $notNull = false;

    /** @var  bool */
    private $first = false;

    /**
     * @param string $sql the sql string
     */
    function __construct($sql)
    {
        $this->sql = trim(trim($sql), ',');

        preg_match(
            "/^`(?P<name>.*)` (?P<type>\w*)(?P<length>\(.+\))? (?<notNull>NOT NULL)?.?(?:DEFAULT (?<default>'?\w*'?))?/",
            $this->sql,
            $matches
        );

        $this->name = $matches['name'];
        $this->type = $matches['type'];
        $this->notNull = array_key_exists('notNull', $matches);

        if (array_key_exists('length', $matches)) {
            $this->length = $matches['length'];
        } else {
            $this->length = false;
        }

        if (array_key_exists('default', $matches)) {
            $this->default = $matches['default'];
        } else {
            $this->default = false;
        }
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
        return $this->sql;
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
        return $this->sql;
    }
}  