<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Domain\Enums;

use Modules\ChannelNotifications\Infrastructure\Channels\DiscordChannel;
use Modules\ChannelNotifications\Infrastructure\Channels\SlackChannel;
use Modules\ChannelNotifications\Infrastructure\Channels\TelegramChannel;
use Modules\ChannelNotifications\Infrastructure\Channels\WhatsAppChannel;

enum NotificationChannel: string
{
    case Telegram = 'telegram';
    case Discord = 'discord';
    case Slack = 'slack';
    case WhatsApp = 'whatsapp';

    public function label(): string
    {
        return match ($this) {
            self::Telegram => 'Telegram',
            self::Discord => 'Discord',
            self::Slack => 'Slack',
            self::WhatsApp => 'WhatsApp',
        };
    }

    public function settingsPrefix(): string
    {
        return 'notifications_'.$this->value;
    }

    /**
     * @return class-string
     */
    public function channelClass(): string
    {
        return match ($this) {
            self::Telegram => TelegramChannel::class,
            self::Discord => DiscordChannel::class,
            self::Slack => SlackChannel::class,
            self::WhatsApp => WhatsAppChannel::class,
        };
    }
}
