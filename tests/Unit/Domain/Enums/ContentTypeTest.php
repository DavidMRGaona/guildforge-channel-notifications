<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Tests\Unit\Domain\Enums;

use App\Infrastructure\Persistence\Eloquent\Models\ArticleModel;
use App\Infrastructure\Persistence\Eloquent\Models\EventModel;
use App\Infrastructure\Persistence\Eloquent\Models\GalleryModel;
use InvalidArgumentException;
use Modules\ChannelNotifications\Domain\Enums\ContentType;
use PHPUnit\Framework\TestCase;
use stdClass;

final class ContentTypeTest extends TestCase
{
    public function test_it_returns_correct_model_class(): void
    {
        $this->assertSame(EventModel::class, ContentType::Event->modelClass());
        $this->assertSame(ArticleModel::class, ContentType::Article->modelClass());
        $this->assertSame(GalleryModel::class, ContentType::Gallery->modelClass());
    }

    public function test_from_model_class_resolves_correctly(): void
    {
        $this->assertSame(ContentType::Event, ContentType::fromModelClass(EventModel::class));
        $this->assertSame(ContentType::Article, ContentType::fromModelClass(ArticleModel::class));
        $this->assertSame(ContentType::Gallery, ContentType::fromModelClass(GalleryModel::class));
    }

    public function test_from_model_class_throws_for_unknown_class(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported model class: stdClass');

        ContentType::fromModelClass(stdClass::class);
    }
}
