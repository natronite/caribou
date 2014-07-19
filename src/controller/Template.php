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
        return $this->content;
    }
}