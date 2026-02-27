<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Infrastructure\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\ChannelNotifications\Notifications\ContentPublishedNotification;

final class DiscordChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notification instanceof ContentPublishedNotification) {
            return;
        }

        $data = $notification->toDiscord();

        if ($data['webhook_url'] === '') {
            return;
        }

        $embed = [
            'title' => $data['title'],
            'description' => $data['text'],
            'url' => $data['content_url'],
            'color' => 0xD97706,
        ];

        if ($data['image_url'] !== null && $data['image_url'] !== '') {
            $embed['image'] = ['url' => $data['image_url']];
        }

        try {
            $response = Http::timeout(10)->post($data['webhook_url'], [
                'embeds' => [$embed],
            ]);

            if ($response->failed()) {
                Log::warning('Discord notification failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Discord notification error', ['error' => $e->getMessage()]);
        }
    }
}
