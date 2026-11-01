<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Infrastructure\Services;

use Modules\ChannelNotifications\Domain\ValueObjects\NotificationMessage;

final class TemplateRenderer
{
    /**
     * @param  bool  $escapeHtml  escape the values (not the template) for a channel that parses HTML,
     *                            so a title with "&" or "<" neither breaks the message nor adds markup
     */
    public function render(string $template, NotificationMessage $message, string $guildName, bool $escapeHtml = false): string
    {
        $placeholders = array_merge(
            $message->toPlaceholders(),
            ['{guild_name}' => $guildName],
        );

        if ($escapeHtml) {
            $placeholders = array_map(
                static fn (string $value): string => htmlspecialchars($value, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8'),
                $placeholders,
            );
        }

        return str_replace(
            array_keys($placeholders),
            array_values($placeholders),
            $template,
        );
    }
}
