<?php
/**
 * Created by PhpStorm.
 * User: speej
 * Date: 17-2-2018
 * Time: 14:10
 */

namespace Smartirc\Enum;


class FileDestination
{
    const STDOUT = 0;
    const FILE = 1;
    const SYSLOG = 2;
    const BROWSEROUT = 3;
    const NONE = 4;
}