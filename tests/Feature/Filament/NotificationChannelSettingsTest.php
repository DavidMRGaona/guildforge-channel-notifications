<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Tests\Feature\Filament;

use App\Application\Authorization\Services\PermissionRegistryInterface;
use App\Application\Services\SettingsServiceInterface;
use Livewire\Livewire;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\ChannelNotifications\Filament\Pages\NotificationChannelSettings;
use Tests\Support\Authorization\CreatesUsersWithPermissions;
use Tests\Support\Modules\InteractsWithModulePanel;
use Tests\Support\Modules\ModuleTestCase;

final class NotificationChannelSettingsTest extends ModuleTestCase
{
    use CreatesUsersWithPermissions;
    use InteractsWithModulePanel;

    protected ?string $moduleName = 'channel-notifications';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerOnAdminPanel(pages: [NotificationChannelSettings::class]);
        $this->actingAsPanelAdmin();
    }

    public function test_an_admin_can_access_the_settings_page(): void
    {
        $this->assertTrue(NotificationChannelSettings::canAccess());
        Livewire::test(NotificationChannelSettings::class)->assertOk();
    }

    public function test_a_user_with_the_manage_permission_can_access_the_settings_page(): void
    {
        $this->actingAs($this->editorWithPermissions(['channel-notifications:notifications.manage']));

        $this->assertTrue(NotificationChannelSettings::canAccess());
        Livewire::test(NotificationChannelSettings::class)->assertOk();
    }

    public function test_a_user_without_the_manage_permission_cannot_access_the_settings_page(): void
    {
        $this->actingAs(UserModel::factory()->editor()->create());

        $this->assertFalse(NotificationChannelSettings::canAccess());
        Livewire::test(NotificationChannelSettings::class)->assertForbidden();
    }

    public function test_the_page_checks_the_permission_the_module_registers(): void
    {
        $permission = app(PermissionRegistryInterface::class)->find(NotificationChannelSettings::MANAGE_PERMISSION);

        $this->assertNotNull($permission);
        $this->assertSame('channel-notifications', $permission->module);
        $this->assertSame(['admin'], $permission->defaultRoles);
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
