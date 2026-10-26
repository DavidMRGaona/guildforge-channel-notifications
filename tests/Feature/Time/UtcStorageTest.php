<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Tests\Feature\Time;

use App\Infrastructure\Persistence\Eloquent\Models\EventModel;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;
use Modules\ChannelNotifications\Application\Services\ChannelConfigServiceInterface;
use Modules\ChannelNotifications\Domain\Enums\ContentType;
use Modules\ChannelNotifications\Infrastructure\Services\TemplateRenderer;
use Modules\ChannelNotifications\Notifications\ContentPublishedNotification;
use Modules\ChannelNotifications\Notifications\NotificationTarget;
use Tests\Support\Modules\ModuleTestCase;
use Tests\Support\Time\UsesUtcStorage;

/**
 * With UTC storage the event dates reach the observer as UTC instants, but the channel
 * message names the site's Madrid wall time.
 */
final class UtcStorageTest extends ModuleTestCase
{
    use UsesUtcStorage;

    protected ?string $moduleName = 'channel-notifications';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        $configService = $this->createStub(ChannelConfigServiceInterface::class);
        $configService->method('isChannelEnabled')->willReturn(true);
        $configService->method('getEnabledContentTypes')->willReturn([ContentType::Event]);
        $this->app->instance(ChannelConfigServiceInterface::class, $configService);

        Notification::fake();
    }

    public function test_the_channel_message_names_the_event_dates_in_madrid_time_with_utc_storage(): void
    {
        $this->useUtcStorage();

        $this->assertMessageNamesMadridTime();
    }

    public function test_the_channel_message_names_the_event_dates_in_madrid_time_with_madrid_storage(): void
    {
        $this->assertMessageNamesMadridTime();
    }

    private function assertMessageNamesMadridTime(): void
    {
        EventModel::factory()->create([
            'is_published' => true,
            'start_date' => CarbonImmutable::parse('2026-07-01T10:00:00Z'),
            'end_date' => CarbonImmutable::parse('2026-07-01T14:00:00Z'),
        ]);

        Notification::assertSentTo(
            new NotificationTarget,
            function (ContentPublishedNotification $notification): bool {
                $message = $notification->getMessage();

                $this->assertSame('01/07/2026 12:00', $message->extraData['date']);
                $this->assertSame('01/07/2026 16:00', $message->extraData['end_date']);
                $this->assertSame(
                    'Empieza el 01/07/2026 12:00 y termina el 01/07/2026 16:00',
                    (new TemplateRenderer)->render('Empieza el {date} y termina el {end_date}', $message, 'Asociación'),
                );

                return true;
            },
        );
    }
}
