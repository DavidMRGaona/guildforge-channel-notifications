<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Infrastructure\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\ChannelNotifications\Notifications\ContentPublishedNotification;

final class WhatsAppChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notification instanceof ContentPublishedNotification) {
            return;
        }

        $data = $notification->toWhatsApp();

        if ($data['access_token'] === '' || $data['phone_number_id'] === '' || $data['recipient'] === '') {
            return;
        }

        $url = "https://graph.facebook.com/v21.0/{$data['phone_number_id']}/messages";

        $payload = $this->buildPayload($data);

        try {
            $response = Http::timeout(10)
                ->withToken($data['access_token'])
                ->post($url, $payload);

            if ($response->failed()) {
                Log::warning('WhatsApp notification failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('WhatsApp notification error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildPayload(array $data): array
    {
        $base = [
            'messaging_product' => 'whatsapp',
            'to' => $data['recipient'],
        ];

        if ($data['image_url'] !== null && $data['image_url'] !== '') {
            return array_merge($base, [
                'type' => 'image',
                'image' => [
                    'link' => $data['image_url'],
                    'caption' => $data['text'],
                ],
            ]);
        }

        return array_merge($base, [
            'type' => 'text',
            'text' => [
                'body' => $data['text'],
            ],
        ]);
    }
}
