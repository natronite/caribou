<?php
/**
 * Created by IntelliJ IDEA.
 * User: nmaegli
 * Date: 21/07/14
 * Time: 10:18
 */

namespace Natronite\Caribou\Model;

interface Descriptor
{

    /**
     * @return string The name of the object
     */
    public function getName();


    /**
     * @return string The sql statement to create the object
     */
    public function getCreateSql();
}
