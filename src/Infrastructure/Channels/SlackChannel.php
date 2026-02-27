<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Infrastructure\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\ChannelNotifications\Notifications\ContentPublishedNotification;

final class SlackChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notification instanceof ContentPublishedNotification) {
            return;
        }

        $data = $notification->toSlack();

        if ($data['webhook_url'] === '') {
            return;
        }

        $blocks = [
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => $data['title'],
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => $data['text'],
                ],
            ],
        ];

        if ($data['image_url'] !== null && $data['image_url'] !== '') {
            $blocks[] = [
                'type' => 'image',
                'image_url' => $data['image_url'],
                'alt_text' => $data['title'],
            ];
        }

        $blocks[] = [
            'type' => 'actions',
            'elements' => [
                [
                    'type' => 'button',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => 'Ver más',
                    ],
                    'url' => $data['content_url'],
                ],
            ],
        ];

        try {
            $response = Http::timeout(10)->post($data['webhook_url'], [
                'blocks' => $blocks,
            ]);

            if ($response->failed()) {
                Log::warning('Slack notification failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Slack notification error', ['error' => $e->getMessage()]);
        }
    }
}
