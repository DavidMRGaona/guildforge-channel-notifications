<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Tests\Feature\Listeners;

use App\Application\Services\SettingsServiceInterface;
use App\Domain\Surveys\Events\GeneralSurveyOpened;
use DateTimeImmutable;
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
 * A general survey that opens with «Anunciar en los canales» is posted as the "survey" content
 * type, on the channels that take it.
 */
final class AnnounceGeneralSurveyTest extends ModuleTestCase
{
    protected ?string $moduleName = 'channel-notifications';

    protected bool $autoEnableModule = true;

    private const string SURVEY_ID = '0b5c3a1e-8f7d-4c2b-9a6e-1d2f3a4b5c6d';

    public function test_it_sends_the_survey_announcement(): void
    {
        $this->takeSurveysOnEveryChannel();
        Notification::fake();

        event(new GeneralSurveyOpened(
            surveyId: self::SURVEY_ID,
            title: 'Horario del local',
            description: '<p>Queremos saber qué días os viene mejor.</p>',
            isPoll: false,
            closesAt: new DateTimeImmutable('2030-01-10T19:00:00+00:00'),
            url: 'https://example.com/encuestas/'.self::SURVEY_ID,
        ));

        Notification::assertSentTo(new NotificationTarget, ContentPublishedNotification::class, function (ContentPublishedNotification $notification): bool {
            $message = $notification->getMessage();

            return $message->contentType === ContentType::Survey
                && $message->title === 'Horario del local'
                && $message->excerpt === 'Queremos saber qué días os viene mejor.'
                && $message->imageUrl === null
                && $message->contentUrl === 'https://example.com/encuestas/'.self::SURVEY_ID
                && $message->extraData['close_date'] === '10/01/2030 20:00'
                && $message->extraData['survey_kind'] === 'Nueva encuesta';
        });
    }

    public function test_a_survey_without_closing_date_says_so(): void
    {
        $this->takeSurveysOnEveryChannel();
        Notification::fake();

        event(new GeneralSurveyOpened(self::SURVEY_ID, 'Horario del local', null, false, null, 'https://example.com/encuestas/x'));

        Notification::assertSentTo(new NotificationTarget, ContentPublishedNotification::class, fn (ContentPublishedNotification $notification): bool => $notification->getMessage()->extraData['close_date'] === 'Sin fecha de cierre'
            && $notification->getMessage()->excerpt === '');
    }

    public function test_a_poll_is_announced_as_a_poll(): void
    {
        $this->takeSurveysOnEveryChannel();
        Notification::fake();

        event(new GeneralSurveyOpened(self::SURVEY_ID, '¿Qué día hacemos la asamblea?', null, true, null, 'https://example.com/encuestas/x'));

        Notification::assertSentTo(new NotificationTarget, ContentPublishedNotification::class, fn (ContentPublishedNotification $notification): bool => $notification->getMessage()->extraData['survey_kind'] === 'Nuevo sondeo');
    }

    public function test_it_posts_only_on_the_channels_that_take_surveys(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);
        $settings = $this->app->make(SettingsServiceInterface::class);
        $settings->set('notifications_telegram_enabled', '1');
        $settings->setEncrypted('notifications_telegram_bot_token', 'test-token');
        $settings->set('notifications_telegram_chat_id', '-100456');
        $settings->set('notifications_telegram_content_types', json_encode([ContentType::Survey->value], JSON_THROW_ON_ERROR));
        $settings->set('notifications_discord_enabled', '1');
        $settings->setEncrypted('notifications_discord_webhook_url', 'https://discord.example.com/webhook');
        $settings->set('notifications_discord_content_types', json_encode([ContentType::Event->value], JSON_THROW_ON_ERROR));

        event(new GeneralSurveyOpened(self::SURVEY_ID, 'Rol & tablero', null, false, null, 'https://example.com/encuestas/x'));

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'api.telegram.org/bottest-token/sendMessage')
            && str_contains((string) $request['text'], '<b>Rol &amp; tablero</b>')
            && str_contains((string) $request['text'], 'href="https://example.com/encuestas/x"'));
    }

    public function test_nothing_is_sent_when_no_channel_takes_surveys(): void
    {
        Http::fake();

        event(new GeneralSurveyOpened(self::SURVEY_ID, 'Horario del local', null, false, null, 'https://example.com/encuestas/x'));

        Http::assertNothingSent();
    }

    public function test_the_default_survey_template_renders_on_every_channel(): void
    {
        foreach (NotificationChannel::cases() as $channel) {
            $template = (string) __("channel-notifications::messages.defaults.{$channel->value}.survey");

            $this->assertNotSame("channel-notifications::messages.defaults.{$channel->value}.survey", $template);
            $this->assertStringNotContainsString('{image_url}', $template);
            $this->assertStringNotContainsString('{tags}', $template);
            $this->assertStringContainsString('{close_date}', $template);
        }
    }

    /**
     * A notification with no channel to go to is not recorded by the fake.
     */
    private function takeSurveysOnEveryChannel(): void
    {
        $config = $this->createStub(ChannelConfigServiceInterface::class);
        $config->method('isChannelEnabled')->willReturn(true);
        $config->method('getEnabledContentTypes')->willReturn([ContentType::Survey]);
        $this->app->instance(ChannelConfigServiceInterface::class, $config);
    }
}
