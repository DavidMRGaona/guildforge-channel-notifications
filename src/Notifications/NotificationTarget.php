<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Notifications;

use Illuminate\Notifications\Notifiable;

final class NotificationTarget
{
    use Notifiable;

    public function getKey(): string
    {
        return 'channel-notification-target';
    }
}
