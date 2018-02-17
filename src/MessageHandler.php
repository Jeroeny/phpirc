<?php
/**
 * Created by PhpStorm.
 * User: speej
 * Date: 17-2-2018
 * Time: 12:57
 */

namespace Smartirc;

use Smartirc\Enum\Debug;
use Smartirc\Enum\HandlerType;
use Smartirc\Enum\Priority;

class MessageHandler extends Command
{

    protected function _event_ping($ircdata)
    {
        $this->log(Debug::CONNECTION, 'DEBUG_CONNECTION: Ping? Pong!',
            __FILE__, __LINE__
        );
        $this->send('PONG :' . $ircdata->message, Priority::CRITICAL);
    }

    protected function _event_error($ircdata)
    {
        if ($this->_autoretry) {
            $this->reconnect();
        } else {
            $this->disconnect(true);
        }
    }

    protected function _event_join($ircdata)
    {
        if ($this->_channelsyncing) {
            if ($this->_nick == $ircdata->nick) {
                $this->log(Debug::CHANNELSYNCING,
                    'DEBUG_CHANNELSYNCING: joining channel: '.$ircdata->channel,
                    __FILE__, __LINE__
                );
                $channel = new Channel();
                $channel->name = $ircdata->channel;
                $microint = microtime(true);
                $channel->synctime_start = $microint;
                $this->log(Debug::CHANNELSYNCING,
                    'DEBUG_CHANNELSYNCING: synctime_start for '
                    .$ircdata->channel.' set to: '.$microint, __FILE__, __LINE__
                );
                $this->_channels[strtolower($channel->name)] = $channel;

                // the class will get his own who data from the whole who channel list
                $this->mode($channel->name);
                $this->who($channel->name);
                $this->ban($channel->name);
            } else {
                // the class didn't join but someone else, lets get his who data
                $this->who($ircdata->nick);
            }

            $this->log(Debug::CHANNELSYNCING, 'DEBUG_CHANNELSYNCING: '
                .$ircdata->nick.' joins channel: '.$ircdata->channel,
                __FILE__, __LINE__
            );
            $channel = &$this->getChannel($ircdata->channel);
            $user = new ChannelUser();
            $user->nick = $ircdata->nick;
            $user->ident = $ircdata->ident;
            $user->host = $ircdata->host;

            $this->_adduser($channel, $user);
        }
    }

    protected function _event_part($ircdata)
    {
        if ($this->_channelsyncing) {
            $this->_removeuser($ircdata);
        }
    }

    protected function _event_kick($ircdata)
    {
        if ($this->_channelsyncing) {
            $this->_removeuser($ircdata);
        }
    }

    protected function _event_quit($ircdata)
    {
        if ($this->_channelsyncing) {
            $this->_removeuser($ircdata);
        }
    }

    protected function _event_nick($ircdata)
    {
        if ($this->_channelsyncing) {
            $newnick = $ircdata->params[0];
            $lowerednewnick = strtolower($newnick);
            $lowerednick = strtolower($ircdata->nick);

            $channelkeys = array_keys($this->_channels);
            foreach ($channelkeys as $channelkey) {
                // loop through all channels
                $channel = &$this->getChannel($channelkey);
                foreach ($channel->users as $uservalue) {
                    // loop through all user in this channel

                    if ($ircdata->nick == $uservalue->nick) {
                        // found him
                        // time for updating the object and his nickname
                        $channel->users[$lowerednewnick]
                            = $channel->users[$lowerednick]
                        ;
                        $channel->users[$lowerednewnick]->nick = $newnick;

                        if ($lowerednewnick != $lowerednick) {
                            unset($channel->users[$lowerednick]);
                        }

                        // he was maybe op or voice, update coming
                        $lists = array('founders', 'admins', 'ops', 'hops',
                            'voices'
                        );
                        foreach ($lists as $list) {
                            if (isset($channel->{$list}[$ircdata->nick])) {
                                $channel->{$list}[$newnick]
                                    = $channel->{$list}[$ircdata->nick];
                                unset($channel->{$list}[$ircdata->nick]);
                            }
                        }
                        break;
                    }
                }
            }
        }
    }

    protected function _event_mode($ircdata)
    {
        // check if its own usermode
        if ($ircdata->params[0] == $this->_nick) {
            $this->_usermode = $ircdata->message;
        } else if ($this->_channelsyncing) {
            // it's not, and we do channel syncing
            $channel = &$this->getChannel($ircdata->channel);
            $this->log(Debug::CHANNELSYNCING,
                'DEBUG_CHANNELSYNCING: updating channel mode for: '
                .$channel->name, __FILE__, __LINE__
            );
            $mode = $ircdata->params[1];
            $parameters = array_slice($ircdata->params, 2);

            $add = false;
            $remove = false;
            $modelength = strlen($mode);
            for ($i = 0; $i < $modelength; $i++) {
                switch($mode{$i}) {
                    case '-':
                        $remove = true;
                        $add = false;
                        break;

                    case '+':
                        $add = true;
                        $remove = false;
                        break;

                    // user modes
                    case 'q':
                        $nick = array_shift($parameters);
                        $lowerednick = strtolower($nick);
                        if ($add) {
                            $this->log(Debug::CHANNELSYNCING,
                                'DEBUG_CHANNELSYNCING: adding founder: '.$nick
                                .' to channel: '.$channel->name,
                                __FILE__, __LINE__
                            );
                            $channel->founders[$nick] = true;
                            $channel->users[$lowerednick]->founder = true;
                        }
                        if ($remove) {
                            $this->log(Debug::CHANNELSYNCING,
                                'DEBUG_CHANNELSYNCING: removing founder: '.$nick
                                .' to channel: '.$channel->name,
                                __FILE__, __LINE__
                            );
                            unset($channel->founders[$nick]);
                            $channel->users[$lowerednick]->founder = false;
                        }
                        break;

                    case 'a':
                        $nick = array_shift($parameters);
                        $lowerednick = strtolower($nick);
                        if ($add) {
                            $this->log(Debug::CHANNELSYNCING,
                                'DEBUG_CHANNELSYNCING: adding admin: '.$nick
                                .' to channel: '.$channel->name,
                                __FILE__, __LINE__
                            );
                            $channel->admins[$nick] = true;
                            $channel->users[$lowerednick]->admin = true;
                        }
                        if ($remove) {
                            $this->log(Debug::CHANNELSYNCING,
                                'DEBUG_CHANNELSYNCING: removing admin: '.$nick
                                .' to channel: '.$channel->name,
                                __FILE__, __LINE__
                            );
                            unset($channel->admins[$nick]);
                            $channel->users[$lowerednick]->admin = false;
                        }
                        break;

                    case 'o':
                        $nick = array_shift($parameters);
                        $lowerednick = strtolower($nick);
                        if ($add) {
                            $this->log(Debug::CHANNELSYNCING,
                                'DEBUG_CHANNELSYNCING: adding op: '.$nick
                                .' to channel: '.$channel->name,
                                __FILE__, __LINE__
                            );
                            $channel->ops[$nick] = true;
                            $channel->users[$lowerednick]->op = true;
                        }
                        if ($remove) {
                            $this->log(Debug::CHANNELSYNCING,
                                'DEBUG_CHANNELSYNCING: removing op: '.$nick
                                .' to channel: '.$channel->name,
                                __FILE__, __LINE__
                            );
                            unset($channel->ops[$nick]);
                            $channel->users[$lowerednick]->op = false;
                        }
                        break;

                    case 'h':
                        $nick = array_shift($parameters);
                        $lowerednick = strtolower($nick);
                        if ($add) {
                            $this->log(Debug::CHANNELSYNCING,
                                'DEBUG_CHANNELSYNCING: adding half-op: '.$nick
                                .' to channel: '.$channel->name,
                                __FILE__, __LINE__
                            );
                            $channel->hops[$nick] = true;
                            $channel->users[$lowerednick]->hop = true;
                        }
                        if ($remove) {
                            $this->log(Debug::CHANNELSYNCING,
                                'DEBUG_CHANNELSYNCING: removing half-op: '.$nick
                                .' to channel: '.$channel->name,
                                __FILE__, __LINE__
                            );
                            unset($channel->hops[$nick]);
                            $channel->users[$lowerednick]->hop = false;
                        }
                        break;

                    case 'v':
                        $nick = array_shift($parameters);
                        $lowerednick = strtolower($nick);
                        if ($add) {
                            $this->log(Debug::CHANNELSYNCING,
                                'DEBUG_CHANNELSYNCING: adding voice: '.$nick
                                .' to channel: '.$channel->name,
                                __FILE__, __LINE__
                            );
                            $channel->voices[$nick] = true;
                            $channel->users[$lowerednick]->voice = true;
                        }
                        if ($remove) {
                            $this->log(Debug::CHANNELSYNCING,
                                'DEBUG_CHANNELSYNCING: removing voice: '.$nick
                                .' to channel: '.$channel->name,
                                __FILE__, __LINE__
                            );
                            unset($channel->voices[$nick]);
                            $channel->users[$lowerednick]->voice = false;
                        }
                        break;

                    case 'k':
                        $key = array_shift($parameters);
                        if ($add) {
                            $this->log(Debug::CHANNELSYNCING,
                                'DEBUG_CHANNELSYNCING: stored channel key for: '
                                .$channel->name, __FILE__, __LINE__
                            );
                            $channel->key = $key;
                        }
                        if ($remove) {
                            $this->log(Debug::CHANNELSYNCING,
                                'DEBUG_CHANNELSYNCING: removed channel key for: '
                                .$channel->name, __FILE__, __LINE__
                            );
                            $channel->key = '';
                        }
                        break;

                    case 'l':
                        if ($add) {
                            $limit = array_shift($parameters);
                            $this->log(Debug::CHANNELSYNCING,
                                'DEBUG_CHANNELSYNCING: stored user limit for: '
                                .$channel->name, __FILE__, __LINE__
                            );
                            $channel->user_limit = $limit;
                        }
                        if ($remove) {
                            $this->log(Debug::CHANNELSYNCING,
                                'DEBUG_CHANNELSYNCING: removed user limit for: '
                                .$channel->name, __FILE__, __LINE__
                            );
                            $channel->user_limit = false;
                        }
                        break;

                    default:
                        // channel modes
                        if ($mode{$i} == 'b') {
                            $hostmask = array_shift($parameters);
                            if ($add) {
                                $this->log(Debug::CHANNELSYNCING,
                                    'DEBUG_CHANNELSYNCING: adding ban: '
                                    .$hostmask.' for: '.$channel->name,
                                    __FILE__, __LINE__
                                );
                                $channel->bans[$hostmask] = true;
                            }
                            if ($remove) {
                                $this->log(Debug::CHANNELSYNCING,
                                    'DEBUG_CHANNELSYNCING: removing ban: '
                                    .$hostmask.' for: '.$channel->name,
                                    __FILE__, __LINE__
                                );
                                unset($channel->bans[$hostmask]);
                            }
                        } else {
                            $this->log(Debug::CHANNELSYNCING,
                                'DEBUG_CHANNELSYNCING: updating unknown channelmode ('
                                .$mode{$i}.') in channel->mode for: '
                                .$channel->name, __FILE__, __LINE__
                            );
                            if ($add) {
                                $channel->mode .= $mode{$i};
                            }
                            if ($remove) {
                                $channel->mode = str_replace($mode{$i}, '',
                                    $channel->mode
                                );
                            }
                        }
                }
            }
        }
    }

    protected function _event_topic($ircdata)
    {
        if ($this->_channelsyncing) {
            $channel = &$this->getChannel($ircdata->channel);
            $channel->topic = $ircdata->message;
        }
    }

    protected function _event_privmsg($ircdata)
    {
        if ($ircdata->type & HandlerType::CTCP_REQUEST) {
            // substr must be 1,4 because of \001 in CTCP messages
            if (substr($ircdata->message, 1, 4) == 'PING') {
                $this->message(HandlerType::CTCP_REPLY, $ircdata->nick,
                    'PING'.substr($ircdata->message, 5, -1)
                );
            } elseif (substr($ircdata->message, 1, 7) == 'VERSION') {
                if (!empty($this->_ctcpversion)) {
                    $versionstring = $this->_ctcpversion;
                } else {
                    $versionstring = Irc::VERSIONSTRING;
                }

                $this->message(HandlerType::CTCP_REPLY, $ircdata->nick,
                    'VERSION '.$versionstring
                );
            } elseif (substr($ircdata->message, 1, 10) == 'CLIENTINFO') {
                $this->message(HandlerType::CTCP_REPLY, $ircdata->nick,
                    'CLIENTINFO PING VERSION CLIENTINFO'
                );
            }
        }
    }

    /* rpl_ */
    protected function _event_rpl_welcome($ircdata)
    {
        $this->_loggedin = true;

        // updating our nickname, that we got (maybe cutted...)
        $this->_nick = $ircdata->params[0];

        $this->log(Debug::CONNECTION, 'DEBUG_CONNECTION: logged in as '
            . $this->_nick, __FILE__, __LINE__
        );

    }

    protected function _event_rpl_motdstart($ircdata)
    {
        $this->_motd[] = $ircdata->message;
    }

    protected function _event_rpl_motd($ircdata)
    {
        $this->_motd[] = $ircdata->message;
    }

    protected function _event_rpl_endofmotd($ircdata)
    {
        $this->_motd[] = $ircdata->message;
    }

    protected function _event_rpl_umodeis($ircdata)
    {
        $this->_usermode = $ircdata->message;
    }

    protected function _event_rpl_channelmodeis(&$ircdata) {
        if ($this->_channelsyncing && $this->isJoined($ircdata->channel)) {
            $ircdata->params[0] = '';

            // let _mode() handle the received mode
            $this->_event_mode($ircdata);
        }
    }

    protected function _event_rpl_whoreply($ircdata)
    {
        if ($this->_channelsyncing) {
            $offset = (int) ($ircdata->params[0] == $this->_nick);
            $nick = $ircdata->params[4 + $offset];

            if ($ircdata->channel == '*') {
                // we got who info without channel info, so search the user
                // on all channels and update him
                foreach ($this->_channels as $channel) {
                    if ($this->isJoined($channel->name, $nick)) {
                        $ircdata->channel = $channel->name;
                        $this->_event_rpl_whoreply($ircdata);
                    }
                }
            } else {
                if (!$this->isJoined($ircdata->channel, $nick)) {
                    return;
                }

                $user = new ChannelUser();
                $user->ident = $ircdata->params[1 + $offset];
                $user->host = $ircdata->params[2 + $offset];
                $user->server = $ircdata->params[3 + $offset];
                $user->nick = $nick;

                $user->ircop = false;
                $user->founder = false;
                $user->admin = false;
                $user->op = false;
                $user->hop = false;
                $user->voice = false;

                $usermode = $ircdata->params[5 + $offset];
                $user->modes = $usermode;

                $usermodelength = strlen($usermode);
                for ($i = 0; $i < $usermodelength; $i++) {
                    switch ($usermode{$i}) {
                        case 'H':
                            $user->away = false;
                            break;

                        case 'G':
                            $user->away = true;
                            break;

                        case '*':
                            $user->ircop = true;
                            break;

                        case '~':
                            $user->founder = true;
                            break;

                        case '&':
                            $user->admin = true;
                            break;

                        case '@':
                            $user->op = true;
                            break;

                        case '%':
                            $user->hop = true;
                            break;

                        case '+':
                            $user->voice = true;
                    }
                }

                $user->hopcount = $ircdata->messageex[0];
                $user->realname = implode(array_slice($ircdata->messageex, 1), ' ');

                $channel = &$this->getChannel($ircdata->channel);
                $this->_adduser($channel, $user);
            }
        }
    }

    protected function _event_rpl_namreply($ircdata)
    {
        if ($this->_channelsyncing) {
            $userarray = explode(' ', rtrim($ircdata->message));
            $userarraycount = count($userarray);
            for ($i = 0; $i < $userarraycount; $i++) {
                $user = new ChannelUser();

                switch ($userarray[$i]{0}) {
                    case '~':
                        $user->founder = true;
                        $user->nick = substr($userarray[$i], 1);
                        break;

                    case '&':
                        $user->admin = true;
                        $user->nick = substr($userarray[$i], 1);
                        break;

                    case '@':
                        $user->op = true;
                        $user->nick = substr($userarray[$i], 1);
                        break;

                    case '%':
                        $user->hop = true;
                        $user->nick = substr($userarray[$i], 1);
                        break;

                    case '+':
                        $user->voice = true;
                        $user->nick = substr($userarray[$i], 1);
                        break;

                    default:
                        $user->nick = $userarray[$i];
                }

                $channel = &$this->getChannel($ircdata->channel);
                $this->_adduser($channel, $user);
            }
        }
    }

    protected function _event_rpl_banlist($ircdata)
    {
        if ($this->_channelsyncing && $this->isJoined($ircdata->channel)) {
            $channel = &$this->getChannel($ircdata->channel);
            $hostmask = $ircdata->params[1];
            $channel->bans[$hostmask] = true;
        }
    }

    protected function _event_rpl_endofbanlist($ircdata)
    {
        if ($this->_channelsyncing && $this->isJoined($ircdata->channel)) {
            $channel = &$this->getChannel($ircdata->channel);
            if ($channel->synctime_stop == 0) {
                // we received end of banlist and the stop timestamp is not set yet
                $channel->synctime_stop = microtime(true);
                $this->log(Debug::CHANNELSYNCING,
                    'DEBUG_CHANNELSYNCING: synctime_stop for '.$ircdata->channel
                    .' set to: '.$channel->synctime_stop, __FILE__, __LINE__
                );

                $channel->synctime = (float)$channel->synctime_stop
                    - (float)$channel->synctime_start
                ;
                $this->log(Debug::CHANNELSYNCING,
                    'DEBUG_CHANNELSYNCING: synced channel '.$ircdata->channel
                    .' in '.round($channel->synctime, 2).' secs',
                    __FILE__, __LINE__
                );
            }
        }
    }

    protected function _event_rpl_topic($ircdata)
    {
        if ($this->_channelsyncing) {
            $channel = &$this->getChannel($ircdata->channel);
            $channel->topic = $ircdata->message;
        }
    }

    /* err_ */
    protected function _event_err_nicknameinuse($ircdata)
    {
        $newnick = substr($this->_nick, 0, 5) . rand(0, 999);
        $this->changeNick($newnick, Priority::CRITICAL);
    }
}