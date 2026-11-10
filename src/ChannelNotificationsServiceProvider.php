<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications;

use App\Application\Modules\DTOs\PermissionDTO;
use App\Domain\Forum\Events\ForumDiscussionStarted;
use App\Domain\Surveys\Events\GeneralSurveyOpened;
use App\Infrastructure\Persistence\Eloquent\Models\ArticleModel;
use App\Infrastructure\Persistence\Eloquent\Models\EventModel;
use App\Infrastructure\Persistence\Eloquent\Models\GalleryModel;
use App\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Event;
use Modules\ChannelNotifications\Application\Services\ChannelConfigServiceInterface;
use Modules\ChannelNotifications\Filament\Pages\NotificationChannelSettings;
use Modules\ChannelNotifications\Infrastructure\Observers\ContentPublishedObserver;
use Modules\ChannelNotifications\Infrastructure\Services\ChannelConfigService;
use Modules\ChannelNotifications\Listeners\AnnounceForumDiscussion;
use Modules\ChannelNotifications\Listeners\AnnounceGeneralSurvey;

final class ChannelNotificationsServiceProvider extends ModuleServiceProvider
{
    public function moduleName(): string
    {
        return 'channel-notifications';
    }

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(
            $this->modulePath('config/module.php'),
            'channel_notifications'
        );

        $this->app->bind(ChannelConfigServiceInterface::class, ChannelConfigService::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->registerObservers();

        // The surveys module dispatches it, if installed, when a general survey asks to be announced
        Event::listen(GeneralSurveyOpened::class, AnnounceGeneralSurvey::class);
        // The forum module dispatches it, if installed, for the discussions it lets be announced
        Event::listen(ForumDiscussionStarted::class, AnnounceForumDiscussion::class);
    }

    /**
     * @return array<class-string>
     */
    public function registerFilamentPages(): array
    {
        return [
            NotificationChannelSettings::class,
        ];
    }

    /**
     * @return array<PermissionDTO>
     */
    public function registerPermissions(): array
    {
        return [
            new PermissionDTO(
                name: 'notifications.manage',
                label: __('channel-notifications::messages.permissions.notifications.manage'),
                group: __('channel-notifications::messages.settings.navigation_group'),
                module: 'channel-notifications',
                roles: ['admin'],
            ),
        ];
    }

    private function registerObservers(): void
    {
        EventModel::observe(ContentPublishedObserver::class);
        ArticleModel::observe(ContentPublishedObserver::class);
        GalleryModel::observe(ContentPublishedObserver::class);
    }
}
