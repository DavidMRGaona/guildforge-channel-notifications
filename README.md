# Notificaciones a canales

Notificaciones automáticas a canales externos (Telegram, Discord, Slack, WhatsApp) cuando se publica contenido en GuildForge. Detecta la publicación de eventos, artículos y galerías, y envía mensajes formateados con imagen, extracto y enlace.

## Características

- **4 canales soportados**: Telegram, Discord, Slack, WhatsApp
- **Detección automática**: Observer de Eloquent detecta cuando `is_published` pasa a `true`
- **Granularidad canal x tipo**: cada canal puede recibir solo eventos, artículos, galerías, o cualquier combinación
- **Plantillas personalizables**: 12 plantillas (4 canales x 3 tipos de contenido) con placeholders
- **Envío asíncrono**: las notificaciones se encolan via `ShouldQueue`
- **Credenciales encriptadas**: tokens y webhooks almacenados con `Crypt::encryptString`
- **Botón de prueba**: envío de test desde el panel de administración
- **Sin frontend**: módulo puramente backend + Filament

## Requisitos

- PHP >= 8.2
- GuildForge core

## Instalación

1. Copiar el módulo a `src/modules/channel-notifications/`

2. Descubrir y habilitar el módulo:

```bash
php artisan module:discover
php artisan module:enable channel-notifications
```

No requiere migraciones (almacena configuración en la tabla `settings` existente).

## Configuración

Una vez habilitado, aparecerá **Notificaciones a canales** en el grupo **Configuración** del menú lateral del panel de administración. Solo accesible para administradores.

### Por canal

Cada pestaña permite:

1. **Activar/desactivar** el canal
2. **Credenciales** (encriptadas en BD)
3. **Tipos de contenido** a notificar
4. **Plantillas** personalizables (sección colapsable)
5. **Enviar prueba** para verificar la configuración

### Credenciales por canal

| Canal | Campos |
|-------|--------|
| Telegram | Token del bot, ID del chat |
| Discord | URL del webhook |
| Slack | URL del webhook |
| WhatsApp | Token de acceso, ID del número, destinatario |

### Placeholders disponibles

| Placeholder | Descripción | Disponible en |
|-------------|-------------|---------------|
| `{title}` | Título del contenido | Todos |
| `{excerpt}` | Extracto o descripción breve | Todos |
| `{url}` | Enlace al contenido | Todos |
| `{guild_name}` | Nombre de la asociación | Todos |
| `{image_url}` | URL de la imagen | Todos |
| `{date}` | Fecha del evento | Solo eventos |
| `{location}` | Ubicación del evento | Solo eventos |

## Funcionamiento

### Trigger

El observer `ContentPublishedObserver` escucha los modelos `EventModel`, `ArticleModel` y `GalleryModel`:

- **`created`**: si el modelo se crea con `is_published = true`, notifica
- **`updated`**: si `is_published` cambia de `false` a `true`, notifica
- Editar un modelo ya publicado sin cambiar `is_published` **no** genera notificación

### Flujo

```
Modelo publicado
  → Observer detecta cambio
    → Construye NotificationMessage (título, extracto, imagen, URL)
      → ContentPublishedNotification (queued)
        → Para cada canal habilitado con ese tipo de contenido:
          → TemplateRenderer renderiza la plantilla
            → Canal envía via HTTP (Telegram API, Discord webhook, etc.)
```

### Formato por canal

| Canal | Formato | Imagen |
|-------|---------|--------|
| Telegram | HTML (`sendPhoto` con caption o `sendMessage`) | Sí, via `photo` param |
| Discord | Embed (título, descripción, color, URL) | Sí, via `image.url` |
| Slack | Blocks (header, section, image, actions) | Sí, via image block |
| WhatsApp | Texto plano o imagen + caption | Sí, via image message |

## Arquitectura

```
channel-notifications/
├── config/
│   └── module.php             # Activación del módulo
├── lang/
│   └── es/
│       └── messages.php       # Traducciones y plantillas por defecto
├── resources/
│   └── views/
│       └── filament/pages/    # Vista Blade de la página de settings
├── routes/
│   ├── api.php                # (vacío)
│   └── web.php                # (vacío)
├── src/
│   ├── Application/
│   │   └── Services/
│   │       └── ChannelConfigServiceInterface.php
│   ├── Domain/
│   │   ├── Enums/
│   │   │   ├── ContentType.php          # Event, Article, Gallery
│   │   │   └── NotificationChannel.php  # Telegram, Discord, Slack, WhatsApp
│   │   └── ValueObjects/
│   │       └── NotificationMessage.php  # VO inmutable con datos del contenido
│   ├── Filament/
│   │   └── Pages/
│   │       └── NotificationChannelSettings.php  # Página de configuración
│   ├── Infrastructure/
│   │   ├── Channels/
│   │   │   ├── TelegramChannel.php
│   │   │   ├── DiscordChannel.php
│   │   │   ├── SlackChannel.php
│   │   │   └── WhatsAppChannel.php
│   │   ├── Observers/
│   │   │   └── ContentPublishedObserver.php
│   │   └── Services/
│   │       ├── ChannelConfigService.php
│   │       └── TemplateRenderer.php
│   └── Notifications/
│       ├── ContentPublishedNotification.php  # ShouldQueue
│       └── NotificationTarget.php            # Notifiable ligero
├── tests/
│   ├── Feature/
│   │   └── Infrastructure/
│   │       ├── Channels/          # Tests HTTP con Http::fake()
│   │       └── Observers/         # Tests del observer
│   └── Unit/
│       ├── Domain/
│       │   ├── Enums/             # Tests de enums
│       │   └── ValueObjects/      # Tests del VO
│       └── Infrastructure/
│           └── Services/          # Tests de servicios (mocks)
├── module.json                    # Manifiesto del módulo
└── phpunit.xml                    # Configuración de tests
```

## Permisos

| Permiso | Descripción |
|---------|-------------|
| `notifications.manage` | Gestionar notificaciones a canales |

Asignado al rol `admin` por defecto.

## Settings keys

El módulo almacena configuración en la tabla `settings` (40 claves):

```
notifications_{canal}_enabled              # bool
notifications_{canal}_bot_token            # encrypted (Telegram)
notifications_{canal}_chat_id              # string (Telegram)
notifications_{canal}_webhook_url          # encrypted (Discord, Slack)
notifications_{canal}_access_token         # encrypted (WhatsApp)
notifications_{canal}_phone_number_id      # string (WhatsApp)
notifications_{canal}_recipient            # string (WhatsApp)
notifications_{canal}_content_types        # JSON ["event","article","gallery"]
notifications_template_{canal}_{tipo}      # texto con placeholders (x12)
```

## Tests

```bash
# Desde el directorio src/
cd src

# Todos los tests del módulo
./vendor/bin/phpunit modules/channel-notifications/tests/

# Solo tests unitarios
./vendor/bin/phpunit modules/channel-notifications/tests/Unit/

# Solo tests de integración
./vendor/bin/phpunit modules/channel-notifications/tests/Feature/

# Análisis estático
./vendor/bin/phpstan analyse modules/channel-notifications/src/

# Estilo de código
./vendor/bin/pint modules/channel-notifications/
```

## Licencia

Este módulo es parte de GuildForge y está bajo la misma licencia del proyecto principal.
