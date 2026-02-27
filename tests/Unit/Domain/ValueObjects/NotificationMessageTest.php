<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Tests\Unit\Domain\ValueObjects;

use Modules\ChannelNotifications\Domain\Enums\ContentType;
use Modules\ChannelNotifications\Domain\ValueObjects\NotificationMessage;
use PHPUnit\Framework\TestCase;

final class NotificationMessageTest extends TestCase
{
    public function test_it_constructs_with_all_properties(): void
    {
        $message = new NotificationMessage(
            title: 'Test Event',
            excerpt: 'A great event',
            imageUrl: 'https://example.com/image.jpg',
            contentUrl: 'https://example.com/events/1',
            contentType: ContentType::Event,
            extraData: ['date' => '2026-03-01', 'location' => 'Madrid'],
        );

        $this->assertSame('Test Event', $message->title);
        $this->assertSame('A great event', $message->excerpt);
        $this->assertSame('https://example.com/image.jpg', $message->imageUrl);
        $this->assertSame('https://example.com/events/1', $message->contentUrl);
        $this->assertSame(ContentType::Event, $message->contentType);
        $this->assertSame(['date' => '2026-03-01', 'location' => 'Madrid'], $message->extraData);
    }

    public function test_it_constructs_with_null_image_url(): void
    {
        $message = new NotificationMessage(
            title: 'Test Article',
            excerpt: 'An interesting article',
            imageUrl: null,
            contentUrl: 'https://example.com/articles/1',
            contentType: ContentType::Article,
        );

        $this->assertNull($message->imageUrl);
    }

    public function test_to_placeholders_returns_correct_keys(): void
    {
        $message = new NotificationMessage(
            title: 'My Event',
            excerpt: 'Event description',
            imageUrl: 'https://example.com/image.jpg',
            contentUrl: 'https://example.com/events/1',
            contentType: ContentType::Event,
        );

        $placeholders = $message->toPlaceholders();

        $this->assertArrayHasKey('{title}', $placeholders);
        $this->assertArrayHasKey('{excerpt}', $placeholders);
        $this->assertArrayHasKey('{url}', $placeholders);
        $this->assertArrayHasKey('{image_url}', $placeholders);
        $this->assertSame('My Event', $placeholders['{title}']);
        $this->assertSame('Event description', $placeholders['{excerpt}']);
        $this->assertSame('https://example.com/events/1', $placeholders['{url}']);
        $this->assertSame('https://example.com/image.jpg', $placeholders['{image_url}']);
    }

    public function test_to_placeholders_includes_extra_data(): void
    {
        $message = new NotificationMessage(
            title: 'Event Title',
            excerpt: 'Excerpt text',
            imageUrl: 'https://example.com/image.jpg',
            contentUrl: 'https://example.com/events/1',
            contentType: ContentType::Event,
            extraData: ['date' => '2026-03-01', 'location' => 'Madrid'],
        );

        $placeholders = $message->toPlaceholders();

        $this->assertArrayHasKey('{date}', $placeholders);
        $this->assertArrayHasKey('{location}', $placeholders);
        $this->assertSame('2026-03-01', $placeholders['{date}']);
        $this->assertSame('Madrid', $placeholders['{location}']);
    }

    public function test_to_placeholders_handles_empty_extra_data(): void
    {
        $message = new NotificationMessage(
            title: 'Gallery Title',
            excerpt: 'Gallery description',
            imageUrl: 'https://example.com/gallery.jpg',
            contentUrl: 'https://example.com/galleries/1',
            contentType: ContentType::Gallery,
            extraData: [],
        );

        $placeholders = $message->toPlaceholders();

        $this->assertCount(4, $placeholders);
        $this->assertArrayHasKey('{title}', $placeholders);
        $this->assertArrayHasKey('{excerpt}', $placeholders);
        $this->assertArrayHasKey('{url}', $placeholders);
        $this->assertArrayHasKey('{image_url}', $placeholders);
    }

    public function test_to_placeholders_returns_empty_string_for_null_image_url(): void
    {
        $message = new NotificationMessage(
            title: 'Article Title',
            excerpt: 'Article excerpt',
            imageUrl: null,
            contentUrl: 'https://example.com/articles/1',
            contentType: ContentType::Article,
        );

        $placeholders = $message->toPlaceholders();

        $this->assertSame('', $placeholders['{image_url}']);
    }
}
