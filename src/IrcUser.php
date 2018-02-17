<?php
/**
 * Created by PhpStorm.
 * User: speej
 * Date: 17-2-2018
 * Time: 12:53
 */

namespace Smartirc;

class IrcUser extends User
{
    /**
     * @var array
     */
    public $joinedchannels = [];
}