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

        $recipients = $this->parseRecipients($data['recipients']);
        $webhookUrl = $data['webhook_url'];

        if ($recipients === [] && $webhookUrl === '') {
            return;
        }

        if ($recipients !== []) {
            $this->sendToRecipients($data, $recipients);
        }

        if ($webhookUrl !== '') {
            $this->sendToWebhook($data, $webhookUrl);
        }
    }

    /**
     * @return array<string>
     */
    private function parseRecipients(string $recipients): array
    {
        if ($recipients === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $recipients)),
            static fn (string $r): bool => $r !== '',
        ));
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string>  $recipients
     */
    private function sendToRecipients(array $data, array $recipients): void
    {
        if ($data['access_token'] === '' || $data['phone_number_id'] === '') {
            return;
        }

        $url = "https://graph.facebook.com/v21.0/{$data['phone_number_id']}/messages";

        foreach ($recipients as $recipient) {
            try {
                $payload = $this->buildPayload($data, $recipient);
                $response = Http::timeout(10)
                    ->withToken($data['access_token'])
                    ->post($url, $payload);

                if ($response->failed()) {
                    Log::warning('WhatsApp notification failed', [
                        'recipient' => $recipient,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('WhatsApp notification error', [
                    'recipient' => $recipient,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function sendToWebhook(array $data, string $webhookUrl): void
    {
        try {
            $response = Http::timeout(10)->post($webhookUrl, [
                'channel' => 'whatsapp',
                'timestamp' => now()->toIso8601String(),
                'message' => [
                    'text' => $data['text'],
                    'image_url' => $data['image_url'],
                ],
                'metadata' => [
                    'content_type' => $data['content_type'],
                    'title' => $data['title'],
                    'content_url' => $data['content_url'],
                    'guild_name' => $data['guild_name'],
                ],
            ]);

            if ($response->failed()) {
                Log::warning('WhatsApp webhook failed', [
                    'url' => $webhookUrl,
                    'status' => $response->status(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('WhatsApp webhook error', [
                'url' => $webhookUrl,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildPayload(array $data, string $recipient): array
    {
        $base = [
            'messaging_product' => 'whatsapp',
            'to' => $recipient,
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
