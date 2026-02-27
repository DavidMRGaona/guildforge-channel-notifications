<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Tests\Feature\Infrastructure\Channels;

use Illuminate\Support\Facades\Http;
use Modules\ChannelNotifications\Application\Services\ChannelConfigServiceInterface;
use Modules\ChannelNotifications\Domain\Enums\ContentType;
use Modules\ChannelNotifications\Domain\Enums\NotificationChannel;
use Modules\ChannelNotifications\Domain\ValueObjects\NotificationMessage;
use Modules\ChannelNotifications\Infrastructure\Channels\DiscordChannel;
use Modules\ChannelNotifications\Notifications\ContentPublishedNotification;
use Modules\ChannelNotifications\Notifications\NotificationTarget;
use Tests\Support\Modules\ModuleTestCase;

final class DiscordChannelTest extends ModuleTestCase
{
    protected ?string $moduleName = 'channel-notifications';

    protected bool $autoEnableModule = true;

    public function test_it_sends_embed_to_webhook(): void
    {
        Http::fake(['*' => Http::response(null, 204)]);

        $this->mockConfigService(
            webhookUrl: 'https://discord.com/api/webhooks/123/abc',
            template: '{title} - {excerpt}',
        );

        $notification = new ContentPublishedNotification(
            new NotificationMessage(
                title: 'New event',
                excerpt: 'Event description',
                imageUrl: null,
                contentUrl: 'https://example.com/eventos/test',
                contentType: ContentType::Event,
            ),
        );

        $channel = new DiscordChannel;
        $channel->send(new NotificationTarget, $notification);

        Http::assertSent(function ($request) {
            $body = $request->data();
            $embed = $body['embeds'][0] ?? null;

            return $request->url() === 'https://discord.com/api/webhooks/123/abc'
                && $embed !== null
                && $embed['title'] === 'New event'
                && $embed['url'] === 'https://example.com/eventos/test'
                && $embed['color'] === 0xD97706
                && ! isset($embed['image']);
        });
    }

    public function test_it_includes_image_in_embed_when_present(): void
    {
        Http::fake(['*' => Http::response(null, 204)]);

        $this->mockConfigService(
            webhookUrl: 'https://discord.com/api/webhooks/123/abc',
            template: '{title}',
        );

        $notification = new ContentPublishedNotification(
            new NotificationMessage(
                title: 'New event',
                excerpt: 'Event description',
                imageUrl: 'https://example.com/image.jpg',
                contentUrl: 'https://example.com/eventos/test',
                contentType: ContentType::Event,
            ),
        );

        $channel = new DiscordChannel;
        $channel->send(new NotificationTarget, $notification);

        Http::assertSent(function ($request) {
            $body = $request->data();
            $embed = $body['embeds'][0] ?? null;

            return $embed !== null
                && isset($embed['image'])
                && $embed['image']['url'] === 'https://example.com/image.jpg';
        });
    }

    public function test_it_does_not_send_when_webhook_url_empty(): void
    {
        Http::fake();

        $this->mockConfigService(
            webhookUrl: '',
            template: '{title}',
        );

        $notification = new ContentPublishedNotification(
            new NotificationMessage(
                title: 'New event',
                excerpt: 'Event description',
                imageUrl: null,
                contentUrl: 'https://example.com/eventos/test',
                contentType: ContentType::Event,
            ),
        );

        $channel = new DiscordChannel;
        $channel->send(new NotificationTarget, $notification);

        Http::assertNothingSent();
    }

    private function mockConfigService(string $webhookUrl, string $template): void
    {
        $configService = $this->createMock(ChannelConfigServiceInterface::class);
        $configService->method('getChannelCredentials')
            ->with(NotificationChannel::Discord)
            ->willReturn([
                'webhook_url' => $webhookUrl,
            ]);
        $configService->method('getTemplate')->willReturn($template);

        $this->app->instance(ChannelConfigServiceInterface::class, $configService);
    }
}
