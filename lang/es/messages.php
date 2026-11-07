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
            'recipients' => 'Destinatarios',
            'whatsapp_webhook_url' => 'URL del webhook',
            'credentials' => 'Credenciales',
            'content_types' => 'Tipos de contenido',
            'content_types_helper' => 'Selecciona qué tipos de contenido enviar a este canal.',
            'templates_section' => 'Plantillas de mensajes',
            'templates_helper' => 'Personaliza el formato de los mensajes. Placeholders disponibles:',
            'test_content_type' => 'Tipo de contenido',
            'template_event' => 'Plantilla de eventos',
            'template_article' => 'Plantilla de artículos',
            'template_gallery' => 'Plantilla de galerías',
            'template_survey' => 'Plantilla de encuestas',
        ],

        'helpers' => [
            'telegram_bot_token' => 'Obtén el token creando un bot con @BotFather en Telegram.',
            'telegram_chat_id' => 'ID del chat, grupo o canal donde enviar las notificaciones.',
            'discord_webhook_url' => 'Configura un webhook en Ajustes del canal > Integraciones > Webhooks.',
            'slack_webhook_url' => 'Configura un webhook entrante en la configuración de tu espacio de trabajo de Slack.',
            'whatsapp_access_token' => 'Token de acceso permanente de la API de WhatsApp Business.',
            'whatsapp_phone_number_id' => 'ID del número de teléfono de la API de WhatsApp Business.',
            'whatsapp_recipient' => 'Número de teléfono del destinatario (con código de país, ej: 34612345678).',
            'whatsapp_recipients' => 'Números de teléfono separados por comas (con código de país, ej: 34612345678, 34698765432).',
            'whatsapp_webhook_url' => 'URL de un webhook externo para reenviar notificaciones (whatsapp-web.js, Baileys, etc.). Permite enviar a grupos y comunidades.',
        ],

        'actions' => [
            'test' => 'Enviar prueba',
            'test_success' => 'Mensaje de prueba enviado correctamente.',
            'test_error' => 'Error al enviar el mensaje de prueba: :error',
            'test_disabled' => 'El canal está desactivado. Actívalo en el formulario para enviar la prueba.',
            'test_missing_fields' => 'Falta rellenar en el formulario: :fields.',
            'test_missing_whatsapp' => 'Falta rellenar en el formulario: la URL del webhook, o bien :fields.',
            'saved' => 'Configuración de notificaciones guardada.',
        ],
    ],

    // Content types
    'content_types' => [
        'event' => 'Eventos',
        'article' => 'Artículos',
        'gallery' => 'Galerías',
        'survey' => 'Encuestas',
    ],

    // Placeholders
    'placeholders' => [
        'title' => 'Título del contenido',
        'excerpt' => 'Extracto o descripción breve',
        'url' => 'Enlace al contenido',
        'guild_name' => 'Nombre de la asociación',
        'image_url' => 'URL de la imagen',
        'tags' => 'Etiquetas del contenido',
        'date' => 'Fecha del evento',
        'end_date' => 'Fecha de fin del evento',
        'location' => 'Ubicación del evento',
        'price' => 'Precio del evento',
        'author' => 'Autor del artículo',
        'photo_count' => 'Número de fotos de la galería',
        'close_date' => 'Cierre de la encuesta',
        'survey_kind' => '«Nueva encuesta» o «Nuevo sondeo»',
    ],

    // Event price shown in the {price} placeholder
    'prices' => [
        'free' => 'Gratis',
        'member_and_non_member' => 'Socios: :member_price€ · No socios: :non_member_price€',
        'single' => ':price€',
    ],

    // General surveys announced when they open (core event GeneralSurveyOpened)
    'surveys' => [
        'no_close_date' => 'Sin fecha de cierre',
        'kind' => [
            'survey' => 'Nueva encuesta',
            'poll' => 'Nuevo sondeo',
        ],
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
            'event' => "<b>{guild_name}</b> — Nuevo evento\n\n📌 <b>{title}</b>\n📅 {date} → {end_date}\n📍 {location}\n💰 {price}\n\n{excerpt}\n\n🏷 {tags}\n\n🔗 <a href=\"{url}\">Ver evento</a>",
            'article' => "<b>{guild_name}</b> — Nuevo artículo\n\n📰 <b>{title}</b>\n✍️ {author}\n\n{excerpt}\n\n🏷 {tags}\n\n🔗 <a href=\"{url}\">Leer artículo</a>",
            'gallery' => "<b>{guild_name}</b> — Nueva galería\n\n📸 <b>{title}</b>\n🖼 {photo_count} fotos\n\n{excerpt}\n\n🏷 {tags}\n\n🔗 <a href=\"{url}\">Ver galería</a>",
            'survey' => "<b>{guild_name}</b> — {survey_kind}\n\n🗳 <b>{title}</b>\n⏳ Cierre: {close_date}\n\n{excerpt}\n\n🔗 <a href=\"{url}\">Responder</a>",
        ],
        'discord' => [
            'event' => "📅 {date} → {end_date}\n📍 {location}\n💰 {price}\n\n{excerpt}\n\n🏷 {tags}",
            'article' => "✍️ {author}\n\n{excerpt}\n\n🏷 {tags}",
            'gallery' => "🖼 {photo_count} fotos\n\n{excerpt}\n\n🏷 {tags}",
            'survey' => "{survey_kind}\n⏳ Cierre: {close_date}\n\n{excerpt}",
        ],
        'slack' => [
            'event' => "📅 {date} → {end_date}\n📍 {location}\n💰 {price}\n\n{excerpt}\n\n🏷 {tags}",
            'article' => "✍️ {author}\n\n{excerpt}\n\n🏷 {tags}",
            'gallery' => "🖼 {photo_count} fotos\n\n{excerpt}\n\n🏷 {tags}",
            'survey' => "{survey_kind}\n⏳ Cierre: {close_date}\n\n{excerpt}",
        ],
        'whatsapp' => [
            'event' => "{guild_name} — Nuevo evento\n\n📌 {title}\n📅 {date} → {end_date}\n📍 {location}\n💰 {price}\n\n{excerpt}\n\n🏷 {tags}\n\n👉 {url}",
            'article' => "{guild_name} — Nuevo artículo\n\n📰 {title}\n✍️ {author}\n\n{excerpt}\n\n🏷 {tags}\n\n👉 {url}",
            'gallery' => "{guild_name} — Nueva galería\n\n📸 {title}\n🖼 {photo_count} fotos\n\n{excerpt}\n\n🏷 {tags}\n\n👉 {url}",
            'survey' => "{guild_name} — {survey_kind}\n\n🗳 {title}\n⏳ Cierre: {close_date}\n\n{excerpt}\n\n👉 {url}",
        ],
    ],

    // Test notification
    'test' => [
        'event' => [
            'title' => 'Torneo Warhammer 40K',
            'excerpt' => 'Gran torneo de Warhammer con premios para los tres primeros clasificados.',
            'date' => '15/03/2026 10:00',
            'end_date' => '16/03/2026 20:00',
            'location' => 'Local de la asociación',
            'price' => '10€',
            'tags' => 'warhammer, torneo, 40K',
        ],
        'article' => [
            'title' => 'Crónica de la última partida',
            'excerpt' => 'Resumen épico de la última sesión de rol donde el grupo se enfrentó al dragón.',
            'author' => 'El director',
            'tags' => 'crónica, rol, partida',
        ],
        'gallery' => [
            'title' => 'Fotos del último evento',
            'excerpt' => 'Recopilación de las mejores fotos del torneo del fin de semana.',
            'photo_count' => '24',
            'tags' => 'fotos, torneo, galería',
        ],
        'survey' => [
            'title' => 'Horario del local',
            'excerpt' => 'Queremos saber qué días y franjas os vienen mejor para abrir el local.',
            'close_date' => '15/03/2026 20:00',
        ],
    ],
];
