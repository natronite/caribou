<?php
/**
 * Created by IntelliJ IDEA.
 * User: natronite
 * Date: 19/07/14
 * Time: 20:06
 */

namespace Natronite\Caribou\Controller;


class Template
{

    private $content;

    function   __construct($name)
    {
        $template = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'templates', $name . ".ntp"]);
        $this->content = file_get_contents($template);
    }

    public function set($name, $content)
    {
        $upperName = strtoupper($name);
        $this->content = str_replace('/*##' . $upperName . '##*/', $content, $this->content);
    }

    /**
     * @return string
     */
    public function getContent()
    {
        $lines = explode(PHP_EOL, $this->content);
        $doDelete = false;
        $delete = [];
        $prev = 0;
        foreach ($lines as $key => $line) {
            $delete[$key] = false;
            // check if semicolon or empty line
            if (strpos($line, "/*##") !== false) {
                // This is an element which hasn't been replaced;
                $doDelete = true;
            }
            if (strpos($line, ";") !== false) {
                if ($doDelete) {
                    for ($i = $prev + 1; $i <= $key; $i++) {
                        $delete[$i] = true;
                    }
                    $doDelete = false;
                }
                $prev = $key;
            }
        }

        foreach ($delete as $key => $value) {
            if ($value) {
                unset($lines[$key]);
            }
        }

        return implode(PHP_EOL, $lines);
    }
}