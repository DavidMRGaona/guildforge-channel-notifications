<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Tests\Feature\Infrastructure\Channels;

use Illuminate\Support\Facades\Http;
use Modules\ChannelNotifications\Application\Services\ChannelConfigServiceInterface;
use Modules\ChannelNotifications\Domain\Enums\ContentType;
use Modules\ChannelNotifications\Domain\Enums\NotificationChannel;
use Modules\ChannelNotifications\Domain\ValueObjects\NotificationMessage;
use Modules\ChannelNotifications\Infrastructure\Channels\WhatsAppChannel;
use Modules\ChannelNotifications\Notifications\ContentPublishedNotification;
use Modules\ChannelNotifications\Notifications\NotificationTarget;
use Tests\Support\Modules\ModuleTestCase;

final class WhatsAppChannelTest extends ModuleTestCase
{
    protected ?string $moduleName = 'channel-notifications';

    protected bool $autoEnableModule = true;

    public function test_it_sends_text_message_when_no_image(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.123']]])]);

        $this->mockConfigService(
            accessToken: 'EAAx-test-token',
            phoneNumberId: '1234567890',
            recipient: '+34600000000',
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

        $channel = new WhatsAppChannel;
        $channel->send(new NotificationTarget, $notification);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'graph.facebook.com/v21.0/1234567890/messages')
                && $request->hasHeader('Authorization', 'Bearer EAAx-test-token')
                && $request['messaging_product'] === 'whatsapp'
                && $request['to'] === '+34600000000'
                && $request['type'] === 'text'
                && isset($request['text']['body']);
        });
    }

    public function test_it_sends_image_message_when_image_present(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.123']]])]);

        $this->mockConfigService(
            accessToken: 'EAAx-test-token',
            phoneNumberId: '1234567890',
            recipient: '+34600000000',
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

        $channel = new WhatsAppChannel;
        $channel->send(new NotificationTarget, $notification);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'graph.facebook.com/v21.0/1234567890/messages')
                && $request['messaging_product'] === 'whatsapp'
                && $request['to'] === '+34600000000'
                && $request['type'] === 'image'
                && $request['image']['link'] === 'https://example.com/image.jpg';
        });
    }

    public function test_it_does_not_send_when_access_token_empty(): void
    {
        Http::fake();

        $this->mockConfigService(
            accessToken: '',
            phoneNumberId: '1234567890',
            recipient: '+34600000000',
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

        $channel = new WhatsAppChannel;
        $channel->send(new NotificationTarget, $notification);

        Http::assertNothingSent();
    }

    public function test_it_does_not_send_when_recipient_empty(): void
    {
        Http::fake();

        $this->mockConfigService(
            accessToken: 'EAAx-test-token',
            phoneNumberId: '1234567890',
            recipient: '',
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

        $channel = new WhatsAppChannel;
        $channel->send(new NotificationTarget, $notification);

        Http::assertNothingSent();
    }

    private function mockConfigService(
        string $accessToken,
        string $phoneNumberId,
        string $recipient,
        string $template,
    ): void {
        $configService = $this->createMock(ChannelConfigServiceInterface::class);
        $configService->method('getChannelCredentials')
            ->with(NotificationChannel::WhatsApp)
            ->willReturn([
                'access_token' => $accessToken,
                'phone_number_id' => $phoneNumberId,
                'recipient' => $recipient,
            ]);
        $configService->method('getTemplate')->willReturn($template);

        $this->app->instance(ChannelConfigServiceInterface::class, $configService);
    }
}
