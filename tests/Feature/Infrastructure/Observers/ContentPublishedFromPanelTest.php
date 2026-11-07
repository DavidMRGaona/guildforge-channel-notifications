<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Tests\Feature\Infrastructure\Observers;

use App\Filament\Resources\EventResource\Pages\CreateEvent;
use App\Filament\Resources\GalleryResource\Pages\CreateGallery;
use App\Infrastructure\Persistence\Eloquent\Models\TagModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\ChannelNotifications\Application\Services\ChannelConfigServiceInterface;
use Modules\ChannelNotifications\Domain\Enums\ContentType;
use Modules\ChannelNotifications\Notifications\ContentPublishedNotification;
use Modules\ChannelNotifications\Notifications\NotificationTarget;
use Tests\Support\Modules\ModuleTestCase;

/**
 * The panel saves tags and photos after the record, so the announcement of content created
 * already published has to be prepared once they are saved.
 */
final class ContentPublishedFromPanelTest extends ModuleTestCase
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
        Storage::fake('images');
        $this->actingAsPanelAdmin();
    }

    public function test_an_event_created_published_from_the_panel_announces_its_tags(): void
    {
        $category = TagModel::factory()->forEvents()->create(['name' => 'Torneo']);

        Livewire::test(CreateEvent::class)
            ->fillForm([
                'title' => 'Jornada de ejemplo',
                'slug' => 'jornada-de-ejemplo',
                'description' => '<p>Una jornada.</p>',
                'start_date' => '2026-12-05 10:00:00',
                'end_date' => '2026-12-05 20:00:00',
                'category_id' => $category->id,
                'is_published' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->finishRequest();

        Notification::assertSentToTimes(new NotificationTarget, ContentPublishedNotification::class, 1);
        Notification::assertSentTo(
            new NotificationTarget,
            fn (ContentPublishedNotification $notification): bool => $notification->getMessage()->extraData['tags'] === 'Torneo',
        );
    }

    public function test_a_gallery_created_published_from_the_panel_announces_its_tags_and_photos(): void
    {
        $category = TagModel::factory()->forGalleries()->create(['name' => 'Pintura']);

        Livewire::test(CreateGallery::class)
            ->fillForm([
                'title' => 'Galería de ejemplo',
                'slug' => 'galeria-de-ejemplo',
                'category_id' => $category->id,
                'is_published' => true,
                'photos' => [
                    ['image_public_id' => [UploadedFile::fake()->image('uno.jpg')], 'caption' => 'Uno'],
                    ['image_public_id' => [UploadedFile::fake()->image('dos.jpg')], 'caption' => 'Dos'],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->finishRequest();

        Notification::assertSentToTimes(new NotificationTarget, ContentPublishedNotification::class, 1);
        Notification::assertSentTo(
            new NotificationTarget,
            function (ContentPublishedNotification $notification): bool {
                $extra = $notification->getMessage()->extraData;

                return $extra['tags'] === 'Pintura' && $extra['photo_count'] === '2';
            },
        );
    }

    /** What the kernel does once the response has been sent. */
    private function finishRequest(): void
    {
        $this->app->make(DeferredCallbackCollection::class)->invoke();
    }
}
