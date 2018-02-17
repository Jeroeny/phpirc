<?php
/**
 * Created by PhpStorm.
 * User: speej
 * Date: 17-2-2018
 * Time: 12:52
 */

namespace Smartirc;


class Channel
{

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $key;

    /**
     * @var array
     */
    public $users = [];

    /**
     * @var array
     */
    public $founders = [];

    /**
     * @var array
     */
    public $admins = [];

    /**
     * @var array
     */
    public $ops = [];

    /**
     * @var array
     */
    public $hops = [];

    /**
     * @var array
     */
    public $voices = [];

    /**
     * @var array
     */
    public $bans = [];

    /**
     * @var string
     */
    public $topic;

    /**
     * @var string
     */
    public $user_limit = false;

    /**
     * @var string
     */
    public $mode;

    /**
     * @var integer
     */
    public $synctime_start = 0;

    /**
     * @var integer
     */
    public $synctime_stop = 0;

    /**
     * @var integer
     */
    public $synctime;

}