---
id: channel-notifications.panel.overview
title: 'Notificaciones a canales: cómo funcionan'
summary: Qué anuncia el módulo en Telegram, Discord, Slack y WhatsApp, cuándo lo hace y quién puede configurarlo.
category: site-settings
audience: panel
order: 60
featured: true
keywords: [notificaciones, canales, avisos, anunciar, anuncio, difundir, redes, telegram, discord, slack, whatsapp, grupo, bot, webhook, publicar, cómo funciona, introducción, qué es]
related: [channel-notifications.panel.telegram, channel-notifications.panel.discord-slack, channel-notifications.panel.whatsapp, channel-notifications.panel.templates, channel-notifications.panel.test-message, core.panel.roles-and-permissions]
conditions:
  permissions:
    all: ['channel-notifications:notifications.manage']
---
Las notificaciones a canales publican un mensaje en los grupos y canales de {{site_name}} cada vez que sale algo nuevo en la web. Así nadie tiene que copiar el enlace a mano en Telegram, Discord, Slack o WhatsApp.

## Qué se anuncia y cuándo

| Tipo de contenido | Cuándo sale el mensaje |
|---|---|
| **Eventos**, **Artículos** y **Galerías** | Al guardarlo con **Publicado** (o **Publicada**) activado: al crearlo ya publicado o al activarlo después. |
| **Encuestas** | Si tu web tiene encuestas, cuando se abre por primera vez una encuesta general marcada para anunciarse en los canales. |

- Editar algo que ya estaba publicado no manda otro mensaje.
- Si despublicas un contenido y lo vuelves a publicar, se anuncia otra vez.
- Cada canal solo recibe los tipos de contenido que marques en él.

El mensaje lleva el título, un extracto de hasta 200 caracteres, el enlace y, en eventos, artículos y galerías, la imagen si la tiene. Las encuestas salen sin imagen.

## Los cuatro canales

| Canal | Qué necesitas |
|---|---|
| **Telegram** | Un bot y el chat, grupo o canal donde escribe |
| **Discord** | La URL de un webhook del canal |
| **Slack** | La URL de un webhook entrante |
| **WhatsApp** | La API de WhatsApp Business o un webhook propio que reenvíe los mensajes |

Puedes activar uno, varios o todos. Cada uno tiene su propia guía para conectarlo.

## Dónde se configura

En el menú lateral, abre **Configuración** y elige **Notificaciones a canales**. La página tiene una pestaña por canal. En cada una:

1. **Canal activo** enciende el canal. El resto de campos aparece al activarlo.
2. En **Credenciales** pegas los datos de conexión. Los tokens y las URL de webhook se guardan cifrados.
3. En **Tipos de contenido** marcas qué se anuncia en ese canal.
4. En **Plantillas de mensajes** puedes cambiar el texto de cada tipo de contenido. Vacías, se usa la plantilla por defecto.
5. **Enviar prueba** manda un mensaje de ejemplo para comprobar la conexión.

Al terminar, pulsa **Guardar**.

## Quién puede configurarlo

Hace falta el permiso **Gestionar notificaciones a canales**. Los administradores lo tienen siempre. Para dárselo a alguien más, añádelo a uno de sus roles en **Roles**. Esa persona también tiene que poder entrar en el panel.

## Si algo no sale bien

- **No aparece Notificaciones a canales en el menú**: te falta el permiso, o el módulo no está habilitado.
- **La prueba llega, pero los anuncios no**: comprueba que pulsaste **Guardar** y que el tipo de contenido está marcado en ese canal. Los anuncios reales salen en segundo plano: si tampoco llegan así, avisa a quien mantiene la web para que revise la cola de tareas.
- **Un canal falla y los demás no**: un fallo en un canal no frena a los otros. Revisa sus credenciales y prueba de nuevo con **Enviar prueba**.
- **No sé si un anuncio ha fallado**: los fallos de los anuncios reales no se muestran en el panel; quedan en el registro de errores de la web.

[[action:filament.admin.pages.notification-channel-settings|Abrir las notificaciones a canales]]
