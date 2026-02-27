<?php

declare(strict_types=1);

return [
    'title' => 'Notificaciones a canales',

    // Settings page
    'settings' => [
        'title' => 'Notificaciones a canales',
        'description' => 'Configura las notificaciones automáticas a canales externos cuando se publica contenido.',
        'navigation_label' => 'Notificaciones a canales',
        'navigation_group' => 'Configuración',

        'tabs' => [
            'telegram' => 'Telegram',
            'discord' => 'Discord',
            'slack' => 'Slack',
            'whatsapp' => 'WhatsApp',
        ],

        'fields' => [
            'enabled' => 'Canal activo',
            'bot_token' => 'Token del bot',
            'chat_id' => 'ID del chat',
            'webhook_url' => 'URL del webhook',
            'access_token' => 'Token de acceso',
            'phone_number_id' => 'ID del número de teléfono',
            'recipient' => 'Número de destinatario',
            'credentials' => 'Credenciales',
            'content_types' => 'Tipos de contenido',
            'content_types_helper' => 'Selecciona qué tipos de contenido enviar a este canal.',
            'templates_section' => 'Plantillas de mensajes',
            'templates_helper' => 'Personaliza el formato de los mensajes. Placeholders disponibles:',
            'template_event' => 'Plantilla de eventos',
            'template_article' => 'Plantilla de artículos',
            'template_gallery' => 'Plantilla de galerías',
        ],

        'helpers' => [
            'telegram_bot_token' => 'Obtén el token creando un bot con @BotFather en Telegram.',
            'telegram_chat_id' => 'ID del chat, grupo o canal donde enviar las notificaciones.',
            'discord_webhook_url' => 'Configura un webhook en Ajustes del canal > Integraciones > Webhooks.',
            'slack_webhook_url' => 'Configura un webhook entrante en la configuración de tu espacio de trabajo de Slack.',
            'whatsapp_access_token' => 'Token de acceso permanente de la API de WhatsApp Business.',
            'whatsapp_phone_number_id' => 'ID del número de teléfono de la API de WhatsApp Business.',
            'whatsapp_recipient' => 'Número de teléfono del destinatario (con código de país, ej: 34612345678).',
        ],

        'actions' => [
            'test' => 'Enviar prueba',
            'test_success' => 'Mensaje de prueba enviado correctamente.',
            'test_error' => 'Error al enviar el mensaje de prueba: :error',
            'test_not_configured' => 'El canal no está configurado. Guarda la configuración primero.',
            'saved' => 'Configuración de notificaciones guardada.',
        ],
    ],

    // Content types
    'content_types' => [
        'event' => 'Eventos',
        'article' => 'Artículos',
        'gallery' => 'Galerías',
    ],

    // Placeholders
    'placeholders' => [
        'title' => 'Título del contenido',
        'excerpt' => 'Extracto o descripción breve',
        'url' => 'Enlace al contenido',
        'guild_name' => 'Nombre de la asociación',
        'image_url' => 'URL de la imagen',
        'date' => 'Fecha del evento',
        'location' => 'Ubicación del evento',
    ],

    // Permissions
    'permissions' => [
        'notifications' => [
            'manage' => 'Gestionar notificaciones a canales',
        ],
    ],

    // Default templates
    'defaults' => [
        'telegram' => [
            'event' => "<b>{guild_name}</b> — Nuevo evento\n\n📌 <b>{title}</b>\n📅 {date}\n📍 {location}\n\n{excerpt}\n\n🔗 <a href=\"{url}\">Ver más</a>",
            'article' => "<b>{guild_name}</b> — Nuevo artículo\n\n📰 <b>{title}</b>\n\n{excerpt}\n\n🔗 <a href=\"{url}\">Leer más</a>",
            'gallery' => "<b>{guild_name}</b> — Nueva galería\n\n📸 <b>{title}</b>\n\n{excerpt}\n\n🔗 <a href=\"{url}\">Ver galería</a>",
        ],
        'discord' => [
            'event' => "📌 {title}\n📅 {date} · 📍 {location}\n\n{excerpt}",
            'article' => "📰 {title}\n\n{excerpt}",
            'gallery' => "📸 {title}\n\n{excerpt}",
        ],
        'slack' => [
            'event' => "📌 {title}\n📅 {date} · 📍 {location}\n\n{excerpt}",
            'article' => "📰 {title}\n\n{excerpt}",
            'gallery' => "📸 {title}\n\n{excerpt}",
        ],
        'whatsapp' => [
            'event' => "{guild_name} — Nuevo evento: {title}\n{date} · {location}\n{excerpt}\n{url}",
            'article' => "{guild_name} — Nuevo artículo: {title}\n{excerpt}\n{url}",
            'gallery' => "{guild_name} — Nueva galería: {title}\n{excerpt}\n{url}",
        ],
    ],

    // Test notification
    'test' => [
        'title' => 'Mensaje de prueba',
        'excerpt' => 'Este es un mensaje de prueba de las notificaciones a canales.',
    ],
];
