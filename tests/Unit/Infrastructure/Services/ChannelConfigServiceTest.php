<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Tests\Unit\Infrastructure\Services;

use App\Application\Services\SettingsServiceInterface;
use Modules\ChannelNotifications\Domain\Enums\ContentType;
use Modules\ChannelNotifications\Domain\Enums\NotificationChannel;
use Modules\ChannelNotifications\Infrastructure\Services\ChannelConfigService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ChannelConfigServiceTest extends TestCase
{
    private SettingsServiceInterface&MockObject $settings;

    private ChannelConfigService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings = $this->createMock(SettingsServiceInterface::class);
        $this->service = new ChannelConfigService($this->settings);
    }

    public function test_is_channel_enabled_returns_true_when_setting_is_1(): void
    {
        $this->settings
            ->method('get')
            ->with('notifications_telegram_enabled', '0')
            ->willReturn('1');

        $result = $this->service->isChannelEnabled(NotificationChannel::Telegram);

        $this->assertTrue($result);
    }

    public function test_is_channel_enabled_returns_false_when_setting_is_0(): void
    {
        $this->settings
            ->method('get')
            ->with('notifications_discord_enabled', '0')
            ->willReturn('0');

        $result = $this->service->isChannelEnabled(NotificationChannel::Discord);

        $this->assertFalse($result);
    }

    public function test_is_channel_enabled_returns_true_when_setting_is_true_string(): void
    {
        $this->settings
            ->method('get')
            ->with('notifications_slack_enabled', '0')
            ->willReturn('true');

        $result = $this->service->isChannelEnabled(NotificationChannel::Slack);

        $this->assertTrue($result);
    }

    public function test_is_channel_enabled_returns_true_when_setting_is_boolean_true(): void
    {
        $this->settings
            ->method('get')
            ->with('notifications_whatsapp_enabled', '0')
            ->willReturn(true);

        $result = $this->service->isChannelEnabled(NotificationChannel::WhatsApp);

        $this->assertTrue($result);
    }

    public function test_get_channel_credentials_for_telegram(): void
    {
        $this->settings
            ->method('getEncrypted')
            ->with('notifications_telegram_bot_token', '')
            ->willReturn('bot123456:ABC-DEF');

        $this->settings
            ->method('get')
            ->with('notifications_telegram_chat_id', '')
            ->willReturn('-100123456789');

        $credentials = $this->service->getChannelCredentials(NotificationChannel::Telegram);

        $this->assertSame('bot123456:ABC-DEF', $credentials['bot_token']);
        $this->assertSame('-100123456789', $credentials['chat_id']);
        $this->assertCount(2, $credentials);
    }

    public function test_get_channel_credentials_for_discord(): void
    {
        $this->settings
            ->method('getEncrypted')
            ->with('notifications_discord_webhook_url', '')
            ->willReturn('https://discord.com/api/webhooks/123/abc');

        $credentials = $this->service->getChannelCredentials(NotificationChannel::Discord);

        $this->assertSame('https://discord.com/api/webhooks/123/abc', $credentials['webhook_url']);
        $this->assertCount(1, $credentials);
    }

    public function test_get_channel_credentials_for_slack(): void
    {
        $this->settings
            ->method('getEncrypted')
            ->with('notifications_slack_webhook_url', '')
            ->willReturn('https://hooks.slack.com/services/T00/B00/xxx');

        $credentials = $this->service->getChannelCredentials(NotificationChannel::Slack);

        $this->assertSame('https://hooks.slack.com/services/T00/B00/xxx', $credentials['webhook_url']);
        $this->assertCount(1, $credentials);
    }

    public function test_get_channel_credentials_for_whatsapp(): void
    {
        $matcher = $this->exactly(2);
        $this->settings
            ->method('get')
            ->willReturnCallback(function (string $key, mixed $default) use ($matcher): mixed {
                $matcher->numberOfInvocations();

                return match ($key) {
                    'notifications_whatsapp_phone_number_id' => '1234567890',
                    'notifications_whatsapp_recipient' => '+34600000000',
                    default => $default,
                };
            });

        $this->settings
            ->method('getEncrypted')
            ->with('notifications_whatsapp_access_token', '')
            ->willReturn('EAAx...');

        $credentials = $this->service->getChannelCredentials(NotificationChannel::WhatsApp);

        $this->assertSame('EAAx...', $credentials['access_token']);
        $this->assertSame('1234567890', $credentials['phone_number_id']);
        $this->assertSame('+34600000000', $credentials['recipient']);
        $this->assertCount(3, $credentials);
    }

    public function test_get_enabled_content_types_parses_json(): void
    {
        $this->settings
            ->method('get')
            ->with('notifications_telegram_content_types', '[]')
            ->willReturn('["event","article"]');

        $result = $this->service->getEnabledContentTypes(NotificationChannel::Telegram);

        $this->assertCount(2, $result);
        $this->assertSame(ContentType::Event, $result[0]);
        $this->assertSame(ContentType::Article, $result[1]);
    }

    public function test_get_enabled_content_types_returns_empty_for_invalid_json(): void
    {
        $this->settings
            ->method('get')
            ->with('notifications_discord_content_types', '[]')
            ->willReturn('not-json');

        $result = $this->service->getEnabledContentTypes(NotificationChannel::Discord);

        $this->assertEmpty($result);
    }

    public function test_get_enabled_content_types_returns_empty_for_empty_array(): void
    {
        $this->settings
            ->method('get')
            ->with('notifications_slack_content_types', '[]')
            ->willReturn('[]');

        $result = $this->service->getEnabledContentTypes(NotificationChannel::Slack);

        $this->assertEmpty($result);
    }

    public function test_get_template_returns_custom_when_saved(): void
    {
        $this->settings
            ->method('get')
            ->with('notifications_template_telegram_event')
            ->willReturn('Custom: {title} at {guild_name}');

        $result = $this->service->getTemplate(NotificationChannel::Telegram, ContentType::Event);

        $this->assertSame('Custom: {title} at {guild_name}', $result);
    }

    public function test_get_template_falls_back_when_empty(): void
    {
        // getDefaultTemplate() calls __() which requires the Laravel translator service.
        // In pure PHPUnit (without Laravel boot), the translator binding is unavailable.
        $this->markTestSkipped(
            'Requires Laravel application context: getDefaultTemplate() uses __() translation helper.',
        );
    }

    public function test_get_template_does_not_return_custom_when_value_is_empty_string(): void
    {
        $this->settings
            ->method('get')
            ->with('notifications_template_telegram_article')
            ->willReturn('');

        // Like the test above, this falls back to getDefaultTemplate() which needs __().
        $this->markTestSkipped(
            'Requires Laravel application context: getDefaultTemplate() uses __() translation helper.',
        );
    }
}
