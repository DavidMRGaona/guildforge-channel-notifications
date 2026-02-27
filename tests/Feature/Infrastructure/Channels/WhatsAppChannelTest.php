<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Tests\Feature\Infrastructure\Channels;

use App\Application\Services\SettingsServiceInterface;
use Illuminate\Http\Client\Request;
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
            recipients: '+34600000000',
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

        Http::assertSent(function (Request $request): bool {
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
            recipients: '+34600000000',
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

        Http::assertSent(function (Request $request): bool {
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
            recipients: '+34600000000',
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

    public function test_it_skips_when_no_recipients_and_no_webhook(): void
    {
        Http::fake();

        $this->mockConfigService(
            accessToken: 'EAAx-test-token',
            phoneNumberId: '1234567890',
            recipients: '',
            template: '{title}',
            webhookUrl: '',
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

    public function test_it_sends_to_multiple_recipients(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.123']]])]);

        $this->mockConfigService(
            accessToken: 'EAAx-test-token',
            phoneNumberId: '1234567890',
            recipients: '+34600000001,+34600000002',
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

        $sentRecipients = [];
        Http::assertSent(function (Request $request) use (&$sentRecipients): bool {
            if (str_contains($request->url(), 'graph.facebook.com/v21.0/1234567890/messages')) {
                $sentRecipients[] = $request['to'];

                return true;
            }

            return false;
        });

        Http::assertSentCount(2);
        $this->assertContains('+34600000001', $sentRecipients);
        $this->assertContains('+34600000002', $sentRecipients);
    }

    public function test_it_sends_to_webhook_when_configured(): void
    {
        Http::fake(['https://bot.example.com/webhook' => Http::response(['ok' => true])]);

        $this->mockConfigService(
            accessToken: '',
            phoneNumberId: '',
            recipients: '',
            template: '{title}',
            webhookUrl: 'https://bot.example.com/webhook',
        );

        $this->mockSettingsService('GuildForge');

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

        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== 'https://bot.example.com/webhook') {
                return false;
            }

            $body = $request->data();

            return $body['channel'] === 'whatsapp'
                && isset($body['timestamp'])
                && $body['message']['text'] !== ''
                && $body['message']['image_url'] === null
                && $body['metadata']['content_type'] === 'event'
                && $body['metadata']['title'] === 'Test event'
                && $body['metadata']['content_url'] === 'https://example.com/eventos/test'
                && $body['metadata']['guild_name'] === 'GuildForge';
        });

        Http::assertSentCount(1);
    }

    public function test_it_sends_to_both_recipients_and_webhook(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.123']]])]);

        $this->mockConfigService(
            accessToken: 'EAAx-test-token',
            phoneNumberId: '1234567890',
            recipients: '+34600000001,+34600000002',
            template: '{title}',
            webhookUrl: 'https://bot.example.com/webhook',
        );

        $this->mockSettingsService('GuildForge');

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

        Http::assertSentCount(3);

        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), 'graph.facebook.com/v21.0/1234567890/messages')
                && $request['to'] === '+34600000001';
        });

        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), 'graph.facebook.com/v21.0/1234567890/messages')
                && $request['to'] === '+34600000002';
        });

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://bot.example.com/webhook'
                && $request->data()['channel'] === 'whatsapp';
        });
    }

    public function test_it_continues_on_partial_failure(): void
    {
        Http::fake([
            '*' => Http::sequence()
                ->push(['error' => ['message' => 'Invalid recipient']], 400)
                ->push(['messages' => [['id' => 'wamid.456']]], 200),
        ]);

        $this->mockConfigService(
            accessToken: 'EAAx-test-token',
            phoneNumberId: '1234567890',
            recipients: '+34600000001,+34600000002',
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

        Http::assertSentCount(2);
    }

    public function test_it_trims_whitespace_from_recipients(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.123']]])]);

        $this->mockConfigService(
            accessToken: 'EAAx-test-token',
            phoneNumberId: '1234567890',
            recipients: ' +34600000001 , +34600000002 ',
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

        Http::assertSentCount(2);

        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), 'graph.facebook.com/v21.0/1234567890/messages')
                && $request['to'] === '+34600000001';
        });

        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), 'graph.facebook.com/v21.0/1234567890/messages')
                && $request['to'] === '+34600000002';
        });
    }

    public function test_it_skips_empty_entries_in_recipients(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.123']]])]);

        $this->mockConfigService(
            accessToken: 'EAAx-test-token',
            phoneNumberId: '1234567890',
            recipients: '+34600000001,,  ,+34600000002',
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

        Http::assertSentCount(2);

        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), 'graph.facebook.com/v21.0/1234567890/messages')
                && $request['to'] === '+34600000001';
        });

        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), 'graph.facebook.com/v21.0/1234567890/messages')
                && $request['to'] === '+34600000002';
        });
    }

    private function mockConfigService(
        string $accessToken,
        string $phoneNumberId,
        string $recipients,
        string $template,
        string $webhookUrl = '',
    ): void {
        $configService = $this->createMock(ChannelConfigServiceInterface::class);
        $configService->method('getChannelCredentials')
            ->with(NotificationChannel::WhatsApp)
            ->willReturn([
                'access_token' => $accessToken,
                'phone_number_id' => $phoneNumberId,
                'recipients' => $recipients,
                'webhook_url' => $webhookUrl,
            ]);
        $configService->method('getTemplate')->willReturn($template);

        $this->app->instance(ChannelConfigServiceInterface::class, $configService);
    }

    private function mockSettingsService(string $guildName): void
    {
        $settingsService = $this->createMock(SettingsServiceInterface::class);
        $settingsService->method('get')->willReturn($guildName);
        $this->app->instance(SettingsServiceInterface::class, $settingsService);
    }
}
