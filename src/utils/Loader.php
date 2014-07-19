<?php
/**
 * Created by IntelliJ IDEA.
 * User: natronite
 * Date: 19/07/14
 * Time: 21:59
 */

namespace Natronite\Caribou\Utils;


class Loader
{

    private static $migrationsDir;

    /**
     * @param mixed $migrationsDir
     */
    public static function setMigrationsDir($migrationsDir)
    {
        self::$migrationsDir = $migrationsDir;
    }

    public static function loadMigrationVersion($version)
    {
        include_once self::fileForVersion('caribou', $version);
    }

    public static function fileForVersion($name, $version)
    {
        return self::dirForVersion($version) . strtolower($name) . ".php";
    }

    public static function classNameForVersion($name, $version)
    {
        $n = str_replace('_', ' ', $name);
        $name = str_replace(" ", "", ucwords($n));
        $v = explode('.', $version);
        return $name . "_" . implode("_", $v);
    }

    public static function dirForVersion($version)
    {
        return self::$migrationsDir . DIRECTORY_SEPARATOR . $version . DIRECTORY_SEPARATOR;
    }

    public static function loadModelVersion($name, $version)
    {
        include_once self::fileForVersion($name, $version);
    }
}