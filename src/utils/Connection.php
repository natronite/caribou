<?php
/**
 * Created by IntelliJ IDEA.
 * User: natronite
 * Date: 19/07/14
 * Time: 21:34
 */

namespace Natronite\Caribou\Utils;

use Natronite\Caribou\Model\Index;
use Natronite\Caribou\Model\Reference;
use Natronite\Caribou\Model\Table;

class Connection
{
    /** @var  bool */
    static $isTransaction;

    /** @var  \mysqli */
    private static $mysqli;

    /**
     * @param mixed $mysqli
     */
    public static function setMysqli($mysqli)
    {
        self::$mysqli = $mysqli;
    }

    public static function begin()
    {
        self::$mysqli->begin_transaction();
        self::$isTransaction = true;
    }

    public static function commit()
    {
        self::$mysqli->commit();
        self::$isTransaction = false;
    }

    public static function dropTables($tables)
    {
        foreach ($tables as $table) {
            self::query("DROP TABLE `" . $table . "`");
        }
    }

    /**
     * @param $query
     * @return bool|\mysqli_result
     */
    public static function query($query)
    {
        $result = self::$mysqli->query($query);
        if (!$result) {
            echo "MySQL error with " . $query . "\n";
            echo self::$mysqli->error . "\n";
            if (self::$isTransaction) {
                self::rollback();
            }
        }
        return $result;
    }

    public static function rollback()
    {
        self::$mysqli->rollback();
        self::$isTransaction = false;
    }

    /**
     * Retrieves the tables currently in the database
     *
     * @return array An associative array with objects of type Table array('tableName' => 'table', ...)
     */
    public static function getTables()
    {
        $tables = [];

        /** @var \mysqli_result $result */
        $tableNames = self::getTableNames();
        foreach ($tableNames as $tableName) {
            /** @var \mysqli_result $showCreateTable */
            $showCreateTable = self::$mysqli->query("SHOW CREATE TABLE `" . $tableName . "`");

            while ($c = $showCreateTable->fetch_array()) {
                $table = Table::fromSQL($c[1]);
                $options = Connection::getTableStatus($table->getName());
                $table->setCollation($options['collation']);
                $table->setEngine($options['engine']);
                if (array_key_exists('autoIncrement', $options)) {
                    $table->setAutoIncrement($options['autoIncrement']);
                }

                $indexes = Connection::getTableIndices($table->getName());
                $table->setIndexes($indexes);

                $references = Connection::getTableReferences($table->getName());
                $table->setReferences($references);

                $tables[$c[0]] = $table;
            }
        }

        return $tables;
    }

    public static function getTableNames()
    {
        $tables = [];

        /** @var \mysqli_result $result */
        $result = self::$mysqli->query("SHOW TABLES");
        while ($t = $result->fetch_array()) {
            $tables[] = $t[0];
        }

        return $tables;
    }

    public static function getTableStatus($name)
    {
        /** @var \mysqli_result $showCreateTable */
        $showCreateTable = self::$mysqli->query("SHOW TABLE STATUS LIKE '" . $name . "'");

        $c = $showCreateTable->fetch_array();

        $result = [
            'engine' => $c['Engine'],
            'collation' => $c['Collation'],
        ];

        if (isset($c['Auto_increment'])) {
            $result['autoIncrement'] = $c['Auto_increment'];
        }

        return $result;
    }

    public static function getTableIndices($name)
    {
        /** @var \mysqli_result $result */
        $result = self::$mysqli->query("SHOW INDEXES FROM `" . $name . "`");

        $data = [];
        if ($result) {
            while ($t = $result->fetch_array()) {
                $data[$t['Key_name']]['columns'][] = $t['Column_name'];
                $data[$t['Key_name']]['unique'] = !$t['Non_unique'];
            }
        }

        $indexes = [];
        foreach ($data as $key => $index) {
            $indexes[] = new Index($key, $index['columns'], $index['unique']);
        }

        return $indexes;
    }

    public static function getTableReferences($name)
    {
        $references = [];
        $result = self::query("SELECT DATABASE()");
        if ($result) {
            $db = $result->fetch_array();
            $cols = [
                '`u`.CONSTRAINT_NAME',
                '`u`.REFERENCED_TABLE_NAME',
                '`u`.REFERENCED_COLUMN_NAME',
                '`u`.COLUMN_NAME',
                '`r`.UPDATE_RULE',
                '`r`.DELETE_RULE'
            ];
            $query = "SELECT " . implode(", ", $cols) . " FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE as `u`"
                . "JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS as `r` ON `r`.CONSTRAINT_NAME = `u`.CONSTRAINT_NAME"
                . " WHERE `u`.TABLE_NAME='" . $name . "'"
                . " AND `u`.TABLE_SCHEMA='" . $db[0] . "'"
                . " AND `u`.REFERENCED_TABLE_SCHEMA IS NOT NULL";

            $refs = self::query($query);
            if ($refs) {
                while ($r = $refs->fetch_array()) {
                    $references[] = new Reference(
                        $r['CONSTRAINT_NAME'],
                        $r['COLUMN_NAME'],
                        $r['REFERENCED_TABLE_NAME'],
                        $r['REFERENCED_COLUMN_NAME'],
                        $r['UPDATE_RULE'],
                        $r['DELETE_RULE']
                    );
                }
            }
        }
        return $references;
    }

    public static function getTable($name)
    {
        /** @var \mysqli_result $showCreateTable */
        $showCreateTable = self::$mysqli->query("SHOW CREATE TABLE `" . $name . "`");

        $c = $showCreateTable->fetch_array();
        return Table::fromSQL($c[1]);
    }

    public static function tableExists($name)
    {
        $result = self::query("SHOW TABLES LIKE '$name'");
        return $result->num_rows > 0;
    }

} 