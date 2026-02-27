<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\ChannelNotifications\Application\Services\ChannelConfigServiceInterface;
use Modules\ChannelNotifications\Domain\Enums\NotificationChannel;
use Modules\ChannelNotifications\Domain\ValueObjects\NotificationMessage;
use Modules\ChannelNotifications\Infrastructure\Services\TemplateRenderer;

final class ContentPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly NotificationMessage $message,
    ) {}

    public function getMessage(): NotificationMessage
    {
        return $this->message;
    }

    /**
     * @return array<int, class-string>
     */
    public function via(object $notifiable): array
    {
        $config = app(ChannelConfigServiceInterface::class);
        $channels = [];

        foreach (NotificationChannel::cases() as $channel) {
            if ($config->isChannelEnabled($channel)
                && in_array($this->message->contentType, $config->getEnabledContentTypes($channel), true)) {
                $channels[] = $channel->channelClass();
            }
        }

        return $channels;
    }

    /**
     * @return array{bot_token: string, chat_id: string, text: string, image_url: ?string}
     */
    public function toTelegram(): array
    {
        $config = app(ChannelConfigServiceInterface::class);
        $credentials = $config->getChannelCredentials(NotificationChannel::Telegram);
        $template = $config->getTemplate(NotificationChannel::Telegram, $this->message->contentType);
        $text = $this->renderTemplate($template);

        return [
            'bot_token' => $credentials['bot_token'],
            'chat_id' => $credentials['chat_id'],
            'text' => $text,
            'image_url' => $this->message->imageUrl,
        ];
    }

    /**
     * @return array{webhook_url: string, title: string, text: string, content_url: string, image_url: ?string}
     */
    public function toDiscord(): array
    {
        $config = app(ChannelConfigServiceInterface::class);
        $credentials = $config->getChannelCredentials(NotificationChannel::Discord);
        $template = $config->getTemplate(NotificationChannel::Discord, $this->message->contentType);
        $text = $this->renderTemplate($template);

        return [
            'webhook_url' => $credentials['webhook_url'],
            'title' => $this->message->title,
            'text' => $text,
            'content_url' => $this->message->contentUrl,
            'image_url' => $this->message->imageUrl,
        ];
    }

    /**
     * @return array{webhook_url: string, title: string, text: string, content_url: string, image_url: ?string}
     */
    public function toSlack(): array
    {
        $config = app(ChannelConfigServiceInterface::class);
        $credentials = $config->getChannelCredentials(NotificationChannel::Slack);
        $template = $config->getTemplate(NotificationChannel::Slack, $this->message->contentType);
        $text = $this->renderTemplate($template);

        return [
            'webhook_url' => $credentials['webhook_url'],
            'title' => $this->message->title,
            'text' => $text,
            'content_url' => $this->message->contentUrl,
            'image_url' => $this->message->imageUrl,
        ];
    }

    /**
     * @return array{access_token: string, phone_number_id: string, recipient: string, text: string, image_url: ?string}
     */
    public function toWhatsApp(): array
    {
        $config = app(ChannelConfigServiceInterface::class);
        $credentials = $config->getChannelCredentials(NotificationChannel::WhatsApp);
        $template = $config->getTemplate(NotificationChannel::WhatsApp, $this->message->contentType);
        $text = $this->renderTemplate($template);

        return [
            'access_token' => $credentials['access_token'],
            'phone_number_id' => $credentials['phone_number_id'],
            'recipient' => $credentials['recipient'],
            'text' => $text,
            'image_url' => $this->message->imageUrl,
        ];
    }

    private function renderTemplate(string $template): string
    {
        $renderer = app(TemplateRenderer::class);
        $guildName = app(\App\Application\Services\SettingsServiceInterface::class)->get('guild_name', 'GuildForge');

        return $renderer->render($template, $this->message, (string) $guildName);
    }
}
