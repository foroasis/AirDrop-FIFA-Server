<?php

namespace App\Daos;

use Illuminate\Support\Facades\Redis;

class GroupSpaceSetLangDao extends BaseDao
{
    // 获得选择的语言信息
    public static function getLang($fromId)
    {
        $redis = Redis::connection('main');
        $key = self::getGroupSpaceLangKey($fromId);
        $res = $redis->get($key);
        return $res ?: "en";
    }

    // 设置选择的语言信息
    public static function setLang($fromId, $data)
    {
        $redis = Redis::connection('main');
        $key = self::getGroupSpaceLangKey($fromId);
        $redis->set($key, $data);
        return true;
    }


    private static function getGroupSpaceLangKey($fromId)
    {
        return "cache_group_space_set_lang_" . $fromId;
    }
}
