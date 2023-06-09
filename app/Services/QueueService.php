<?php

namespace App\Services;


use App\Jobs\ProcessTgChatMessage;

// 队列服务
class QueueService extends BaseService
{

    /**
     * tg的消息
     * @param $message
     * @param $chatId
     * @param array $options
     * @param string $token
     */
    public function doTgChatMessage($message, $chatId, $options = [], $token = "")
    {
        ProcessTgChatMessage::dispatch($message, $chatId, $options, $token)->onQueue(config('game.queue.tgchatmessage'));
    }

}
