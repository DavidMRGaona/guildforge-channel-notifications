<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Listeners;

use App\Application\Services\TimezoneServiceInterface;
use App\Domain\Surveys\Events\GeneralSurveyOpened;
use DateTimeImmutable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Modules\ChannelNotifications\Domain\Enums\ContentType;
use Modules\ChannelNotifications\Domain\ValueObjects\NotificationMessage;
use Modules\ChannelNotifications\Notifications\ContentPublishedNotification;
use Modules\ChannelNotifications\Notifications\NotificationTarget;

/**
 * Posts a general survey that has just opened, as the "survey" content type, on the channels
 * that take it. The surveys module tells it through the core's event: neither imports the other.
 */
final readonly class AnnounceGeneralSurvey
{
    public function __construct(private TimezoneServiceInterface $timezone) {}

    public function handle(GeneralSurveyOpened $event): void
    {
        $message = new NotificationMessage(
            title: $event->title,
            excerpt: Str::limit(trim(strip_tags($event->description ?? '')), 200),
            imageUrl: null,
            contentUrl: $event->url,
            contentType: ContentType::Survey,
            extraData: [
                'close_date' => $event->closesAt instanceof DateTimeImmutable
                    ? $this->timezone->format($event->closesAt)
                    : __('channel-notifications::messages.surveys.no_close_date'),
                'survey_kind' => __('channel-notifications::messages.surveys.kind.'.($event->isPoll ? 'poll' : 'survey')),
            ],
        );

        Notification::send(new NotificationTarget, new ContentPublishedNotification($message));
    }
}
