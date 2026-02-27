<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Infrastructure\Services;

use Modules\ChannelNotifications\Domain\ValueObjects\NotificationMessage;

final class TemplateRenderer
{
    public function render(string $template, NotificationMessage $message, string $guildName): string
    {
        $placeholders = array_merge(
            $message->toPlaceholders(),
            ['{guild_name}' => $guildName],
        );

        return str_replace(
            array_keys($placeholders),
            array_values($placeholders),
            $template,
        );
    }
}
