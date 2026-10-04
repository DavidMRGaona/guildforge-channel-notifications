<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Tests\Feature\Infrastructure\Services;

use App\Application\Services\SettingsServiceInterface;
use Modules\ChannelNotifications\Domain\Enums\ContentType;
use Modules\ChannelNotifications\Domain\Enums\NotificationChannel;
use Modules\ChannelNotifications\Infrastructure\Services\ChannelConfigService;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

/**
 * The default templates come from translations, so these cases need the
 * Laravel translator that the pure unit tests in ChannelConfigServiceTest lack.
 */
final class ChannelConfigServiceTemplateFallbackTest extends TestCase
{
    private SettingsServiceInterface&MockObject $settings;

    private ChannelConfigService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['translator']->addNamespace(
            'channel-notifications',
            base_path('modules/channel-notifications/lang'),
        );

        $this->settings = $this->createMock(SettingsServiceInterface::class);
        $this->service = new ChannelConfigService($this->settings);
    }

    public function test_get_template_falls_back_when_empty(): void
    {
        $this->settings
            ->method('get')
            ->with('notifications_template_telegram_event')
            ->willReturn(null);

        $result = $this->service->getTemplate(NotificationChannel::Telegram, ContentType::Event);

        $this->assertDefaultTemplate('telegram', 'event', $result);
    }

    public function test_get_template_does_not_return_custom_when_value_is_empty_string(): void
    {
        $this->settings
            ->method('get')
            ->with('notifications_template_telegram_article')
            ->willReturn('');

        $result = $this->service->getTemplate(NotificationChannel::Telegram, ContentType::Article);

        $this->assertDefaultTemplate('telegram', 'article', $result);
    }

    private function assertDefaultTemplate(string $channel, string $contentType, string $result): void
    {
        $key = "channel-notifications::messages.defaults.{$channel}.{$contentType}";

        // Guard against a missing translation, where __() would echo the key back
        $this->assertNotSame($key, $result);
        $this->assertSame(__($key), $result);
        $this->assertStringContainsString('{title}', $result);
    }
}
