<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Domain\Enums;

use App\Infrastructure\Persistence\Eloquent\Models\ArticleModel;
use App\Infrastructure\Persistence\Eloquent\Models\EventModel;
use App\Infrastructure\Persistence\Eloquent\Models\GalleryModel;

enum ContentType: string
{
    case Event = 'event';
    case Article = 'article';
    case Gallery = 'gallery';
    case Survey = 'survey';

    public function label(): string
    {
        return match ($this) {
            self::Event => __('channel-notifications::messages.content_types.event'),
            self::Article => __('channel-notifications::messages.content_types.article'),
            self::Gallery => __('channel-notifications::messages.content_types.gallery'),
            self::Survey => __('channel-notifications::messages.content_types.survey'),
        };
    }

    /**
     * The core model whose publication is announced; null for content announced from an event
     * (a survey, told by the core's GeneralSurveyOpened).
     *
     * @return class-string|null
     */
    public function modelClass(): ?string
    {
        return match ($this) {
            self::Event => EventModel::class,
            self::Article => ArticleModel::class,
            self::Gallery => GalleryModel::class,
            self::Survey => null,
        };
    }

    /**
     * @return array<string, string>
     */
    public function placeholders(): array
    {
        $common = [
            '{title}' => __('channel-notifications::messages.placeholders.title'),
            '{excerpt}' => __('channel-notifications::messages.placeholders.excerpt'),
            '{url}' => __('channel-notifications::messages.placeholders.url'),
            '{guild_name}' => __('channel-notifications::messages.placeholders.guild_name'),
            '{image_url}' => __('channel-notifications::messages.placeholders.image_url'),
            '{tags}' => __('channel-notifications::messages.placeholders.tags'),
        ];

        return match ($this) {
            self::Event => array_merge($common, [
                '{date}' => __('channel-notifications::messages.placeholders.date'),
                '{end_date}' => __('channel-notifications::messages.placeholders.end_date'),
                '{location}' => __('channel-notifications::messages.placeholders.location'),
                '{price}' => __('channel-notifications::messages.placeholders.price'),
            ]),
            self::Article => array_merge($common, [
                '{author}' => __('channel-notifications::messages.placeholders.author'),
            ]),
            self::Gallery => array_merge($common, [
                '{photo_count}' => __('channel-notifications::messages.placeholders.photo_count'),
            ]),
            // A survey has no image or tags
            self::Survey => [
                '{title}' => $common['{title}'],
                '{excerpt}' => $common['{excerpt}'],
                '{url}' => $common['{url}'],
                '{guild_name}' => $common['{guild_name}'],
                '{close_date}' => __('channel-notifications::messages.placeholders.close_date'),
                '{survey_kind}' => __('channel-notifications::messages.placeholders.survey_kind'),
            ],
        };
    }

    /**
     * @param  class-string  $modelClass
     */
    public static function fromModelClass(string $modelClass): self
    {
        return match ($modelClass) {
            EventModel::class => self::Event,
            ArticleModel::class => self::Article,
            GalleryModel::class => self::Gallery,
            default => throw new \InvalidArgumentException("Unsupported model class: {$modelClass}"),
        };
    }
}
