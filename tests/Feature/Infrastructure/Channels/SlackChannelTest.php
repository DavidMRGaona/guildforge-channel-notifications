<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Tests\Feature\Infrastructure\Channels;

use Illuminate\Support\Facades\Http;
use Modules\ChannelNotifications\Application\Services\ChannelConfigServiceInterface;
use Modules\ChannelNotifications\Domain\Enums\ContentType;
use Modules\ChannelNotifications\Domain\Enums\NotificationChannel;
use Modules\ChannelNotifications\Domain\ValueObjects\NotificationMessage;
use Modules\ChannelNotifications\Infrastructure\Channels\SlackChannel;
use Modules\ChannelNotifications\Notifications\ContentPublishedNotification;
use Modules\ChannelNotifications\Notifications\NotificationTarget;
use Tests\Support\Modules\ModuleTestCase;

final class SlackChannelTest extends ModuleTestCase
{
    protected ?string $moduleName = 'channel-notifications';

    protected bool $autoEnableModule = true;

    public function test_it_sends_blocks_to_webhook(): void
    {
        Http::fake(['*' => Http::response('ok')]);

        $this->mockConfigService(
            webhookUrl: 'https://hooks.slack.com/services/T00/B00/xxx',
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

        $channel = new SlackChannel;
        $channel->send(new NotificationTarget, $notification);

        Http::assertSent(function ($request) {
            $body = $request->data();
            $blocks = $body['blocks'] ?? [];

            $hasHeader = false;
            $hasSection = false;
            $hasActions = false;
            $hasImage = false;

            foreach ($blocks as $block) {
                if ($block['type'] === 'header' && $block['text']['text'] === 'New event') {
                    $hasHeader = true;
                }
                if ($block['type'] === 'section') {
                    $hasSection = true;
                }
                if ($block['type'] === 'actions') {
                    $hasActions = true;
                }
                if ($block['type'] === 'image') {
                    $hasImage = true;
                }
            }

            return $request->url() === 'https://hooks.slack.com/services/T00/B00/xxx'
                && $hasHeader
                && $hasSection
                && $hasActions
                && ! $hasImage;
        });
    }

    public function test_it_includes_image_block_when_present(): void
    {
        Http::fake(['*' => Http::response('ok')]);

        $this->mockConfigService(
            webhookUrl: 'https://hooks.slack.com/services/T00/B00/xxx',
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

        $channel = new SlackChannel;
        $channel->send(new NotificationTarget, $notification);

        Http::assertSent(function ($request) {
            $body = $request->data();
            $blocks = $body['blocks'] ?? [];

            foreach ($blocks as $block) {
                if ($block['type'] === 'image'
                    && $block['image_url'] === 'https://example.com/image.jpg'
                    && $block['alt_text'] === 'New event') {
                    return true;
                }
            }

            return false;
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

        $channel = new SlackChannel;
        $channel->send(new NotificationTarget, $notification);

        Http::assertNothingSent();
    }

    private function mockConfigService(string $webhookUrl, string $template): void
    {
        $configService = $this->createMock(ChannelConfigServiceInterface::class);
        $configService->method('getChannelCredentials')
            ->with(NotificationChannel::Slack)
            ->willReturn([
                'webhook_url' => $webhookUrl,
            ]);
        $configService->method('getTemplate')->willReturn($template);

        $this->app->instance(ChannelConfigServiceInterface::class, $configService);
    }
}
