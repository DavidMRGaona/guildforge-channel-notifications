<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Tests\Unit\Domain\Enums;

use Modules\ChannelNotifications\Domain\Enums\NotificationChannel;
use Modules\ChannelNotifications\Infrastructure\Channels\DiscordChannel;
use Modules\ChannelNotifications\Infrastructure\Channels\SlackChannel;
use Modules\ChannelNotifications\Infrastructure\Channels\TelegramChannel;
use Modules\ChannelNotifications\Infrastructure\Channels\WhatsAppChannel;
use PHPUnit\Framework\TestCase;

final class NotificationChannelTest extends TestCase
{
    public function test_it_has_four_channels(): void
    {
        $cases = NotificationChannel::cases();

        $this->assertCount(4, $cases);
        $this->assertSame('telegram', NotificationChannel::Telegram->value);
        $this->assertSame('discord', NotificationChannel::Discord->value);
        $this->assertSame('slack', NotificationChannel::Slack->value);
        $this->assertSame('whatsapp', NotificationChannel::WhatsApp->value);
    }

    public function test_it_returns_correct_labels(): void
    {
        $this->assertSame('Telegram', NotificationChannel::Telegram->label());
        $this->assertSame('Discord', NotificationChannel::Discord->label());
        $this->assertSame('Slack', NotificationChannel::Slack->label());
        $this->assertSame('WhatsApp', NotificationChannel::WhatsApp->label());
    }

    public function test_it_returns_correct_settings_prefix(): void
    {
        $this->assertSame('notifications_telegram', NotificationChannel::Telegram->settingsPrefix());
        $this->assertSame('notifications_discord', NotificationChannel::Discord->settingsPrefix());
        $this->assertSame('notifications_slack', NotificationChannel::Slack->settingsPrefix());
        $this->assertSame('notifications_whatsapp', NotificationChannel::WhatsApp->settingsPrefix());
    }

    public function test_it_returns_correct_channel_class(): void
    {
        $this->assertSame(TelegramChannel::class, NotificationChannel::Telegram->channelClass());
        $this->assertSame(DiscordChannel::class, NotificationChannel::Discord->channelClass());
        $this->assertSame(SlackChannel::class, NotificationChannel::Slack->channelClass());
        $this->assertSame(WhatsAppChannel::class, NotificationChannel::WhatsApp->channelClass());
    }
}
