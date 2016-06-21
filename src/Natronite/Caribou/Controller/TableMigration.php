<?php
/**
 * Created by IntelliJ IDEA.
 * User: natronite
 * Date: 19/07/14
 * Time: 19:27
 */

namespace Natronite\Caribou\Controller;

use Natronite\Caribou\Model\Descriptor;
use Natronite\Caribou\Model\Table;
use Natronite\Caribou\Util\Connection;

abstract class TableMigration
{
    /** @var  Table */
    private $table;

    final public function getTableName()
    {
        return $this->table->getName();
    }

    final public function migrateTable()
    {
        // Test if table exists
        if (Connection::tableExists($this->table->getName())) {

            $current = Connection::getTable($this->table->getName());
            $alter = Table::computeAlter($current, $this->table);
            if ($alter) {
                echo "Migrating table " . $this->table->getName() . "\n";
                Connection::query($alter);
            }

        } else {
            // create table
            echo "Creating table " . $this->table->getName() . "\n";
            Connection::query($this->table->getCreateSql());
        }
    }

    final public function createIndexes()
    {
        $current = Connection::getTableIndices($this->table->getName());
        $new = $this->table->getIndexes();

        $this->create($new, $current, $this->table->getName());
    }

    final public function createReferences()
    {
        $current = Connection::getTableReferences($this->table->getName());
        $new = $this->table->getReferences();

        $this->create($new, $current, $this->table->getName());
    }

    final public function dropIndexes()
    {
        $current = Connection::getTableIndices($this->table->getName());
        $new = $this->table->getIndexes();

        $this->drop($current, $new, $this->table->getName(), 'INDEX');
    }

    final public function dropReferences()
    {
        $current = Connection::getTableReferences($this->table->getName());
        $new = $this->table->getReferences();

        $this->drop($current, $new, $this->table->getName(), 'FOREIGN KEY');
    }

    final protected function query($query)
    {
        return Connection::query($query);
    }

    /**
     * @param mixed $table
     */
    final public function setTable($table)
    {
        $this->table = $table;
    }

    private function create(array $array1, array $array2, $tableName)
    {
        //$drop = $this->arrayDiffNamed($array1, $array2);
        $drop = $this->arrayDiffSql($array1, $array2);

        if (!empty($drop)) {
            // Prepare sql statement
            $query = "ALTER TABLE `" . $tableName . "`\n\t";

            /** @var Descriptor $d */
            $statements = [];
            foreach ($drop as $d) {
                $statements[] = $d->getCreateSql();
            }

            $query .= implode(",\n\t", $statements);

            Connection::query($query);
        }
    }

    private function drop(array $array1, array $array2, $tableName, $dropType)
    {
        //$drop = $this->arrayDiffNamed($array1, $array2);
        $drop = $this->arrayDiffSql($array1, $array2);

        if (!empty($drop)) {
            // Prepare sql statement
            $query = "ALTER TABLE `" . $tableName . "`\n\t";

            /** @var Descriptor $d */
            $statements = [];
            foreach ($drop as $d) {
                $statements[] = "DROP $dropType `" . $d->getName() . "`";
            }

            $query .= implode(",\n\t", $statements);

            Connection::query($query);
        }
    }

    private function arrayDiffNamed(array $array1, array $array2)
    {
        return array_udiff(
            $array1,
            $array2,
            function ($val1, $val2) {
                /** @var Descriptor $val1 */
                /** @var Descriptor $val2 */
                if ($val1->getName() == $val2->getName()) {
                    return 0;
                }
                return $val1->getName() > $val2->getName() ? 1 : -1;
            }
        );
    }

    private function arrayDiffSql(array $array1, array $array2)
    {
        return array_udiff(
            $array1,
            $array2,
            function ($val1, $val2) {
                if($val1->getCreateSql() == $val2->getCreateSql()){
                    return 0;
                }
                return $val1->getCreateSql() > $val2->getCreateSql() ? 1 : -1;
            }
        );
    }
}
