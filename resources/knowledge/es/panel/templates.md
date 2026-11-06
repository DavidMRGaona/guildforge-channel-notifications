---
id: channel-notifications.panel.templates
title: Personalizar el texto de los anuncios
summary: Cambia el mensaje de cada canal y tipo de contenido con plantillas y placeholders como {title} o {url}.
category: site-settings
audience: panel
order: 64
keywords: [plantilla, plantillas, plantillas de mensajes, texto del anuncio, mensaje, formato, personalizar, placeholder, placeholders, variables, etiquetas, title, url, excerpt, guild_name, date, price, emojis, plantilla por defecto]
related: [channel-notifications.panel.overview, channel-notifications.panel.test-message, channel-notifications.panel.telegram]
conditions:
  permissions:
    all: ['channel-notifications:notifications.manage']
---
Cada canal tiene una plantilla por tipo de contenido: cuatro canales por cuatro tipos, dieciséis en total. Si dejas una vacía, se usa la plantilla por defecto, que ya trae un texto con emojis pensado para cada canal.

## Cambiar una plantilla

1. En el menú lateral, abre **Configuración** y elige **Notificaciones a canales**.
2. Abre la pestaña del canal. Con **Canal activo** encendido, despliega **Plantillas de mensajes**.
3. Escribe en **Plantilla de eventos**, **Plantilla de artículos**, **Plantilla de galerías** o **Plantilla de encuestas**. Mientras está vacía, el campo muestra en gris la plantilla por defecto; puedes copiarla y cambiarla.
4. Usa los placeholders que salen debajo de cada campo: se cambian por el dato real al enviar.
5. Pulsa **Enviar prueba** para ver cómo queda y luego **Guardar**.

Para volver a la plantilla por defecto, borra todo el texto del campo y guarda.

## Placeholders

| Placeholder | Qué pone | Tipos |
|---|---|---|
| `{title}` | El título | Todos |
| `{excerpt}` | Un extracto de hasta 200 caracteres, sin formato | Todos |
| `{url}` | El enlace al contenido en la web | Todos |
| `{guild_name}` | El nombre del sitio | Todos |
| `{image_url}` | La dirección de la imagen | Eventos, artículos y galerías |
| `{tags}` | Las etiquetas, separadas por comas | Eventos, artículos y galerías |
| `{date}` y `{end_date}` | La fecha y hora de inicio y de fin | Eventos |
| `{location}` | La ubicación | Eventos |
| `{price}` | El precio: «Gratis», un solo precio o el de socios y no socios | Eventos |
| `{author}` | Quien firma el artículo | Artículos |
| `{photo_count}` | Cuántas fotos tiene | Galerías |
| `{close_date}` | Cuándo cierra, o «Sin fecha de cierre» | Encuestas |
| `{survey_kind}` | «Nueva encuesta» o «Nuevo sondeo» | Encuestas |

Un placeholder que no es de ese tipo no se rellena: puede salir tal cual en el mensaje.

## Lo que cambia según el canal

- **Telegram**: admite HTML sencillo, como `<b>negrita</b>` o `<a href="{url}">Ver evento</a>`.
- **Discord** y **Slack**: el título y el enlace ya salen aparte, encima del texto. La plantilla es lo que va debajo. En Discord, la negrita es `**así**`; en Slack, `*así*`.
- **WhatsApp**: texto sin formato HTML; la negrita es `*así*`. Pon `{url}` si quieres que lleve el enlace.

> [!TIP]
> Si el contenido tiene imagen, Telegram y WhatsApp ponen el texto como pie de foto, y esos pies tienen un límite de longitud más corto que un mensaje normal. Las plantillas breves dan menos problemas.

## Si algo no sale bien

- **Sale `{date}` (o otro placeholder) tal cual**: ese placeholder no es del tipo de contenido de la plantilla. Mira la tabla.
- **Telegram rechaza el mensaje**: alguna etiqueta HTML de la plantilla está mal cerrada o no la admite Telegram. Prueba con **Enviar prueba** hasta que salga bien.

[[action:filament.admin.pages.notification-channel-settings|Abrir las notificaciones a canales]]
