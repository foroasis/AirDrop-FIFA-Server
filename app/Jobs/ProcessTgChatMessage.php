<?php

namespace App\Jobs;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\TelegramClient;

class ProcessTgChatMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // 图片URL
    protected $message;
    protected $chatId;
    protected $options;
    protected $token;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($message, $chatId, $options = [], $token = "")
    {
        $this->message = $message;
        $this->chatId = $chatId;
        $this->options = $options;
        $this->token = $token;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $token = config('twitter.bot_token');
            $url = TelegramClient::BASE_BOT_URL . $token . '/sendMessage';
            $body = [
                'chat_id' => $this->chatId,
                'text' => $this->message
            ];
            if ($this->options) {
                $body += $this->options;
            }
            $client = new \GuzzleHttp\Client();
            $res = $client->post($url, ['form_params' => $body]);
            $contents = $res->getBody()->getContents();
            Log::info("ProcessTgChatMessage", [$contents]);
        } catch (\Exception $exception) {
            Log::info("ProcessTgChatMessageError", [$exception->getMessage()]);
        }
        return;
    }

}
