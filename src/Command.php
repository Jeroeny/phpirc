<?php


namespace Smartirc;

use Smartirc\Enum\HandlerType;
use Smartirc\Enum\Priority;

class Command
{

    /**
     * sends a new message
     *
     * Sends a message to a channel or user.
     *
     * @param integer $type specifies the type, like QUERY/ACTION or CTCP see 'Message Types'
     * @param string $destination can be a user or channel
     * @param mixed $messagearray the message
     * @param integer $priority the priority level of the message
     * @return boolean|Irc
     */
    public function message($type, $destination, $messagearray,
        $priority = Priority::MEDIUM
    ) {
        if (!is_array($messagearray)) {
            $messagearray = array($messagearray);
        }

        switch ($type) {
            case HandlerType::CHANNEL:
            case HandlerType::QUERY:
                foreach ($messagearray as $message) {
                    $this->send('PRIVMSG '.$destination.' :'.$message, $priority);
                }
                break;

            case HandlerType::ACTION:
                foreach ($messagearray as $message) {
                    $this->send('PRIVMSG '.$destination.' :'.chr(1).'ACTION '
                        .$message.chr(1), $priority
                    );
                }
                break;

            case HandlerType::NOTICE:
                foreach ($messagearray as $message) {
                    $this->send('NOTICE '.$destination.' :'.$message, $priority);
                }
                break;

            case HandlerType::CTCP: // backwards compatibility
            case HandlerType::CTCP_REPLY:
                foreach ($messagearray as $message) {
                    $this->send('NOTICE '.$destination.' :'.chr(1).$message
                        .chr(1), $priority
                    );
                }
                break;

            case HandlerType::CTCP_REQUEST:
                foreach ($messagearray as $message) {
                    $this->send('PRIVMSG '.$destination.' :'.chr(1).$message
                        .chr(1), $priority
                    );
                }
                break;

            default:
                return false;
        }

        return $this;
    }

    /**
     * Joins one or more IRC channels with an optional key.
     *
     * @param mixed $channelarray
     * @param string $key
     * @param integer $priority message priority, default is Priority::MEDIUM
     * @return Irc
     */
    public function join($channelarray, $key = null, $priority = Priority::MEDIUM)
    {
        if (!is_array($channelarray)) {
            $channelarray = array($channelarray);
        }

        $channellist = implode(',', $channelarray);

        if ($key !== null) {
            foreach ($channelarray as $idx => $value) {
                $this->send('JOIN '.$value.' '.$key, $priority);
            }
        } else {
            foreach ($channelarray as $idx => $value) {
                $this->send('JOIN '.$value, $priority);
            }
        }

        return $this;
    }

    /**
     * parts from one or more IRC channels with an optional reason
     *
     * @param mixed $channelarray
     * @param string $reason
     * @param integer $priority message priority, default is Priority::MEDIUM
     * @return Irc
     * @api
     */
    public function part($channelarray, $reason = null,
        $priority = Priority::MEDIUM
    ) {
        if (!is_array($channelarray)) {
            $channelarray = array($channelarray);
        }

        $channellist = implode(',', $channelarray);

        if ($reason !== null) {
            $this->send('PART '.$channellist.' :'.$reason, $priority);
        } else {
            $this->send('PART '.$channellist, $priority);
        }
        return $this;
    }

    /**
     * Kicks one or more user from an IRC channel with an optional reason.
     *
     * @param string $channel
     * @param mixed $nicknamearray
     * @param string $reason
     * @param integer $priority message priority, default is Priority::MEDIUM
     * @return Irc
     * @api
     */
    public function kick($channel, $nicknamearray, $reason = null,
        $priority = Priority::MEDIUM
    ) {
        if (!is_array($nicknamearray)) {
            $nicknamearray = array($nicknamearray);
        }

        $nicknamelist = implode(',', $nicknamearray);

        if ($reason !== null) {
            $this->send('KICK '.$channel.' '.$nicknamelist.' :'.$reason, $priority);
        } else {
            $this->send('KICK '.$channel.' '.$nicknamelist, $priority);
        }
        return $this;
    }

    /**
     * gets a list of one ore more channels
     *
     * Requests a full channellist if $channelarray is not given.
     * (use it with care, usually its a looooong list)
     *
     * @param mixed $channelarray
     * @param integer $priority message priority, default is Priority::MEDIUM
     * @return Irc
     * @api
     */
    public function getList($channelarray = null, $priority = Priority::MEDIUM)
    {
        if ($channelarray !== null) {
            if (!is_array($channelarray)) {
                $channelarray = array($channelarray);
            }

            $channellist = implode(',', $channelarray);
            $this->send('LIST '.$channellist, $priority);
        } else {
            $this->send('LIST', $priority);
        }
        return $this;
    }

    /**
     * requests all nicknames of one or more channels
     *
     * The requested nickname list also includes op and voice state
     *
     * @param mixed $channelarray
     * @param integer $priority message priority, default is Priority::MEDIUM
     * @return Irc
     * @api
     */
    public function names($channelarray = null, $priority = Priority::MEDIUM)
    {
        if ($channelarray !== null) {
            if (!is_array($channelarray)) {
                $channelarray = array($channelarray);
            }

            $channellist = implode(',', $channelarray);
            $this->send('NAMES '.$channellist, $priority);
        } else {
            $this->send('NAMES', $priority);
        }
        return $this;
    }

    /**
     * sets a new topic of a channel
     *
     * @param string $channel
     * @param string $newtopic
     * @param integer $priority message priority, default is Priority::MEDIUM
     * @return Irc
     * @api
     */
    public function setTopic($channel, $newtopic, $priority = Priority::MEDIUM)
    {
        $this->send('TOPIC '.$channel.' :'.$newtopic, $priority);
        return $this;
    }

    /**
     * gets the topic of a channel
     *
     * @param string $channel
     * @param integer $priority message priority, default is Priority::MEDIUM
     * @return Irc
     * @api
     */
    public function getTopic($channel, $priority = Priority::MEDIUM)
    {
        $this->send('TOPIC '.$channel, $priority);
        return $this;
    }

    /**
     * sets or gets the mode of an user or channel
     *
     * Changes/requests the mode of the given target.
     *
     * @param string $target the target, can be an user (only yourself) or a channel
     * @param string $newmode the new mode like +mt
     * @param integer $priority message priority, default is Priority::MEDIUM
     * @return Irc
     * @api
     */
    public function mode($target, $newmode = null, $priority = Priority::MEDIUM)
    {
        if ($newmode !== null) {
            $this->send('MODE '.$target.' '.$newmode, $priority);
        } else {
            $this->send('MODE '.$target, $priority);
        }
        return $this;
    }

    /**
     * founders an user in the given channel
     *
     * @param string $channel
     * @param string $nickname
     * @param integer $priority message priority, default is Priority::MEDIUM
     * @return Irc
     * @api
     */
    public function founder($channel, $nickname, $priority = Priority::MEDIUM)
    {
        return $this->mode($channel, '+q '.$nickname, $priority);
    }

    /**
     * defounders an user in the given channel
     *
     * @param string $channel
     * @param string $nickname
     * @param integer $priority message priority, default is Priority::MEDIUM
     * @return Irc
     * @api
     */
    public function defounder($channel, $nickname, $priority = Priority::MEDIUM)
    {
        return $this->mode($channel, '-q '.$nickname, $priority);
    }

    /**
     * admins an user in the given channel
     *
     * @param string $channel
     * @param string $nickname
     * @param integer $priority message priority, default is Priority::MEDIUM
     * @return Irc
     * @api
     */
    public function admin($channel, $nickname, $priority = Priority::MEDIUM)
    {
        return $this->mode($channel, '+a '.$nickname, $priority);
    }

    /**
     * deadmins an user in the given channel
     *
     * @param string $channel
     * @param string $nickname
     * @param integer $priority message priority, default is Priority::MEDIUM
     * @return Irc
     * @api
     */
    public function deadmin($channel, $nickname, $priority = Priority::MEDIUM)
    {
        return $this->mode($channel, '-a '.$nickname, $priority);
    }

    /**
     * ops an user in the given channel
     *
     * @param string $channel
     * @param string $nickname
     * @param integer $priority message priority, default is Priority::MEDIUM
     * @return Irc
     * @api
     */
    public function op($channel, $nickname, $priority = Priority::MEDIUM)
    {
        return $this->mode($channel, '+o '.$nickname, $priority);
    }

    /**
     * deops an user in the given channel
     *
     * @param string $channel
     * @param string $nickname
     * @param integer $priority message priority, default is Priority::MEDIUM
     * @return Irc
     * @api
     */
    public function deop($channel, $nickname, $priority = Priority::MEDIUM)
    {
        return $this->mode($channel, '-o '.$nickname, $priority);
    }

    /**
     * hops an user in the given channel
     *
     * @param string $channel
     * @param string $nickname
     * @param integer $priority message priority, default is Priority::MEDIUM
     * @return Irc
     * @api
     */
    public function hop($channel, $nickname, $priority = Priority::MEDIUM)
    {
        return $this->mode($channel, '+h '.$nickname, $priority);
    }

    /**
     * dehops an user in the given channel
     *
     * @param string $channel
     * @param string $nickname
     * @param integer $priority message priority, default is Priority::MEDIUM
     * @return Irc
     * @api
     */
    public function dehop($channel, $nickname, $priority = Priority::MEDIUM)
    {
        return $this->mode($channel, '-h '.$nickname, $priority);
    }

    /**
     * voice a user in the given channel
     *
     * @param string $channel
     * @param string $nickname
     * @param integer $priority message priority, default is Priority::MEDIUM
     * @return Irc
     * @api
     */
    public function voice($channel, $nickname, $priority = Priority::MEDIUM)
    {
        return $this->mode($channel, '+v '.$nickname, $priority);
    }

    /**
     * devoice a user in the given channel
     *
     * @param string $channel
     * @param string $nickname
     * @param integer $priority message priority, default is Priority::MEDIUM
     * @return Irc
     * @api
     */
    public function devoice($channel, $nickname, $priority = Priority::MEDIUM)
    {
        return $this->mode($channel, '-v '.$nickname, $priority);
    }

    /**
     * bans a hostmask for the given channel or requests the current banlist
     *
     * The banlist will be requested if no hostmask is specified
     *
     * @param string $channel
     * @param string $hostmask
     * @param integer $priority message priority, default is Priority::MEDIUM
     * @return Irc
     * @api
     */
    public function ban($channel, $hostmask = null, $priority = Priority::MEDIUM)
    {
        if ($hostmask !== null) {
            $this->mode($channel, '+b '.$hostmask, $priority);
        } else {
            $this->mode($channel, 'b', $priority);
        }
        return $this;
    }

    /**
     * unbans a hostmask on the given channel
     *
     * @param string $channel
     * @param string $hostmask
     * @param integer $priority message priority, default is Priority::MEDIUM
     * @return Irc
     * @api
     */
    public function unban($channel, $hostmask, $priority = Priority::MEDIUM)
    {
        return $this->mode($channel, '-b '.$hostmask, $priority);
    }

    /**
     * invites a user to the specified channel
     *
     * @param string $nickname
     * @param string $channel
     * @param integer $priority message priority, default is Priority::MEDIUM
     * @return Irc
     * @api
     */
    public function invite($nickname, $channel, $priority = Priority::MEDIUM)
    {
        return $this->send('INVITE '.$nickname.' '.$channel, $priority);
    }

    /**
     * changes the own nickname
     *
     * Trys to set a new nickname, nickcollisions are handled.
     *
     * @param string $newnick
     * @param integer $priority message priority, default is Priority::MEDIUM
     * @return Irc
     * @api
     */
    public function changeNick($newnick, $priority = Priority::MEDIUM)
    {
        $this->_nick = $newnick;
        return $this->send('NICK '.$newnick, $priority);
    }

    /**
     * requests a 'WHO' from the specified target
     *
     * @param string $target
     * @param integer $priority message priority, default is Priority::MEDIUM
     * @return Irc
     * @api
     */
    public function who($target, $priority = Priority::MEDIUM)
    {
        return $this->send('WHO '.$target, $priority);
    }

    /**
     * requests a 'WHOIS' from the specified target
     *
     * @param string $target
     * @param integer $priority message priority, default is Priority::MEDIUM
     * @return Irc
     * @api
     */
    public function whois($target, $priority = Priority::MEDIUM)
    {
        return $this->send('WHOIS '.$target, $priority);
    }

    /**
     * requests a 'WHOWAS' from the specified target
     * (if he left the IRC network)
     *
     * @param string $target
     * @param integer $priority message priority, default is Priority::MEDIUM
     * @return Irc
     * @api
     */
    public function whowas($target, $priority = Priority::MEDIUM)
    {
        return $this->send('WHOWAS '.$target, $priority);
    }

    /**
     * sends QUIT to IRC server and disconnects
     *
     * @param string $quitmessage optional quitmessage
     * @param integer $priority message priority, default is Priority::CRITICAL
     * @return Irc
     * @api
     */
    public function quit($quitmessage = null, $priority = Priority::CRITICAL)
    {
        if ($quitmessage !== null) {
            $this->send('QUIT :'.$quitmessage, $priority);
        } else {
            $this->send('QUIT', $priority);
        }

        return $this->disconnect(true);
    }

}