<?php

namespace bot;

require_once('vendor/autoload.php');

use Smartirc\Enum\HandlerType;
use Smartirc\Irc;
use Smartirc\Message;

class Bot
{

    public function __construct()
    {

        $config = json_decode(file_get_contents('config.json'), true);

        $irc = new Irc();
        $irc->registerActionHandler(
            HandlerType::JOIN,
            '.*',
            function (Irc $irc, Message $data) {

                if (!empty($data->nick)) {
                    $irc->message(HandlerType::CHANNEL, $data->channel,
                        "hi {$data->nick}, Wanneer gaan we naar de mac?");
                }
            }
        );

        $irc->registerActionHandler(HandlerType::CHANNEL, '^ping!$', function (Irc $irc, Message $data) {
            $irc->message(HandlerType::CHANNEL, $data->channel, 'Pong!');
        });

        $irc->registerActionHandler(HandlerType::QUERY, '^ping!$', function (Irc $irc, Message $data) {
            $irc->message(HandlerType::QUERY, $data->nick, 'Pong!');
        });


        $irc->connect($config['connection']['host'], $config['connection']['port'], $config['connection']['reconnect']);
        $irc->setUserSyncing(true);
        $irc->login(
            $config['login']['nick'],
            $config['login']['realname'],
            0,
            $config['login']['username'],
            $config['login']['password']
        );
        $irc->join($config['channels']);
        $irc->listen();
        $irc->disconnect();
    }

}

$bot = new Bot();