<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Infrastructure\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\ChannelNotifications\Notifications\ContentPublishedNotification;

final class TelegramChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notification instanceof ContentPublishedNotification) {
            return;
        }

        $data = $notification->toTelegram();

        if ($data['bot_token'] === '' || $data['chat_id'] === '') {
            return;
        }

        $url = "https://api.telegram.org/bot{$data['bot_token']}";

        try {
            if ($data['image_url'] !== null && $data['image_url'] !== '') {
                $response = Http::timeout(10)->post("{$url}/sendPhoto", [
                    'chat_id' => $data['chat_id'],
                    'photo' => $data['image_url'],
                    'caption' => $data['text'],
                    'parse_mode' => 'HTML',
                ]);
            } else {
                $response = Http::timeout(10)->post("{$url}/sendMessage", [
                    'chat_id' => $data['chat_id'],
                    'text' => $data['text'],
                    'parse_mode' => 'HTML',
                ]);
            }

            if ($response->failed()) {
                Log::warning('Telegram notification failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Telegram notification error', ['error' => $e->getMessage()]);
        }
    }
}
