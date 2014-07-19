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

if (version_compare(PHP_VERSION, '5.3.6') < 0) {
    echo "Caribou needs at least PHP 5.3.6 to run. You use " . PHP_VERSION;
    exit(1);
}

if (count($argv) < 3) {
    displayUsage();
}

$command = $argv[1];
if ($command != 'generate' && $command != 'run') {
    echo $command . " is not a recognized command\n\n";
    displayUsage();
}

$configFile = $argv[2];
if (!is_file($configFile)) {
    echo "Cannot find config file " . $configFile . "\n\n";
}


// Try to load config
$config = @parse_ini_file($configFile);
if (!$config) {
    $pConfig = require_once $configFile;
    if (!isset($pConfig) || !isset($pConfig->database)) {
        echo "Error loading configuration " . $configFile . "\n\n";
    }
    $config = $pConfig->database;
}

if (count($argv) > 3) {
    $migrationsDir = $argv[3];
} else {
    $migrationsDir = "migrations";
}

$caribou = new \Natronite\Caribou\Caribou($config, $migrationsDir);

if ($command == "generate") {
    $caribou->generate();
} elseif ($command == "run") {
    $caribou->run();
}

/*
 * FUNCTIONS
 */

function displayUsage()
{
    echo "Usage: caribou command config [migrations-dir]\n\n";
    echo "command:\n\tgenerate\n\trun\n";
    echo "config:\n\tpath to ini config file with database connection info.";
    echo "migrations-dir:\n\tPath to the migrations (will be created if not existent). If not set ./migrations will be used.";
    exit("\n");
}

function __autoload($className)
{
    $className = ltrim($className, '\\');
    $fileName = '';
    if ($lastNsPos = strrpos($className, '\\')) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }
    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
    $fileName = str_replace('Natronite' . DIRECTORY_SEPARATOR . 'Caribou', 'src', $fileName);
    require $fileName;
}
