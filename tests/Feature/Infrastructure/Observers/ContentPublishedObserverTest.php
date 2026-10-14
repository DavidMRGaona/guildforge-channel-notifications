<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Tests\Feature\Infrastructure\Observers;

use App\Infrastructure\Persistence\Eloquent\Models\ArticleModel;
use App\Infrastructure\Persistence\Eloquent\Models\EventModel;
use App\Infrastructure\Persistence\Eloquent\Models\GalleryModel;
use App\Infrastructure\Persistence\Eloquent\Models\PhotoModel;
use App\Infrastructure\Persistence\Eloquent\Models\TagModel;
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

        $configService = $this->createStub(ChannelConfigServiceInterface::class);
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

    public function test_event_notification_includes_all_extra_data(): void
    {
        $tags = TagModel::factory()->count(2)->sequence(
            ['name' => 'Warhammer'],
            ['name' => 'Torneo'],
        )->create();

        $event = EventModel::factory()->create([
            'is_published' => false,
            'member_price' => 10.00,
            'non_member_price' => 15.00,
            'location' => 'Madrid',
        ]);

        $event->tags()->attach($tags->pluck('id'));

        $event->update(['is_published' => true]);

        Notification::assertSentTo(
            new NotificationTarget,
            function (ContentPublishedNotification $notification): bool {
                $message = $notification->getMessage();

                $this->assertArrayHasKey('date', $message->extraData);
                $this->assertArrayHasKey('end_date', $message->extraData);
                $this->assertArrayHasKey('location', $message->extraData);
                $this->assertArrayHasKey('price', $message->extraData);
                $this->assertArrayHasKey('tags', $message->extraData);
                $this->assertSame('Madrid', $message->extraData['location']);
                $this->assertStringContainsString('10', $message->extraData['price']);
                $this->assertStringContainsString('Warhammer', $message->extraData['tags']);
                $this->assertStringContainsString('Torneo', $message->extraData['tags']);

                return true;
            },
        );
    }

    public function test_article_notification_includes_author_and_tags(): void
    {
        $user = UserModel::factory()->create(['display_name' => 'John Doe']);
        $tag = TagModel::factory()->create(['name' => 'RPG']);

        $article = ArticleModel::factory()->create([
            'is_published' => false,
            'author_id' => $user->id,
        ]);

        $article->tags()->attach($tag->id);

        $article->update(['is_published' => true]);

        Notification::assertSentTo(
            new NotificationTarget,
            function (ContentPublishedNotification $notification): bool {
                $message = $notification->getMessage();

                $this->assertArrayHasKey('author', $message->extraData);
                $this->assertArrayHasKey('tags', $message->extraData);
                $this->assertSame('John Doe', $message->extraData['author']);
                $this->assertSame('RPG', $message->extraData['tags']);

                return true;
            },
        );
    }

    public function test_gallery_notification_includes_photo_count_and_tags(): void
    {
        $tag = TagModel::factory()->create(['name' => 'Fotos']);

        $gallery = GalleryModel::factory()->create([
            'is_published' => false,
        ]);

        $gallery->tags()->attach($tag->id);
        PhotoModel::factory()->count(5)->create(['gallery_id' => $gallery->id]);

        $gallery->update(['is_published' => true]);

        Notification::assertSentTo(
            new NotificationTarget,
            function (ContentPublishedNotification $notification): bool {
                $message = $notification->getMessage();

                $this->assertArrayHasKey('photo_count', $message->extraData);
                $this->assertArrayHasKey('tags', $message->extraData);
                $this->assertSame('5', $message->extraData['photo_count']);
                $this->assertSame('Fotos', $message->extraData['tags']);

                return true;
            },
        );
    }

    public function test_event_with_free_price_shows_gratis(): void
    {
        EventModel::factory()->create([
            'is_published' => true,
            'member_price' => null,
            'non_member_price' => null,
        ]);

        Notification::assertSentTo(
            new NotificationTarget,
            function (ContentPublishedNotification $notification): bool {
                $message = $notification->getMessage();

                $this->assertSame('Gratis', $message->extraData['price']);

                return true;
            },
        );
    }

    public function test_the_price_texts_come_from_the_module_translations(): void
    {
        $this->overrideTranslations([
            'messages.prices.free' => 'Free entry',
            'messages.prices.member_and_non_member' => 'Members :member_price · Others :non_member_price',
            'messages.prices.single' => ':price EUR',
        ]);

        EventModel::factory()->create(['is_published' => true, 'member_price' => null, 'non_member_price' => null]);
        EventModel::factory()->create(['is_published' => true, 'member_price' => 10, 'non_member_price' => 15]);
        EventModel::factory()->create(['is_published' => true, 'member_price' => 8, 'non_member_price' => 8]);

        $prices = [];
        Notification::assertSentTo(
            new NotificationTarget,
            function (ContentPublishedNotification $notification) use (&$prices): bool {
                $prices[] = $notification->getMessage()->extraData['price'];

                return true;
            },
        );

        $this->assertSame(['Free entry', 'Members 10.00 · Others 15.00', '8.00 EUR'], $prices);
    }

    public function test_the_spanish_price_texts(): void
    {
        $this->assertSame('Gratis', __('channel-notifications::messages.prices.free'));
        $this->assertSame(
            'Socios: 10€ · No socios: 15€',
            __('channel-notifications::messages.prices.member_and_non_member', ['member_price' => '10', 'non_member_price' => '15']),
        );
        $this->assertSame('8€', __('channel-notifications::messages.prices.single', ['price' => '8']));
    }

    /**
     * @param  array<string, string>  $lines
     */
    private function overrideTranslations(array $lines): void
    {
        // Load the module's group first so the overrides are merged into it
        __('channel-notifications::messages.title');
        app('translator')->addLines($lines, app()->getLocale(), 'channel-notifications');
    }
}
