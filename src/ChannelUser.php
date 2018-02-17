<?php

namespace Smartirc;

class ChannelUser extends User
{
    /**
     * @var boolean
     */
    public $founder;

    /**
     * @var boolean
     */
    public $admin;

    /**
     * @var boolean
     */
    public $op;

    /**
     * @var boolean
     */
    public $hop;

    /**
     * @var boolean
     */
    public $voice;
}