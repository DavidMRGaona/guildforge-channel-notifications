<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Tests\Feature\Filament;

use App\Application\Authorization\Services\PermissionRegistryInterface;
use App\Application\Services\SettingsServiceInterface;
use App\Filament\Clusters\SettingsCluster;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Modules\ChannelNotifications\ChannelNotificationsServiceProvider;
use Modules\ChannelNotifications\Domain\Enums\ContentType;
use Modules\ChannelNotifications\Filament\Pages\NotificationChannelSettings;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function test_the_page_lives_in_the_settings_cluster_in_its_slot(): void
    {
        $this->assertSame(SettingsCluster::class, NotificationChannelSettings::getCluster());
        $this->assertSame(140, NotificationChannelSettings::getNavigationSort());
        $this->assertSame('Notificaciones a canales', NotificationChannelSettings::getNavigationLabel());
        $this->assertStringStartsWith(
            '/admin/configuracion/',
            (string) parse_url(NotificationChannelSettings::getUrl(), PHP_URL_PATH),
        );
    }

    public function test_the_page_declares_no_navigation_group_of_its_own(): void
    {
        // The cluster entry sits in the core's «Sistema» group, so the page must not name one
        $this->assertNull(NotificationChannelSettings::getNavigationGroup());
    }

    public function test_the_module_registers_no_navigation_groups(): void
    {
        $provider = new ChannelNotificationsServiceProvider($this->app);

        $this->assertSame([], $provider->registerNavigationGroups());
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

    public function test_a_user_with_the_manage_permission_can_save_the_settings(): void
    {
        $this->actingAs($this->editorWithPermissions(['channel-notifications:notifications.manage']));

        Livewire::test(NotificationChannelSettings::class)
            ->fillForm([
                'notifications_telegram_enabled' => true,
                'notifications_telegram_chat_id' => '-100456',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('-100456', app(SettingsServiceInterface::class)->get('notifications_telegram_chat_id'));
    }

    public function test_a_user_with_the_manage_permission_can_send_a_test_notification(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $this->actingAs($this->editorWithPermissions(['channel-notifications:notifications.manage']));

        Livewire::test(NotificationChannelSettings::class)
            ->fillForm([
                'notifications_telegram_enabled' => true,
                'notifications_telegram_bot_token' => 'test-token',
                'notifications_telegram_chat_id' => '-100456',
            ])
            ->call('sendTest', 'telegram', 'event')
            ->assertNotified(__('channel-notifications::messages.settings.actions.test_success'));

        Http::assertSent(static fn (Request $request): bool => $request->url() === 'https://api.telegram.org/bottest-token/sendMessage'
            && $request['chat_id'] === '-100456');
    }

    public function test_the_telegram_test_message_escapes_the_values_for_html(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $this->app->make(SettingsServiceInterface::class)->set('guild_name', 'Dados & Rol');
        $this->actingAs($this->editorWithPermissions(['channel-notifications:notifications.manage']));

        Livewire::test(NotificationChannelSettings::class)
            ->fillForm([
                'notifications_telegram_enabled' => true,
                'notifications_telegram_bot_token' => 'test-token',
                'notifications_telegram_chat_id' => '-100456',
                'notifications_template_telegram_event' => '<b>{guild_name}</b>',
            ])
            ->call('sendTest', 'telegram', 'event')
            ->assertNotified(__('channel-notifications::messages.settings.actions.test_success'));

        Http::assertSent(static fn (Request $request): bool => $request['text'] === '<b>Dados &amp; Rol</b>');
    }

    public function test_a_survey_test_message_can_be_sent(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $this->actingAs($this->editorWithPermissions(['channel-notifications:notifications.manage']));

        Livewire::test(NotificationChannelSettings::class)
            ->fillForm([
                'notifications_telegram_enabled' => true,
                'notifications_telegram_bot_token' => 'test-token',
                'notifications_telegram_chat_id' => '-100456',
                'notifications_telegram_content_types' => [ContentType::Survey->value],
            ])
            ->call('sendTest', 'telegram', 'survey')
            ->assertNotified(__('channel-notifications::messages.settings.actions.test_success'));

        Http::assertSent(static fn (Request $request): bool => str_contains((string) $request['text'], (string) __('channel-notifications::messages.test.survey.title')));
    }

    /**
     * @return array<string, array{string, array<string, mixed>, string}>
     */
    public static function incompleteChannels(): array
    {
        $on = static fn (string $channel): array => ["notifications_{$channel}_enabled" => true];

        return [
            'telegram without token or chat' => ['telegram', $on('telegram'), 'Token del bot, ID del chat'],
            'telegram without chat' => ['telegram', $on('telegram') + ['notifications_telegram_bot_token' => 'tok'], 'ID del chat'],
            'telegram with blank values' => ['telegram', $on('telegram') + ['notifications_telegram_bot_token' => '  ', 'notifications_telegram_chat_id' => ' '], 'Token del bot, ID del chat'],
            'discord without webhook' => ['discord', $on('discord'), 'URL del webhook'],
            'slack without webhook' => ['slack', $on('slack'), 'URL del webhook'],
        ];
    }

    /**
     * @param  array<string, mixed>  $form
     */
    #[DataProvider('incompleteChannels')]
    public function test_the_test_message_names_the_missing_form_fields(string $channel, array $form, string $missing): void
    {
        Http::fake();

        Livewire::test(NotificationChannelSettings::class)
            ->fillForm($form)
            ->call('sendTest', $channel, 'event')
            ->assertNotified(__('channel-notifications::messages.settings.actions.test_missing_fields', ['fields' => $missing]))
            ->assertNotNotified(__('channel-notifications::messages.settings.actions.test_success'));

        Http::assertNothingSent();
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function incompleteWhatsApp(): array
    {
        $on = ['notifications_whatsapp_enabled' => true];

        return [
            'nothing filled in' => [$on, 'Destinatarios, Token de acceso, ID del número de teléfono'],
            'recipients without token or number id' => [$on + ['notifications_whatsapp_recipients' => '34612345678'], 'Token de acceso, ID del número de teléfono'],
            'recipients and token without number id' => [$on + ['notifications_whatsapp_recipients' => '34612345678', 'notifications_whatsapp_access_token' => 'tok'], 'ID del número de teléfono'],
            'only separators as recipients' => [$on + ['notifications_whatsapp_recipients' => ' , ,', 'notifications_whatsapp_access_token' => 'tok', 'notifications_whatsapp_phone_number_id' => '1'], 'Destinatarios'],
        ];
    }

    /**
     * @param  array<string, mixed>  $form
     */
    #[DataProvider('incompleteWhatsApp')]
    public function test_the_whatsapp_test_does_not_pass_without_the_api_data_or_a_webhook(array $form, string $missing): void
    {
        Http::fake();

        Livewire::test(NotificationChannelSettings::class)
            ->fillForm($form)
            ->call('sendTest', 'whatsapp', 'event')
            ->assertNotified(__('channel-notifications::messages.settings.actions.test_missing_whatsapp', ['fields' => $missing]))
            ->assertNotNotified(__('channel-notifications::messages.settings.actions.test_success'));

        Http::assertNothingSent();
    }

    public function test_the_whatsapp_test_goes_through_a_webhook_alone(): void
    {
        Http::fake(['hooks.example.test/*' => Http::response([], 200)]);

        Livewire::test(NotificationChannelSettings::class)
            ->fillForm([
                'notifications_whatsapp_enabled' => true,
                'notifications_whatsapp_webhook_url' => 'https://hooks.example.test/wa',
            ])
            ->call('sendTest', 'whatsapp', 'event')
            ->assertNotified(__('channel-notifications::messages.settings.actions.test_success'));

        Http::assertSentCount(1);
    }

    public function test_the_whatsapp_test_goes_through_the_api_with_all_its_data(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => []], 200)]);

        Livewire::test(NotificationChannelSettings::class)
            ->fillForm([
                'notifications_whatsapp_enabled' => true,
                'notifications_whatsapp_recipients' => '34612345678, 34698765432',
                'notifications_whatsapp_access_token' => 'tok',
                'notifications_whatsapp_phone_number_id' => '555',
            ])
            ->call('sendTest', 'whatsapp', 'event')
            ->assertNotified(__('channel-notifications::messages.settings.actions.test_success'));

        Http::assertSent(static fn (Request $request): bool => $request->url() === 'https://graph.facebook.com/v21.0/555/messages'
            && $request['to'] === '34612345678');
    }

    public function test_the_test_message_says_the_channel_is_disabled_instead_of_asking_to_save(): void
    {
        Http::fake();

        Livewire::test(NotificationChannelSettings::class)
            ->fillForm(['notifications_telegram_enabled' => false])
            ->call('sendTest', 'telegram', 'event')
            ->assertNotified(__('channel-notifications::messages.settings.actions.test_disabled'));

        Http::assertNothingSent();
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
