<?php
/**
 * Created by IntelliJ IDEA.
 * User: nmaegli
 * Date: 21/07/14
 * Time: 14:15
 */

namespace Natronite\Caribou\Utils;


use Natronite\Caribou\Controller\Template;
use Natronite\Caribou\Model\Column;
use Natronite\Caribou\Model\Index;
use Natronite\Caribou\Model\Reference;
use Natronite\Caribou\Model\Table;

class Generator
{
    const LINE_PREFIX = "\t\t\t\t";

    public static function generateVersion($version, $migrationsDir)
    {
        // create dir
        mkdir($migrationsDir . DIRECTORY_SEPARATOR . $version);
        // get tables
        $tables = Connection::getTables();

        /** @var Table $table */
        foreach ($tables as $table) {
            Generator::generateTable($version, $table);
        }
    }

    private static function generateTable($version, Table $table)
    {
        $className = Loader::classNameForVersion($table->getName(), $version);
        $template = new Template('table');
        $template->set('className', $className);

        Generator::generateGeneral($template, $table);
        Generator::generateColumns($template, $table);
        Generator::generateIndexes($template, $table);
        Generator::generateReferences($template, $table);

        $file = Loader::fileForVersion($table->getName(), $version);
        file_put_contents($file, $template->getContent());
    }

    private static function generateGeneral(Template $template, Table $table)
    {
        $template->set('tableName', $table->getName());
        $template->set('engine', $table->getEngine());
        $template->set('collate', $table->getCollate());
        if ($table->getAutoIncrement() !== null) {
            $template->set('autoIncrement', $table->getAutoIncrement());
        }
    }

    private static function generateColumns(Template $template, Table $table)
    {
        /** @var Column $column */
        $columns = [];
        foreach ($table->getColumns() as $column) {
            $c = "'" . $column->getName() . "' => new Column(";
            $c .= "\n\t" . self::LINE_PREFIX . "\"" . $column->getName() . "\",\n";
            $c .= Generator::varExport($column->getDescription(), self::LINE_PREFIX . "\t");
            $c .= "\n" . self::LINE_PREFIX . ")";
            $columns[] = $c;
        }
        $template->set('columns', implode(",\n" . self::LINE_PREFIX, $columns));
    }

    private static function generateIndexes(Template $template, Table $table)
    {
        /** @var Index $index */
        $indexes = [];
        foreach ($table->getIndexes() as $index) {
            $c = "new Index("
                . "\"" . $index->getName() . "\","
                . "['" . implode('\', \'', $index->getColumns()) . "']";
            if ($index->isUnique()) {
                $c .= ", true";
            }
            $c .= ")";
            $indexes[] = $c;
        }
        $template->set('indexes', implode(",\n" . self::LINE_PREFIX, $indexes));
    }

    private static function generateReferences(Template $template, Table $table)
    {
        /** @var Reference $reference */
        $references = [];
        foreach ($table->getReferences() as $reference) {
            $r = "new Reference('"
                . $reference->getName() . "', "
                . "['" . implode("', '", $reference->getColumns()) . "'], '"
                . $reference->getReferencedTable() . "', "
                . "['" . implode("', '", $reference->getReferencedColumns()) . "']";

            if ($reference->getUpdateRule()) {
                $r .= ", '" . $reference->getUpdateRule() . "'";
            }
            if ($reference->getDeleteRule()) {
                $r .= ", '" . $reference->getDeleteRule() . "'";
            }
            $r .= ")";

            $references[] = $r;
        }
        if(!empty($references)) {
            $template->set('references', implode(",\n" . self::LINE_PREFIX, $references));
        }
    }

    private static function varExport(array $array, $linePrefix)
    {
        $lines = explode(PHP_EOL, var_export($array, true));
        $result = implode(PHP_EOL . $linePrefix, $lines);
        return $linePrefix . $result;
    }
} 