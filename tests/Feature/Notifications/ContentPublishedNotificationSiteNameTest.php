<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Tests\Feature\Notifications;

use App\Application\Services\SettingsServiceInterface;
use Modules\ChannelNotifications\Application\Services\ChannelConfigServiceInterface;
use Modules\ChannelNotifications\Domain\Enums\ContentType;
use Modules\ChannelNotifications\Domain\ValueObjects\NotificationMessage;
use Modules\ChannelNotifications\Notifications\ContentPublishedNotification;
use Tests\Support\Modules\ModuleTestCase;

/**
 * {guild_name} is the site's name: the association's name from the site settings, or the
 * installation's name (APP_NAME) when that is blank.
 */
final class ContentPublishedNotificationSiteNameTest extends ModuleTestCase
{
    protected ?string $moduleName = 'channel-notifications';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        $configService = $this->createStub(ChannelConfigServiceInterface::class);
        $configService->method('getChannelCredentials')->willReturn([
            'bot_token' => 'token',
            'chat_id' => '1',
            'access_token' => 'token',
            'phone_number_id' => '1',
            'recipients' => '+34600000000',
            'webhook_url' => '',
        ]);
        $configService->method('getTemplate')->willReturn('{guild_name}: {title}');
        $this->app->instance(ChannelConfigServiceInterface::class, $configService);
    }

    public function test_the_templates_use_the_association_name(): void
    {
        $this->app->make(SettingsServiceInterface::class)->set('guild_name', 'Example guild');

        $whatsApp = $this->notification()->toWhatsApp();

        $this->assertSame('Example guild: Sample event', $this->notification()->toTelegram()['text']);
        $this->assertSame('Example guild: Sample event', $whatsApp['text']);
        $this->assertSame('Example guild', $whatsApp['guild_name']);
    }

    public function test_a_blank_association_name_falls_back_to_the_installation_name(): void
    {
        config(['app.name' => 'Example installation']);
        $this->app->make(SettingsServiceInterface::class)->set('guild_name', '');

        $whatsApp = $this->notification()->toWhatsApp();

        $this->assertSame('Example installation: Sample event', $this->notification()->toTelegram()['text']);
        $this->assertSame('Example installation', $whatsApp['guild_name']);
    }

    private function notification(): ContentPublishedNotification
    {
        return new ContentPublishedNotification(new NotificationMessage(
            title: 'Sample event',
            excerpt: 'Sample description',
            imageUrl: null,
            contentUrl: 'https://example.com/eventos/sample',
            contentType: ContentType::Event,
        ));
    }
}
