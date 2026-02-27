<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Tests\Feature\Infrastructure\Channels;

use Illuminate\Support\Facades\Http;
use Modules\ChannelNotifications\Application\Services\ChannelConfigServiceInterface;
use Modules\ChannelNotifications\Domain\Enums\ContentType;
use Modules\ChannelNotifications\Domain\Enums\NotificationChannel;
use Modules\ChannelNotifications\Domain\ValueObjects\NotificationMessage;
use Modules\ChannelNotifications\Infrastructure\Channels\TelegramChannel;
use Modules\ChannelNotifications\Notifications\ContentPublishedNotification;
use Modules\ChannelNotifications\Notifications\NotificationTarget;
use Tests\Support\Modules\ModuleTestCase;

final class TelegramChannelTest extends ModuleTestCase
{
    protected ?string $moduleName = 'channel-notifications';

    protected bool $autoEnableModule = true;

    public function test_it_sends_text_message_when_no_image(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);

        $this->mockConfigService(
            botToken: 'test-token',
            chatId: '123456',
            template: '{title} - {excerpt}',
        );

        $notification = new ContentPublishedNotification(
            new NotificationMessage(
                title: 'Test event',
                excerpt: 'Test description',
                imageUrl: null,
                contentUrl: 'https://example.com/eventos/test',
                contentType: ContentType::Event,
            ),
        );

        $channel = new TelegramChannel;
        $channel->send(new NotificationTarget, $notification);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.telegram.org/bottest-token/sendMessage')
                && $request['chat_id'] === '123456'
                && $request['parse_mode'] === 'HTML';
        });
    }

    public function test_it_sends_photo_when_image_url_present(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);

        $this->mockConfigService(
            botToken: 'test-token',
            chatId: '123456',
            template: '{title}',
        );

        $notification = new ContentPublishedNotification(
            new NotificationMessage(
                title: 'Test event',
                excerpt: 'Test description',
                imageUrl: 'https://example.com/image.jpg',
                contentUrl: 'https://example.com/eventos/test',
                contentType: ContentType::Event,
            ),
        );

        $channel = new TelegramChannel;
        $channel->send(new NotificationTarget, $notification);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.telegram.org/bottest-token/sendPhoto')
                && $request['chat_id'] === '123456'
                && $request['photo'] === 'https://example.com/image.jpg'
                && $request['parse_mode'] === 'HTML';
        });
    }

    public function test_it_does_not_send_when_bot_token_empty(): void
    {
        Http::fake();

        $this->mockConfigService(
            botToken: '',
            chatId: '123456',
            template: '{title}',
        );

        $notification = new ContentPublishedNotification(
            new NotificationMessage(
                title: 'Test event',
                excerpt: 'Test description',
                imageUrl: null,
                contentUrl: 'https://example.com/eventos/test',
                contentType: ContentType::Event,
            ),
        );

        $channel = new TelegramChannel;
        $channel->send(new NotificationTarget, $notification);

        Http::assertNothingSent();
    }

    public function test_it_does_not_send_when_chat_id_empty(): void
    {
        Http::fake();

        $this->mockConfigService(
            botToken: 'test-token',
            chatId: '',
            template: '{title}',
        );

        $notification = new ContentPublishedNotification(
            new NotificationMessage(
                title: 'Test event',
                excerpt: 'Test description',
                imageUrl: null,
                contentUrl: 'https://example.com/eventos/test',
                contentType: ContentType::Event,
            ),
        );

        $channel = new TelegramChannel;
        $channel->send(new NotificationTarget, $notification);

        Http::assertNothingSent();
    }

    private function mockConfigService(string $botToken, string $chatId, string $template): void
    {
        $configService = $this->createMock(ChannelConfigServiceInterface::class);
        $configService->method('getChannelCredentials')
            ->with(NotificationChannel::Telegram)
            ->willReturn([
                'bot_token' => $botToken,
                'chat_id' => $chatId,
            ]);
        $configService->method('getTemplate')->willReturn($template);

        $this->app->instance(ChannelConfigServiceInterface::class, $configService);
    }
}
