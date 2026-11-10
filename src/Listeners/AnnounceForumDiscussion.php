<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Listeners;

use App\Domain\Forum\Events\ForumDiscussionStarted;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Modules\ChannelNotifications\Domain\Enums\ContentType;
use Modules\ChannelNotifications\Domain\ValueObjects\NotificationMessage;
use Modules\ChannelNotifications\Notifications\ContentPublishedNotification;
use Modules\ChannelNotifications\Notifications\NotificationTarget;

/**
 * Posts a discussion started in the forum, as the "forum_discussion" content type, on the
 * channels that take it. The forum module tells it through the core's event, once and only for
 * what it lets be announced: neither imports the other.
 */
final readonly class AnnounceForumDiscussion
{
    public function handle(ForumDiscussionStarted $event): void
    {
        $message = new NotificationMessage(
            title: $event->title,
            excerpt: Str::limit(trim(strip_tags($event->excerpt)), 200),
            imageUrl: null,
            contentUrl: $event->url,
            contentType: ContentType::ForumDiscussion,
            extraData: [
                'tags' => implode(', ', $event->tags),
            ],
        );

        Notification::send(new NotificationTarget, new ContentPublishedNotification($message));
    }
}
