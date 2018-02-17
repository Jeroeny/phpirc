<?php
/**
 * Created by PhpStorm.
 * User: speej
 * Date: 17-2-2018
 * Time: 12:55
 */

namespace Smartirc;


class Error
{
    private $error_msg;

    public function __construct($message)
    {
        $this->error_msg = $message;
    }

    public function getMessage()
    {
        return $this->error_msg;
    }
}