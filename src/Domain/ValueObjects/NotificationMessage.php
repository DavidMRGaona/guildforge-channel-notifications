<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Domain\ValueObjects;

use Modules\ChannelNotifications\Domain\Enums\ContentType;

final readonly class NotificationMessage
{
    /**
     * @param  array<string, string>  $extraData
     */
    public function __construct(
        public string $title,
        public string $excerpt,
        public ?string $imageUrl,
        public string $contentUrl,
        public ContentType $contentType,
        public array $extraData = [],
    ) {}

    /**
     * @return array<string, string>
     */
    public function toPlaceholders(): array
    {
        return array_merge([
            '{title}' => $this->title,
            '{excerpt}' => $this->excerpt,
            '{url}' => $this->contentUrl,
            '{image_url}' => $this->imageUrl ?? '',
        ], array_map(
            static fn (string $value): string => $value,
            array_combine(
                array_map(static fn (string $key): string => '{'.$key.'}', array_keys($this->extraData)),
                array_values($this->extraData),
            ),
        ));
    }
}
