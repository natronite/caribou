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

namespace Natronite\Caribou;


class Caribou
{
    private $migrationsDir;
    private $mysqli;

    function __construct($config, $migrationsDir)
    {
        $this->migrationsDir = $migrationsDir;

        $this->mysqli = new \mysqli($config['host'], $config['username'], $config['password'], $config['dbname']);
        if ($this->mysqli->connect_errno) {
            throw new \Exception("Failed to connect to MySQL: " . $this->mysqli->connect_error);
        }
    }

    public function generate()
    {
        echo "Running Caribou MySQL migration";
    }

    public function run(){
        echo "Running Caribou MySQL migration";
    }
}