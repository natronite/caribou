<?php
/**
 * Created by IntelliJ IDEA.
 * User: natronite
 * Date: 19/07/14
 * Time: 21:34
 */

namespace Natronite\Caribou\Utils;

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
                $tables[$c[0]] = Table::fromSQL($c[1]);
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