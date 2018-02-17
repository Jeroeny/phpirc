<?php
/**
 * Created by PhpStorm.
 * User: speej
 * Date: 17-2-2018
 * Time: 14:12
 */

namespace Smartirc\Enum;


class HandlerType
{
    const UNKNOWN = 1;
    const CHANNEL = 2;
    const QUERY = 4;
    const CTCP = 8;
    const NOTICE = 16;
    const WHO = 32;
    const JOIN = 64;
    const INVITE = 128;
    const ACTION = 256;
    const TOPICCHANGE = 512;
    const NICKCHANGE = 1024;
    const KICK = 2048;
    const QUIT = 4096;
    const LOGIN = 8192;
    const INFO = 16384;
    const ULIST = 32768;
    const NAME = 65536;
    const MOTD = 131072;
    const MODECHANGE = 262144;
    const PART = 524288;
    const ERROR = 1048576;
    const BANLIST = 2097152;
    const TOPIC = 4194304;
    const NONRELEVANT = 8388608;
    const WHOIS = 16777216;
    const WHOWAS = 33554432;
    const USERMODE = 67108864;
    const CHANNELMODE = 134217728;
    const CTCP_REQUEST = 268435456;
    const CTCP_REPLY = 536870912;
    const DCC = 536870912;
    const ALL = 1073741823;
}