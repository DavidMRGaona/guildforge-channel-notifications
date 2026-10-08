<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Tests\Feature\Filament;

use App\Application\Services\SettingsServiceInterface;
use Livewire\Livewire;
use Modules\ChannelNotifications\Filament\Pages\NotificationChannelSettings;
use Tests\Support\Modules\InteractsWithModulePanel;
use Tests\Support\Modules\ModuleTestCase;

final class NotificationChannelSettingsTest extends ModuleTestCase
{
    use InteractsWithModulePanel;

    protected ?string $moduleName = 'channel-notifications';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerOnAdminPanel(pages: [NotificationChannelSettings::class]);
        $this->actingAsPanelAdmin();
    }

    public function test_settings_page_renders(): void
    {
        Livewire::test(NotificationChannelSettings::class)->assertOk();
    }

    public function test_saving_stores_the_channel_settings(): void
    {
        Livewire::test(NotificationChannelSettings::class)
            ->fillForm([
                'notifications_telegram_enabled' => true,
                'notifications_telegram_chat_id' => '-100123',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app(SettingsServiceInterface::class);
        $this->assertSame('-100123', $settings->get('notifications_telegram_chat_id'));
    }
}
