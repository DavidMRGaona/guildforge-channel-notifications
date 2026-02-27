<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Application\Services;

use Modules\ChannelNotifications\Domain\Enums\ContentType;
use Modules\ChannelNotifications\Domain\Enums\NotificationChannel;

interface ChannelConfigServiceInterface
{
    public function isChannelEnabled(NotificationChannel $channel): bool;

    /**
     * @return array<string, string>
     */
    public function getChannelCredentials(NotificationChannel $channel): array;

    /**
     * @return array<ContentType>
     */
    public function getEnabledContentTypes(NotificationChannel $channel): array;

    public function getTemplate(NotificationChannel $channel, ContentType $contentType): string;

    public function getDefaultTemplate(NotificationChannel $channel, ContentType $contentType): string;
}
