<?php

declare(strict_types=1);

namespace Modules\ChannelNotifications\Filament\Pages;

use App\Application\Services\SettingsServiceInterface;
use App\Filament\Concerns\ManagesPageSettings;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\ChannelNotifications\Domain\Enums\ContentType;
use Modules\ChannelNotifications\Domain\Enums\NotificationChannel;

/**
 * @property Form $form
 */
final class NotificationChannelSettings extends Page implements HasForms
{
    use InteractsWithForms;
    use ManagesPageSettings;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?int $navigationSort = 95;

    protected static string $view = 'channel-notifications::filament.pages.notification-channel-settings';

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('channel-notifications::messages.settings.navigation_label');
    }

    public static function getNavigationGroup(): string
    {
        return __('channel-notifications::messages.settings.navigation_group');
    }

    public function getTitle(): string
    {
        return __('channel-notifications::messages.settings.title');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string>
     */
    protected function getSettingsKeys(): array
    {
        $keys = [];

        foreach (NotificationChannel::cases() as $channel) {
            $prefix = $channel->settingsPrefix();
            $keys[] = "{$prefix}_enabled";
            $keys[] = "{$prefix}_content_types";

            $keys = array_merge($keys, match ($channel) {
                NotificationChannel::Telegram => ["{$prefix}_bot_token", "{$prefix}_chat_id"],
                NotificationChannel::Discord => ["{$prefix}_webhook_url"],
                NotificationChannel::Slack => ["{$prefix}_webhook_url"],
                NotificationChannel::WhatsApp => ["{$prefix}_access_token", "{$prefix}_phone_number_id", "{$prefix}_recipient"],
            });

            foreach (ContentType::cases() as $contentType) {
                $keys[] = "notifications_template_{$channel->value}_{$contentType->value}";
            }
        }

        return $keys;
    }

    /**
     * @return array<string>
     */
    protected function getJsonFields(): array
    {
        return array_map(
            static fn (NotificationChannel $ch): string => $ch->settingsPrefix().'_content_types',
            NotificationChannel::cases(),
        );
    }

    /**
     * @return array<string>
     */
    protected function getImageFields(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getEncryptedFields(): array
    {
        return [
            'notifications_telegram_bot_token',
            'notifications_discord_webhook_url',
            'notifications_slack_webhook_url',
            'notifications_whatsapp_access_token',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getDefaultSettings(): array
    {
        $defaults = [];

        foreach (NotificationChannel::cases() as $channel) {
            $prefix = $channel->settingsPrefix();
            $defaults["{$prefix}_enabled"] = false;
            $defaults["{$prefix}_content_types"] = [];

            foreach (ContentType::cases() as $contentType) {
                $defaults["notifications_template_{$channel->value}_{$contentType->value}"] = '';
            }
        }

        return $defaults;
    }

    public function mount(SettingsServiceInterface $settingsService): void
    {
        $this->form->fill($this->loadSettings($settingsService));
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Channels')
                    ->tabs([
                        $this->buildChannelTab(NotificationChannel::Telegram),
                        $this->buildChannelTab(NotificationChannel::Discord),
                        $this->buildChannelTab(NotificationChannel::Slack),
                        $this->buildChannelTab(NotificationChannel::WhatsApp),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(SettingsServiceInterface $settingsService): void
    {
        $formData = $this->form->getState();
        $this->saveSettings($settingsService, $formData);

        Notification::make()
            ->title(__('channel-notifications::messages.settings.actions.saved'))
            ->success()
            ->send();
    }

    public function sendTest(string $channelValue): void
    {
        $channel = NotificationChannel::from($channelValue);
        $prefix = $channel->settingsPrefix();
        $state = $this->form->getState();

        if (($state["{$prefix}_enabled"] ?? false) !== true && $state["{$prefix}_enabled"] !== '1') {
            Notification::make()
                ->title(__('channel-notifications::messages.settings.actions.test_not_configured'))
                ->warning()
                ->send();

            return;
        }

        try {
            $this->dispatchTestNotification($channel, $state);

            Notification::make()
                ->title(__('channel-notifications::messages.settings.actions.test_success'))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Log::error('Test notification failed', ['channel' => $channelValue, 'error' => $e->getMessage()]);

            Notification::make()
                ->title(__('channel-notifications::messages.settings.actions.test_error', ['error' => $e->getMessage()]))
                ->danger()
                ->send();
        }
    }

    private function buildChannelTab(NotificationChannel $channel): Tab
    {
        $prefix = $channel->settingsPrefix();

        return Tab::make(__("channel-notifications::messages.settings.tabs.{$channel->value}"))
            ->icon($this->getChannelIcon($channel))
            ->schema([
                Toggle::make("{$prefix}_enabled")
                    ->label(__('channel-notifications::messages.settings.fields.enabled'))
                    ->live(),

                Section::make(__('channel-notifications::messages.settings.fields.credentials'))
                    ->schema($this->getCredentialFields($channel))
                    ->visible(fn (Get $get): bool => (bool) $get("{$prefix}_enabled")),

                CheckboxList::make("{$prefix}_content_types")
                    ->label(__('channel-notifications::messages.settings.fields.content_types'))
                    ->helperText(__('channel-notifications::messages.settings.fields.content_types_helper'))
                    ->options(array_combine(
                        array_map(static fn (ContentType $ct): string => $ct->value, ContentType::cases()),
                        array_map(static fn (ContentType $ct): string => $ct->label(), ContentType::cases()),
                    ))
                    ->visible(fn (Get $get): bool => (bool) $get("{$prefix}_enabled")),

                Section::make(__('channel-notifications::messages.settings.fields.templates_section'))
                    ->description(__('channel-notifications::messages.settings.fields.templates_helper'))
                    ->schema($this->getTemplateFields($channel))
                    ->visible(fn (Get $get): bool => (bool) $get("{$prefix}_enabled"))
                    ->collapsed(),

                Actions::make([
                    FormAction::make("test_{$channel->value}")
                        ->label(__('channel-notifications::messages.settings.actions.test'))
                        ->icon('heroicon-o-paper-airplane')
                        ->color('gray')
                        ->action(fn () => $this->sendTest($channel->value)),
                ])->visible(fn (Get $get): bool => (bool) $get("{$prefix}_enabled")),
            ]);
    }

    /**
     * @return array<\Filament\Forms\Components\Component>
     */
    private function getCredentialFields(NotificationChannel $channel): array
    {
        $prefix = $channel->settingsPrefix();

        return match ($channel) {
            NotificationChannel::Telegram => [
                TextInput::make("{$prefix}_bot_token")
                    ->label(__('channel-notifications::messages.settings.fields.bot_token'))
                    ->helperText(__('channel-notifications::messages.settings.helpers.telegram_bot_token'))
                    ->password()
                    ->revealable(),
                TextInput::make("{$prefix}_chat_id")
                    ->label(__('channel-notifications::messages.settings.fields.chat_id'))
                    ->helperText(__('channel-notifications::messages.settings.helpers.telegram_chat_id')),
            ],
            NotificationChannel::Discord => [
                TextInput::make("{$prefix}_webhook_url")
                    ->label(__('channel-notifications::messages.settings.fields.webhook_url'))
                    ->helperText(__('channel-notifications::messages.settings.helpers.discord_webhook_url'))
                    ->password()
                    ->revealable(),
            ],
            NotificationChannel::Slack => [
                TextInput::make("{$prefix}_webhook_url")
                    ->label(__('channel-notifications::messages.settings.fields.webhook_url'))
                    ->helperText(__('channel-notifications::messages.settings.helpers.slack_webhook_url'))
                    ->password()
                    ->revealable(),
            ],
            NotificationChannel::WhatsApp => [
                TextInput::make("{$prefix}_access_token")
                    ->label(__('channel-notifications::messages.settings.fields.access_token'))
                    ->helperText(__('channel-notifications::messages.settings.helpers.whatsapp_access_token'))
                    ->password()
                    ->revealable(),
                TextInput::make("{$prefix}_phone_number_id")
                    ->label(__('channel-notifications::messages.settings.fields.phone_number_id'))
                    ->helperText(__('channel-notifications::messages.settings.helpers.whatsapp_phone_number_id')),
                TextInput::make("{$prefix}_recipient")
                    ->label(__('channel-notifications::messages.settings.fields.recipient'))
                    ->helperText(__('channel-notifications::messages.settings.helpers.whatsapp_recipient')),
            ],
        };
    }

    /**
     * @return array<Textarea>
     */
    private function getTemplateFields(NotificationChannel $channel): array
    {
        $fields = [];

        foreach (ContentType::cases() as $contentType) {
            $key = "notifications_template_{$channel->value}_{$contentType->value}";
            $placeholderText = implode(', ', array_keys($contentType->placeholders()));

            $fields[] = Textarea::make($key)
                ->label(__("channel-notifications::messages.settings.fields.template_{$contentType->value}"))
                ->helperText($placeholderText)
                ->placeholder(
                    __("channel-notifications::messages.defaults.{$channel->value}.{$contentType->value}")
                )
                ->rows(4);
        }

        return $fields;
    }

    private function getChannelIcon(NotificationChannel $channel): string
    {
        return match ($channel) {
            NotificationChannel::Telegram => 'heroicon-o-paper-airplane',
            NotificationChannel::Discord => 'heroicon-o-chat-bubble-left-right',
            NotificationChannel::Slack => 'heroicon-o-hashtag',
            NotificationChannel::WhatsApp => 'heroicon-o-phone',
        };
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function dispatchTestNotification(NotificationChannel $channel, array $state): void
    {
        $settingsService = app(SettingsServiceInterface::class);
        $guildName = (string) $settingsService->get('guild_name', 'GuildForge');
        $testTitle = __('channel-notifications::messages.test.title');
        $testExcerpt = __('channel-notifications::messages.test.excerpt');
        $testUrl = url('/');

        $text = "{$guildName} — {$testTitle}\n\n{$testExcerpt}\n\n{$testUrl}";

        match ($channel) {
            NotificationChannel::Telegram => $this->sendTelegramTest($state, $text),
            NotificationChannel::Discord => $this->sendDiscordTest($state, $text, $testTitle),
            NotificationChannel::Slack => $this->sendSlackTest($state, $text, $testTitle),
            NotificationChannel::WhatsApp => $this->sendWhatsAppTest($state, $text),
        };
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function sendTelegramTest(array $state, string $text): void
    {
        $token = $state['notifications_telegram_bot_token'] ?? '';
        $chatId = $state['notifications_telegram_chat_id'] ?? '';

        $response = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ]);

        if ($response->failed()) {
            throw new \RuntimeException($response->json('description', 'Unknown error'));
        }
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function sendDiscordTest(array $state, string $text, string $title): void
    {
        $webhookUrl = $state['notifications_discord_webhook_url'] ?? '';

        $response = Http::timeout(10)->post($webhookUrl, [
            'embeds' => [[
                'title' => $title,
                'description' => $text,
                'color' => 0xD97706,
            ]],
        ]);

        if ($response->failed()) {
            throw new \RuntimeException("HTTP {$response->status()}");
        }
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function sendSlackTest(array $state, string $text, string $title): void
    {
        $webhookUrl = $state['notifications_slack_webhook_url'] ?? '';

        $response = Http::timeout(10)->post($webhookUrl, [
            'blocks' => [
                ['type' => 'header', 'text' => ['type' => 'plain_text', 'text' => $title]],
                ['type' => 'section', 'text' => ['type' => 'mrkdwn', 'text' => $text]],
            ],
        ]);

        if ($response->failed()) {
            throw new \RuntimeException("HTTP {$response->status()}");
        }
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function sendWhatsAppTest(array $state, string $text): void
    {
        $token = $state['notifications_whatsapp_access_token'] ?? '';
        $phoneId = $state['notifications_whatsapp_phone_number_id'] ?? '';
        $recipient = $state['notifications_whatsapp_recipient'] ?? '';

        $response = Http::timeout(10)
            ->withToken($token)
            ->post("https://graph.facebook.com/v21.0/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $recipient,
                'type' => 'text',
                'text' => ['body' => $text],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException($response->json('error.message', "HTTP {$response->status()}"));
        }
    }
}
