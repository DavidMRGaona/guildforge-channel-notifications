<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Tests\Feature;

use App\Application\Knowledge\Services\KnowledgeQueryServiceInterface;
use App\Domain\Knowledge\Enums\GuideAudience;
use App\Domain\Knowledge\ValueObjects\GuideId;
use App\Filament\Pages\KnowledgeBasePage;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Livewire\Livewire;
use Modules\ChannelNotifications\Filament\Pages\NotificationChannelSettings;
use Tests\Support\Authorization\CreatesUsersWithPermissions;
use Tests\Support\Modules\InteractsWithModulePanel;
use Tests\Support\Modules\ModuleTestCase;

/**
 * The module's help guides pass the knowledge base lint, show only to whoever manages the
 * channels, and the settings page opens them.
 */
final class KnowledgeGuidesTest extends ModuleTestCase
{
    use CreatesUsersWithPermissions;
    use InteractsWithModulePanel;

    private const array PANEL_GUIDES = [
        'channel-notifications.panel.discord-slack',
        'channel-notifications.panel.overview',
        'channel-notifications.panel.telegram',
        'channel-notifications.panel.templates',
        'channel-notifications.panel.test-message',
        'channel-notifications.panel.whatsapp',
    ];

    protected ?string $moduleName = 'channel-notifications';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        // The panel guides link to the module's settings page, routed only once it is on the panel
        $this->registerOnAdminPanel(pages: [NotificationChannelSettings::class]);
    }

    public function test_guides_are_valid(): void
    {
        $this->assertModuleKnowledgeGuidesAreValid('channel-notifications');
    }

    public function test_the_guides_render_without_raw_markup_for_an_admin(): void
    {
        $this->syncGuides();
        $admin = UserModel::factory()->admin()->create();

        $this->assertSame(self::PANEL_GUIDES, $this->visiblePanelGuides($admin));
        foreach (self::PANEL_GUIDES as $id) {
            $guide = $this->queries()->guide($admin, GuideAudience::Panel, GuideId::fromString($id));
            $this->assertNotNull($guide, $id);
            $this->assertStringNotContainsString('[[', $guide->html, $id);
            $this->assertStringNotContainsString('{{', $guide->html, $id);
            $this->assertStringNotContainsString('[!', $guide->html, $id);
        }
    }

    public function test_only_whoever_manages_the_channels_sees_the_guides(): void
    {
        $this->syncGuides();

        $this->assertSame(
            self::PANEL_GUIDES,
            $this->visiblePanelGuides($this->editorWithPermissions([NotificationChannelSettings::MANAGE_PERMISSION])),
        );
        $this->assertSame([], $this->visiblePanelGuides(UserModel::factory()->editor()->create()));
        $this->assertSame([], $this->visibleSiteGuides(UserModel::factory()->admin()->create()));
    }

    public function test_the_settings_page_opens_the_overview_guide(): void
    {
        $this->syncGuides();
        $this->actingAsPanelAdmin();

        Livewire::test(NotificationChannelSettings::class)
            ->assertActionVisible('contextualHelp')
            ->assertActionHasUrl(
                'contextualHelp',
                KnowledgeBasePage::getUrl(['guia' => 'channel-notifications.panel.overview']),
            );
    }

    private function syncGuides(): void
    {
        $this->artisanCommand('knowledge:sync')->assertSuccessful()->run();
    }

    /**
     * @return list<string>
     */
    private function visiblePanelGuides(UserModel $user): array
    {
        return $this->moduleGuides($this->queries()->visibleIds($user, GuideAudience::Panel));
    }

    /**
     * @return list<string>
     */
    private function visibleSiteGuides(UserModel $user): array
    {
        return $this->moduleGuides($this->queries()->visibleIds($user, GuideAudience::Site));
    }

    /**
     * @param  array<string>  $ids
     * @return list<string>
     */
    private function moduleGuides(array $ids): array
    {
        $ids = array_values(array_filter(
            $ids,
            static fn (string $id): bool => str_starts_with($id, 'channel-notifications.'),
        ));
        sort($ids);

        return $ids;
    }

    private function queries(): KnowledgeQueryServiceInterface
    {
        return $this->app->make(KnowledgeQueryServiceInterface::class);
    }
}
