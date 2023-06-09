<?php

return [
    "api_key" => env("TWITTER_API_KEY"),
    "api_key_secret" => env("TWITTER_API_KEY_SECRET"),
    'api_uri' => "https://api.twitter.com/",
    'twitter_user_id' => 1569604967694213122,
    'twitter_link' => "https://mobile.twitter.com/alphagramapp",
    'twitter_article_link' => "https://mobile.twitter.com/alphagramapp/status/",

    //需要修改
    'twitter_chat_id' => env("TWITTER_CHAT_ID", -1001863486628),//推特官方群
    'twitter_chat_link' => env('TWITTER_CHAT_LINK', 'https://t.me/+raohXKJlJa8xZmY1'),
    'twitter_airdrop_nft_group' => env('TWITTER_AIRDROP_NFT_GROUP', 1789420211),
    'twitter_airdrop_nft_group_list' => env('TWITTER_AIRDROP_NFT_GROUP_LIST', "1789420211,1863486628"),
    'twitter_art_id' => env("TWITTER_ART_ID", 1596114032032817153),  //默认的推特文章ID 判断是否转发了

    //bot
    'bot_token' => env('TELEGRAM_TWITTER_BOT_TOKEN'),
    'bot_name' => env('TELEGRAM_TWITTER_BOT_NAME', 'kangtk_alphagramofficial_bot'),
    'bot_id' => env('TELEGRAM_TWITTER_BOT_ID'),
    'callback_query' => [
        'airdrop_status' => 'Airdrop_status_callback',
        'twitter_account' => 'Twitter_account_callback',
    ],
];
