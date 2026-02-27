<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Tests\Unit\Infrastructure\Services;

use Modules\ChannelNotifications\Domain\Enums\ContentType;
use Modules\ChannelNotifications\Domain\ValueObjects\NotificationMessage;
use Modules\ChannelNotifications\Infrastructure\Services\TemplateRenderer;
use PHPUnit\Framework\TestCase;

final class TemplateRendererTest extends TestCase
{
    private TemplateRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->renderer = new TemplateRenderer;
    }

    public function test_it_replaces_basic_placeholders(): void
    {
        $message = new NotificationMessage(
            title: 'Weekly Game Night',
            excerpt: 'Join us for board games',
            imageUrl: 'https://example.com/image.jpg',
            contentUrl: 'https://example.com/events/1',
            contentType: ContentType::Event,
        );

        $template = 'New event at {guild_name}: {title} - {excerpt}. More info: {url}';

        $result = $this->renderer->render($template, $message, 'GuildForge');

        $this->assertSame(
            'New event at GuildForge: Weekly Game Night - Join us for board games. More info: https://example.com/events/1',
            $result,
        );
    }

    public function test_it_replaces_extra_data_placeholders(): void
    {
        $message = new NotificationMessage(
            title: 'Tournament',
            excerpt: 'Warhammer 40K tournament',
            imageUrl: 'https://example.com/image.jpg',
            contentUrl: 'https://example.com/events/2',
            contentType: ContentType::Event,
            extraData: ['date' => '2026-03-15', 'location' => 'Madrid'],
        );

        $template = '{title} on {date} at {location}. Details: {url}';

        $result = $this->renderer->render($template, $message, 'GuildForge');

        $this->assertSame(
            'Tournament on 2026-03-15 at Madrid. Details: https://example.com/events/2',
            $result,
        );
    }

    public function test_it_handles_empty_image_url(): void
    {
        $message = new NotificationMessage(
            title: 'Article Title',
            excerpt: 'Article excerpt',
            imageUrl: null,
            contentUrl: 'https://example.com/articles/1',
            contentType: ContentType::Article,
        );

        $template = '{title} - Image: {image_url}';

        $result = $this->renderer->render($template, $message, 'GuildForge');

        $this->assertSame('Article Title - Image: ', $result);
    }

    public function test_it_preserves_text_without_placeholders(): void
    {
        $message = new NotificationMessage(
            title: 'Test',
            excerpt: 'Excerpt',
            imageUrl: null,
            contentUrl: 'https://example.com',
            contentType: ContentType::Article,
        );

        $template = 'This is plain text with no placeholders at all.';

        $result = $this->renderer->render($template, $message, 'GuildForge');

        $this->assertSame('This is plain text with no placeholders at all.', $result);
    }

    public function test_it_handles_template_with_no_matching_placeholders(): void
    {
        $message = new NotificationMessage(
            title: 'Test',
            excerpt: 'Excerpt',
            imageUrl: null,
            contentUrl: 'https://example.com',
            contentType: ContentType::Article,
        );

        $template = 'Hello {unknown_placeholder}, welcome to {another_unknown}!';

        $result = $this->renderer->render($template, $message, 'GuildForge');

        $this->assertSame('Hello {unknown_placeholder}, welcome to {another_unknown}!', $result);
    }

    public function test_it_replaces_guild_name_placeholder(): void
    {
        $message = new NotificationMessage(
            title: 'Welcome',
            excerpt: 'Welcome message',
            imageUrl: null,
            contentUrl: 'https://example.com',
            contentType: ContentType::Article,
        );

        $template = 'Welcome to {guild_name}!';

        $result = $this->renderer->render($template, $message, 'My Awesome Guild');

        $this->assertSame('Welcome to My Awesome Guild!', $result);
    }

    public function test_it_replaces_all_event_placeholders(): void
    {
        $message = new NotificationMessage(
            title: 'Tournament',
            excerpt: 'Warhammer 40K tournament',
            imageUrl: 'https://example.com/image.jpg',
            contentUrl: 'https://example.com/events/2',
            contentType: ContentType::Event,
            extraData: [
                'date' => '15/03/2026 10:00',
                'end_date' => '15/03/2026 20:00',
                'location' => 'Madrid',
                'price' => 'Socios: 10.00€ · No socios: 15.00€',
                'tags' => 'Warhammer, Torneo',
            ],
        );

        $template = '{title} | {date} → {end_date} | {location} | {price} | {tags}';

        $result = $this->renderer->render($template, $message, 'GuildForge');

        $this->assertSame(
            'Tournament | 15/03/2026 10:00 → 15/03/2026 20:00 | Madrid | Socios: 10.00€ · No socios: 15.00€ | Warhammer, Torneo',
            $result,
        );
    }

    public function test_it_replaces_article_placeholders_with_author(): void
    {
        $message = new NotificationMessage(
            title: 'New Article',
            excerpt: 'Article content',
            imageUrl: null,
            contentUrl: 'https://example.com/articles/1',
            contentType: ContentType::Article,
            extraData: [
                'author' => 'John Doe',
                'tags' => 'RPG, D&D',
            ],
        );

        $template = '{title} by {author} | {tags}';

        $result = $this->renderer->render($template, $message, 'GuildForge');

        $this->assertSame('New Article by John Doe | RPG, D&D', $result);
    }

    public function test_it_replaces_gallery_placeholders_with_photo_count(): void
    {
        $message = new NotificationMessage(
            title: 'Event Photos',
            excerpt: 'Photos from the event',
            imageUrl: null,
            contentUrl: 'https://example.com/gallery/1',
            contentType: ContentType::Gallery,
            extraData: [
                'photo_count' => '24',
                'tags' => 'Fotos, Evento',
            ],
        );

        $template = '{title} | {photo_count} fotos | {tags}';

        $result = $this->renderer->render($template, $message, 'GuildForge');

        $this->assertSame('Event Photos | 24 fotos | Fotos, Evento', $result);
    }
}
