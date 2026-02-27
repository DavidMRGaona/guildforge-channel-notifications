<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Infrastructure\Services;

use App\Application\Services\SettingsServiceInterface;
use Modules\ChannelNotifications\Application\Services\ChannelConfigServiceInterface;
use Modules\ChannelNotifications\Domain\Enums\ContentType;
use Modules\ChannelNotifications\Domain\Enums\NotificationChannel;

final readonly class ChannelConfigService implements ChannelConfigServiceInterface
{
    public function __construct(
        private SettingsServiceInterface $settings,
    ) {}

    public function isChannelEnabled(NotificationChannel $channel): bool
    {
        $value = $this->settings->get($channel->settingsPrefix().'_enabled', '0');

        return $value === '1' || $value === 'true' || $value === true;
    }

    /**
     * @return array<string, string>
     */
    public function getChannelCredentials(NotificationChannel $channel): array
    {
        $prefix = $channel->settingsPrefix();

        return match ($channel) {
            NotificationChannel::Telegram => [
                'bot_token' => (string) $this->settings->getEncrypted($prefix.'_bot_token', ''),
                'chat_id' => (string) $this->settings->get($prefix.'_chat_id', ''),
            ],
            NotificationChannel::Discord => [
                'webhook_url' => (string) $this->settings->getEncrypted($prefix.'_webhook_url', ''),
            ],
            NotificationChannel::Slack => [
                'webhook_url' => (string) $this->settings->getEncrypted($prefix.'_webhook_url', ''),
            ],
            NotificationChannel::WhatsApp => [
                'access_token' => (string) $this->settings->getEncrypted($prefix.'_access_token', ''),
                'phone_number_id' => (string) $this->settings->get($prefix.'_phone_number_id', ''),
                'recipient' => (string) $this->settings->get($prefix.'_recipient', ''),
            ],
        };
    }

    /**
     * @return array<ContentType>
     */
    public function getEnabledContentTypes(NotificationChannel $channel): array
    {
        $json = $this->settings->get($channel->settingsPrefix().'_content_types', '[]');
        $values = json_decode(is_string($json) ? $json : '[]', true) ?: [];

        return array_filter(
            array_map(
                static fn (string $value): ?ContentType => ContentType::tryFrom($value),
                $values,
            ),
        );
    }

    public function getTemplate(NotificationChannel $channel, ContentType $contentType): string
    {
        $key = 'notifications_template_'.$channel->value.'_'.$contentType->value;
        $template = $this->settings->get($key);

        if ($template === null || $template === '') {
            return $this->getDefaultTemplate($channel, $contentType);
        }

        return (string) $template;
    }

    public function getDefaultTemplate(NotificationChannel $channel, ContentType $contentType): string
    {
        $key = "channel-notifications::messages.defaults.{$channel->value}.{$contentType->value}";

        return __($key);
    }
}
