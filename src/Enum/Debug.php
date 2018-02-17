<?php
/**
 * Created by PhpStorm.
 * User: speej
 * Date: 17-2-2018
 * Time: 14:10
 */

namespace Smartirc\Enum;


class Debug
{
    const NONE = 0;
    const NOTICE = 1;
    const CONNECTION = 2;
    const SOCKET = 4;
    const IRCMESSAGES = 8;
    const MESSAGETYPES = 16;
    const ACTIONHANDLER = 32;
    const TIMEHANDLER = 64;
    const MESSAGEHANDLER = 128;
    const CHANNELSYNCING = 256;
    const MODULES = 512;
    const USERSYNCING = 1024;
    const MESSAGEPARSER = 2048;
    const DCC = 4096;
    const ALL = 8191;
}