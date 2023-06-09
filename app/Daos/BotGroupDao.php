<?php

namespace App\Daos;

use Illuminate\Support\Facades\Redis;

class BotGroupDao extends BaseDao
{
    // 获得AdConfig信息
    public static function getGroupConfig($chatId, $userId)
    {
        $redis = Redis::connection('main');
        $key = self::getAdConfigKey($chatId);
        return $redis->hexists($key, $userId);
    }

    // 设置AdConfig信息
    public static function setGroupConfig($chatId, $userId)
    {
        $redis = Redis::connection('main');
        $key = self::getAdConfigKey($chatId);
        $redis->hmSet($key, $userId, 1);
        return true;
    }

    public function del($chatId, $userId)
    {
        $redis = Redis::connection('main');
        $key = self::getAdConfigKey($chatId);
        $redis->hdel($key, $userId);
        return true;
    }

    private static function getAdConfigKey($chatId)
    {
        return "tgvideo_bot_group_key_" . $chatId;
    }
}
