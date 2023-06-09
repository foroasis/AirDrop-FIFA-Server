<?php

namespace App\Daos;

use Illuminate\Support\Facades\Redis;

class TgBotCommandDao extends BaseDao
{
    public static function getTgBotCommand($fromId)
    {
        $redis = Redis::connection('main');
        $key = self::getTgBotCommandKey($fromId);
        return $redis->get($key);
    }

    public static function setTgBotCommand($fromId, $command)
    {
        $redis = Redis::connection('main');
        $key = self::getTgBotCommandKey($fromId);
        $redis->set($key, $command);
        return true;
    }

    public static function delTgBotCommand($fromId)
    {
        $redis = Redis::connection('main');
        $key = self::getTgBotCommandKey($fromId);
        $redis->del($key);
        return true;
    }

    private static function getTgBotCommandKey($fromId)
    {
        return "tg_bot_command_key_" . $fromId;
    }


}
