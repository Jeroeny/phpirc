<?php
/**
 * Created by PhpStorm.
 * User: speej
 * Date: 17-2-2018
 * Time: 12:53
 */

namespace Smartirc;


class User
{

    /**
     * @var string
     */
    public $nick;

    /**
     * @var string
     */
    public $ident;

    /**
     * @var string
     */
    public $host;

    /**
     * @var string
     */
    public $realname;

    /**
     * @var boolean
     */
    public $ircop;

    /**
     * @var boolean
     */
    public $away;

    /**
     * @var string
     */
    public $server;

    /**
     * @var integer
     */
    public $hopcount;
}