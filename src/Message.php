<?php
/**
 * Created by PhpStorm.
 * User: speej
 * Date: 17-2-2018
 * Time: 12:52
 */

namespace Smartirc;


class Message
{

    /**
     * @var string
     */
    public $from;

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
    public $channel;

    /**
     * @var array
     */
    public $params = [];

    /**
     * @var string
     */
    public $message;

    /**
     * @var array
     */
    public $messageex = [];

    /**
     * @var integer
     */
    public $type;

    /**
     * @var string
     */
    public $rawmessage;

    /**
     * @var array
     */
    public $rawmessageex = [];
}