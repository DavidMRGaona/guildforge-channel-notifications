# Notificaciones a canales

Módulo de GuildForge que avisa en canales externos (Telegram, Discord, Slack y WhatsApp) cuando se publica contenido. Detecta la publicación de eventos, artículos y galerías y envía a cada canal un mensaje con imagen, extracto y enlace.

## Características

- **Cuatro canales**: Telegram, Discord, Slack y WhatsApp.
- **Detección automática**: un observer de Eloquent detecta cuándo `is_published` pasa a `true`.
- **Canal × tipo de contenido**: cada canal recibe solo los tipos que se marquen (eventos, artículos, galerías o cualquier combinación).
- **Plantillas personalizables**: 12 plantillas (4 canales × 3 tipos de contenido) con placeholders; si una plantilla está vacía se usa la plantilla por defecto.
- **Envío en cola**: la notificación implementa `ShouldQueue`, así que necesita un worker de colas en marcha.
- **Credenciales cifradas**: tokens y URLs de webhook se guardan cifrados en la tabla `settings`.
- **Envío de prueba**: cada canal tiene un botón para mandar un mensaje de prueba desde el panel.
- **Sin frontend**: el módulo no añade páginas públicas ni rutas propias.

## Requisitos

- GuildForge core `^3.22`
- PHP >= 8.5, Laravel >= 13, Filament 5

El core rechaza el módulo si no se cumplen (`module.json` → `requires`).

El módulo no tiene tablas propias: las fechas de los eventos llegan en la zona de almacenamiento del sitio (UTC cuando el core lo indica) y los mensajes las muestran en la zona de visualización (Europe/Madrid). Declara `"storesUtc": true`.

## Instalación

1. Descarga `channel-notifications-X.Y.Z.zip` de la última release de `DavidMRGaona/guildforge-channel-notifications` en GitHub.
2. En el panel, activa el modo mantenimiento (**Configuración del sitio** → pestaña **Mantenimiento**).
3. En **Módulos**, pulsa **Instalar / actualizar** y sube el ZIP.
4. Pulsa **Habilitar** en el módulo.
5. Desactiva el modo mantenimiento.

No tiene migraciones: guarda su configuración en la tabla `settings` del core.

Las versiones nuevas se aplican desde **Actualizaciones de módulos** (también con el modo mantenimiento activo) o con `php artisan module:update channel-notifications`.

### En desarrollo

Con el repositorio clonado en `src/modules/channel-notifications`:

```bash
php artisan module:discover
php artisan module:enable channel-notifications
```

El módulo no tiene componentes Vue, así que no hay assets que compilar.

## Configuración

Al habilitarlo aparece **Notificaciones a canales** en el grupo **Configuración** del panel. Pueden abrir la página los administradores y los usuarios con el permiso `notifications.manage` (ver [Permisos](#permisos)).

La página tiene una pestaña por canal. En cada una:

1. **Activar** el canal (el resto de campos aparece al activarlo).
2. **Credenciales** (las secretas se guardan cifradas).
3. **Tipos de contenido** que se notifican.
4. **Plantillas** por tipo de contenido (sección plegada; vacía = plantilla por defecto).
5. **Enviar prueba**: pide el tipo de contenido y envía un mensaje de ejemplo con los valores del formulario, aunque no se hayan guardado.

### Credenciales por canal

| Canal | Campos |
|-------|--------|
| Telegram | Token del bot, ID del chat |
| Discord | URL del webhook |
| Slack | URL del webhook |
| WhatsApp | Token de acceso, ID del número, destinatarios (separados por comas) y, opcionalmente, URL de un webhook externo |

En WhatsApp se envía un mensaje por destinatario con la API de WhatsApp Cloud (necesita token e ID del número). Si hay URL de webhook, además se publica el mensaje en ella como JSON (`channel`, `timestamp`, `message.text`, `message.image_url` y `metadata`), pensado para un puente propio que reenvíe a grupos. Basta con configurar una de las dos vías.

### Placeholders

| Placeholder | Descripción | Disponible en |
|-------------|-------------|---------------|
| `{title}` | Título del contenido | Todos |
| `{excerpt}` | Extracto (hasta 200 caracteres, sin HTML) | Todos |
| `{url}` | Enlace al contenido | Todos |
| `{guild_name}` | Nombre de la asociación | Todos |
| `{image_url}` | URL de la imagen | Todos |
| `{tags}` | Etiquetas, separadas por comas | Todos |
| `{date}` | Fecha y hora de inicio | Eventos |
| `{end_date}` | Fecha y hora de fin | Eventos |
| `{location}` | Ubicación | Eventos |
| `{price}` | Precio (socios / no socios, o «Gratis») | Eventos |
| `{author}` | Autor | Artículos |
| `{photo_count}` | Número de fotos | Galerías |

Las plantillas por defecto están en `lang/es/messages.php` (`defaults`).

## Funcionamiento

### Cuándo se notifica

El observer `ContentPublishedObserver` escucha `EventModel`, `ArticleModel` y `GalleryModel`:

- **`created`**: si el modelo se crea con `is_published = true`, notifica.
- **`updated`**: si `is_published` cambia y queda en `true`, notifica.
- Editar un contenido ya publicado sin cambiar `is_published` no genera notificación.

### Flujo

```
Contenido publicado
  → ContentPublishedObserver construye un NotificationMessage (título, extracto, imagen, URL, datos extra)
    → ContentPublishedNotification (en cola)
      → para cada canal activo que tenga marcado ese tipo de contenido:
        → TemplateRenderer rellena la plantilla
          → el canal envía por HTTP (API de Telegram, webhook de Discord o Slack, API de WhatsApp / webhook)
```

Los fallos de envío se registran en el log y no interrumpen el resto de canales.

### Formato por canal

| Canal | Formato | Imagen |
|-------|---------|--------|
| Telegram | HTML (`sendPhoto` con pie de foto o `sendMessage`) | Sí |
| Discord | Embed (título, descripción, color, enlace) | Sí |
| Slack | Blocks (cabecera, texto, imagen, botón con enlace) | Sí |
| WhatsApp | Texto, o imagen con pie de foto | Sí |

## Permisos

| Permiso | Descripción |
|---------|-------------|
| `notifications.manage` | Gestionar notificaciones a canales |

El permiso se guarda como `channel-notifications:notifications.manage` y es el que comprueba la página de configuración, tanto al abrirla y mostrarla en la navegación como al guardar o enviar una prueba.

- Los administradores tienen siempre acceso, porque el rol `admin` tiene todos los permisos.
- Para dar acceso a otro usuario, asigna el permiso a uno de sus roles desde el editor de roles del panel. El usuario necesita además poder entrar en el panel (rol `admin` o `editor`).
- El rol propuesto es `admin`; habilitar el módulo no asigna el permiso a ningún otro rol.

## Claves de configuración

El módulo guarda 28 claves en la tabla `settings`:

```
notifications_{canal}_enabled              # bool (x4)
notifications_{canal}_content_types        # JSON ["event","article","gallery"] (x4)
notifications_telegram_bot_token           # cifrado
notifications_telegram_chat_id             # texto
notifications_discord_webhook_url          # cifrado
notifications_slack_webhook_url            # cifrado
notifications_whatsapp_access_token        # cifrado
notifications_whatsapp_phone_number_id     # texto
notifications_whatsapp_recipients          # texto, números separados por comas
notifications_whatsapp_webhook_url         # cifrado, webhook externo opcional
notifications_template_{canal}_{tipo}      # texto con placeholders (x12)
```

`{canal}` es `telegram`, `discord`, `slack` o `whatsapp`; `{tipo}` es `event`, `article` o `gallery`.

## Arquitectura

```
channel-notifications/
├── config/
│   └── module.php                 # Activación del módulo
├── lang/
│   └── es/
│       └── messages.php           # Textos y plantillas por defecto
├── resources/
│   └── views/
│       └── filament/pages/        # Vista Blade de la página de configuración
├── routes/
│   ├── api.php                    # Grupo vacío
│   └── web.php                    # Grupo vacío
├── src/
│   ├── Application/
│   │   └── Services/
│   │       └── ChannelConfigServiceInterface.php
│   ├── Domain/
│   │   ├── Enums/
│   │   │   ├── ContentType.php          # Event, Article, Gallery (+ placeholders)
│   │   │   └── NotificationChannel.php  # Telegram, Discord, Slack, WhatsApp
│   │   └── ValueObjects/
│   │       └── NotificationMessage.php  # VO inmutable con los datos del contenido
│   ├── Filament/
│   │   └── Pages/
│   │       └── NotificationChannelSettings.php
│   ├── Infrastructure/
│   │   ├── Channels/              # TelegramChannel, DiscordChannel, SlackChannel, WhatsAppChannel
│   │   ├── Observers/
│   │   │   └── ContentPublishedObserver.php
│   │   └── Services/
│   │       ├── ChannelConfigService.php
│   │       └── TemplateRenderer.php
│   ├── Notifications/
│   │   ├── ContentPublishedNotification.php  # ShouldQueue
│   │   └── NotificationTarget.php            # Notifiable ligero
│   └── ChannelNotificationsServiceProvider.php
├── tests/
│   ├── Unit/                      # Enums, VO y servicios
│   ├── Integration/
│   └── Feature/                   # Canales (Http::fake), observer, página del panel
├── module.json
└── phpunit.xml
```

## Tests

Desde `src/` (o dentro del contenedor `guildforge_app`):

```bash
vendor/bin/phpunit -c modules/channel-notifications/phpunit.xml
vendor/bin/phpunit -c modules/channel-notifications/phpunit.xml --testsuite=Unit
```

`php artisan test` no ejecuta los tests de los módulos.

## Publicar una versión

1. Sube `version` en `module.json`.
2. Crea y empuja la etiqueta `vX.Y.Z` (debe coincidir con `version`).
3. El workflow `release.yml` ejecuta los tests contra el core (`main`, o la variable `GUILDFORGE_HOST_REF`) y publica `channel-notifications-X.Y.Z.zip` con su `.sha256` en la release.

## Licencia

Este módulo es parte de GuildForge y está bajo la misma licencia del proyecto principal.
