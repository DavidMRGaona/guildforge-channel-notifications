<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Tests\Feature\Listeners;

use App\Application\Services\SettingsServiceInterface;
use App\Domain\Forum\Events\ForumDiscussionStarted;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Modules\ChannelNotifications\Application\Services\ChannelConfigServiceInterface;
use Modules\ChannelNotifications\Domain\Enums\ContentType;
use Modules\ChannelNotifications\Domain\Enums\NotificationChannel;
use Modules\ChannelNotifications\Notifications\ContentPublishedNotification;
use Modules\ChannelNotifications\Notifications\NotificationTarget;
use Tests\Support\Modules\ModuleTestCase;

/**
 * A discussion started in the forum is posted as the "forum_discussion" content type, on the
 * channels that take it.
 */
final class AnnounceForumDiscussionTest extends ModuleTestCase
{
    protected ?string $moduleName = 'channel-notifications';

    protected bool $autoEnableModule = true;

    public function test_it_sends_the_discussion_announcement(): void
    {
        $this->takeForumDiscussionsOnEveryChannel();
        Notification::fake();

        event(new ForumDiscussionStarted('31', 'Busco grupo de rol', 'https://forum.example.test/d/31', '<p>Faltan jugadores.</p>', ['general', 'rol']));

        Notification::assertSentTo(new NotificationTarget, ContentPublishedNotification::class, function (ContentPublishedNotification $notification): bool {
            $message = $notification->getMessage();

            return $message->contentType === ContentType::ForumDiscussion
                && $message->title === 'Busco grupo de rol'
                && $message->excerpt === 'Faltan jugadores.'
                && $message->imageUrl === null
                && $message->contentUrl === 'https://forum.example.test/d/31'
                && $message->extraData['tags'] === 'general, rol';
        });
    }

    public function test_it_posts_only_on_the_channels_that_take_forum_discussions(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);
        $settings = $this->app->make(SettingsServiceInterface::class);
        $settings->set('notifications_telegram_enabled', '1');
        $settings->setEncrypted('notifications_telegram_bot_token', 'test-token');
        $settings->set('notifications_telegram_chat_id', '-100456');
        $settings->set('notifications_telegram_content_types', json_encode([ContentType::ForumDiscussion->value], JSON_THROW_ON_ERROR));
        $settings->set('notifications_discord_enabled', '1');
        $settings->setEncrypted('notifications_discord_webhook_url', 'https://discord.example.com/webhook');
        $settings->set('notifications_discord_content_types', json_encode([ContentType::Event->value], JSON_THROW_ON_ERROR));

        event(new ForumDiscussionStarted('31', 'Rol & tablero', 'https://forum.example.test/d/31'));

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'api.telegram.org/bottest-token/sendMessage')
            && str_contains((string) $request['text'], '<b>Rol &amp; tablero</b>')
            && str_contains((string) $request['text'], 'href="https://forum.example.test/d/31"'));
    }

    public function test_nothing_is_sent_when_no_channel_takes_forum_discussions(): void
    {
        Http::fake();

        event(new ForumDiscussionStarted('31', 'Busco grupo de rol', 'https://forum.example.test/d/31'));

        Http::assertNothingSent();
    }

    public function test_the_default_template_renders_on_every_channel(): void
    {
        foreach (NotificationChannel::cases() as $channel) {
            $key = "channel-notifications::messages.defaults.{$channel->value}.forum_discussion";
            $template = (string) __($key);

            $this->assertNotSame($key, $template);
            $this->assertStringNotContainsString('{image_url}', $template);
            $this->assertStringContainsString('{tags}', $template);
        }
    }

    /**
     * A notification with no channel to go to is not recorded by the fake.
     */
    private function takeForumDiscussionsOnEveryChannel(): void
    {
        $config = $this->createStub(ChannelConfigServiceInterface::class);
        $config->method('isChannelEnabled')->willReturn(true);
        $config->method('getEnabledContentTypes')->willReturn([ContentType::ForumDiscussion]);
        $this->app->instance(ChannelConfigServiceInterface::class, $config);
    }
}
