<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Infrastructure\Observers;

use App\Infrastructure\Persistence\Eloquent\Models\EventModel;
use App\Infrastructure\Persistence\Eloquent\Models\GalleryModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\ChannelNotifications\Domain\Enums\ContentType;
use Modules\ChannelNotifications\Domain\ValueObjects\NotificationMessage;
use Modules\ChannelNotifications\Notifications\ContentPublishedNotification;
use Modules\ChannelNotifications\Notifications\NotificationTarget;

final class ContentPublishedObserver
{
    public function created(Model $model): void
    {
        if ($model->getAttribute('is_published') === true) {
            $this->dispatchNotification($model);
        }
    }

    public function updated(Model $model): void
    {
        if ($model->wasChanged('is_published') && $model->getAttribute('is_published') === true) {
            $this->dispatchNotification($model);
        }
    }

    private function dispatchNotification(Model $model): void
    {
        $contentType = ContentType::fromModelClass($model::class);
        $message = $this->buildMessage($model, $contentType);

        Notification::send(new NotificationTarget, new ContentPublishedNotification($message));
    }

    private function buildMessage(Model $model, ContentType $contentType): NotificationMessage
    {
        return new NotificationMessage(
            title: (string) $model->getAttribute('title'),
            excerpt: $this->getExcerpt($model, $contentType),
            imageUrl: $this->getImageUrl($model, $contentType),
            contentUrl: $this->getContentUrl($model, $contentType),
            contentType: $contentType,
            extraData: $this->getExtraData($model, $contentType),
        );
    }

    private function getExcerpt(Model $model, ContentType $contentType): string
    {
        return match ($contentType) {
            ContentType::Event => Str::limit(strip_tags((string) $model->getAttribute('description')), 200),
            ContentType::Article => Str::limit(strip_tags((string) ($model->getAttribute('excerpt') ?: $model->getAttribute('content'))), 200),
            ContentType::Gallery => Str::limit(strip_tags((string) $model->getAttribute('description')), 200),
        };
    }

    private function getImageUrl(Model $model, ContentType $contentType): ?string
    {
        $publicId = match ($contentType) {
            ContentType::Event => $model->getAttribute('image_public_id'),
            ContentType::Article => $model->getAttribute('featured_image_public_id'),
            ContentType::Gallery => $model instanceof GalleryModel ? $model->cover_image_public_id : null,
        };

        if ($publicId === null || $publicId === '') {
            return null;
        }

        return Storage::disk('images')->url($publicId);
    }

    private function getContentUrl(Model $model, ContentType $contentType): string
    {
        $slug = $model->getAttribute('slug') ?? $model->getAttribute('id');

        return match ($contentType) {
            ContentType::Event => url("/eventos/{$slug}"),
            ContentType::Article => url("/articulos/{$slug}"),
            ContentType::Gallery => url("/galeria/{$slug}"),
        };
    }

    /**
     * @return array<string, string>
     */
    private function getExtraData(Model $model, ContentType $contentType): array
    {
        if ($contentType !== ContentType::Event) {
            return [];
        }

        /** @var EventModel $model */
        return [
            'date' => $model->start_date->format('d/m/Y H:i'),
            'location' => (string) ($model->location ?? ''),
        ];
    }
}
