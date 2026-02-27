<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Tests\Feature\Infrastructure\Observers;

use App\Infrastructure\Persistence\Eloquent\Models\ArticleModel;
use App\Infrastructure\Persistence\Eloquent\Models\EventModel;
use App\Infrastructure\Persistence\Eloquent\Models\GalleryModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Facades\Notification;
use Modules\ChannelNotifications\Application\Services\ChannelConfigServiceInterface;
use Modules\ChannelNotifications\Domain\Enums\ContentType;
use Modules\ChannelNotifications\Notifications\ContentPublishedNotification;
use Modules\ChannelNotifications\Notifications\NotificationTarget;
use Tests\Support\Modules\ModuleTestCase;

final class ContentPublishedObserverTest extends ModuleTestCase
{
    protected ?string $moduleName = 'channel-notifications';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        $configService = $this->createMock(ChannelConfigServiceInterface::class);
        $configService->method('isChannelEnabled')->willReturn(true);
        $configService->method('getEnabledContentTypes')->willReturn([
            ContentType::Event,
            ContentType::Article,
            ContentType::Gallery,
        ]);
        $this->app->instance(ChannelConfigServiceInterface::class, $configService);

        Notification::fake();
    }

    public function test_it_dispatches_notification_when_event_created_as_published(): void
    {
        EventModel::factory()->create([
            'is_published' => true,
        ]);

        Notification::assertSentTo(
            new NotificationTarget,
            ContentPublishedNotification::class,
        );
    }

    public function test_it_dispatches_notification_when_event_published_on_update(): void
    {
        $event = EventModel::factory()->create([
            'is_published' => false,
        ]);

        Notification::assertNothingSent();

        $event->update(['is_published' => true]);

        Notification::assertSentTo(
            new NotificationTarget,
            ContentPublishedNotification::class,
        );
    }

    public function test_it_does_not_dispatch_when_event_created_unpublished(): void
    {
        EventModel::factory()->create([
            'is_published' => false,
        ]);

        Notification::assertNothingSent();
    }

    public function test_it_does_not_dispatch_when_already_published_event_updated(): void
    {
        $event = EventModel::factory()->create([
            'is_published' => true,
        ]);

        Notification::fake();

        $event->update(['title' => 'Updated title']);

        Notification::assertNothingSent();
    }

    public function test_it_dispatches_notification_when_article_published(): void
    {
        $user = UserModel::factory()->create();

        ArticleModel::factory()->create([
            'is_published' => true,
            'author_id' => $user->id,
        ]);

        Notification::assertSentTo(
            new NotificationTarget,
            ContentPublishedNotification::class,
        );
    }

    public function test_it_dispatches_notification_when_gallery_published(): void
    {
        GalleryModel::factory()->create([
            'is_published' => true,
        ]);

        Notification::assertSentTo(
            new NotificationTarget,
            ContentPublishedNotification::class,
        );
    }
}
