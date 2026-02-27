<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Tests\Feature\Domain\Enums;

use Modules\ChannelNotifications\Domain\Enums\ContentType;
use Tests\Support\Modules\ModuleTestCase;

final class ContentTypePlaceholdersTest extends ModuleTestCase
{
    protected ?string $moduleName = 'channel-notifications';

    protected bool $autoEnableModule = true;

    public function test_event_placeholders_include_all_fields(): void
    {
        $placeholders = ContentType::Event->placeholders();

        $this->assertArrayHasKey('{title}', $placeholders);
        $this->assertArrayHasKey('{excerpt}', $placeholders);
        $this->assertArrayHasKey('{url}', $placeholders);
        $this->assertArrayHasKey('{guild_name}', $placeholders);
        $this->assertArrayHasKey('{image_url}', $placeholders);
        $this->assertArrayHasKey('{tags}', $placeholders);
        $this->assertArrayHasKey('{date}', $placeholders);
        $this->assertArrayHasKey('{end_date}', $placeholders);
        $this->assertArrayHasKey('{location}', $placeholders);
        $this->assertArrayHasKey('{price}', $placeholders);
    }

    public function test_article_placeholders_include_author_and_tags(): void
    {
        $placeholders = ContentType::Article->placeholders();

        $this->assertArrayHasKey('{title}', $placeholders);
        $this->assertArrayHasKey('{tags}', $placeholders);
        $this->assertArrayHasKey('{author}', $placeholders);
    }

    public function test_gallery_placeholders_include_photo_count_and_tags(): void
    {
        $placeholders = ContentType::Gallery->placeholders();

        $this->assertArrayHasKey('{title}', $placeholders);
        $this->assertArrayHasKey('{tags}', $placeholders);
        $this->assertArrayHasKey('{photo_count}', $placeholders);
    }
}
