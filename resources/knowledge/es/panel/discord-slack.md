---
id: channel-notifications.panel.discord-slack
title: Conectar un canal de Discord o de Slack
summary: Crea un webhook en el canal, pega su URL y elige qué se anuncia en Discord o en Slack.
category: site-settings
audience: panel
order: 62
keywords: [discord, slack, webhook, url del webhook, webhook entrante, incoming webhook, integraciones, servidor de discord, espacio de trabajo, conectar discord, conectar slack, discrod]
related: [channel-notifications.panel.overview, channel-notifications.panel.templates, channel-notifications.panel.test-message]
conditions:
  permissions:
    all: ['channel-notifications:notifications.manage']
---
Discord y Slack reciben los anuncios por un webhook: una dirección secreta que publica en un canal concreto todo lo que se le envía.

## Antes de empezar

- **Discord**: en el servidor, abre los ajustes del canal, entra en **Integraciones** y luego en **Webhooks**. Crea uno y copia su URL.
- **Slack**: en la configuración de tu espacio de trabajo, crea un webhook entrante para el canal que quieras y copia su URL.

Trata la URL como una contraseña: quien la tenga puede escribir en el canal.

## Conectarlo

1. En el menú lateral, abre **Sistema**, luego **Configuración** y elige **Notificaciones a canales**.
2. Abre la pestaña **Discord** o **Slack** y activa **Canal activo**.
3. En **Credenciales**, pega la **URL del webhook**.
4. En **Tipos de contenido**, marca lo que quieres anunciar: **Eventos**, **Artículos**, **Galerías** o **Encuestas**.
5. Pulsa **Enviar prueba** para comprobarlo.
6. Pulsa **Guardar**.

## Cómo se ve el mensaje

- **Discord**: una tarjeta con el título del contenido, que enlaza a la web, el texto de la plantilla debajo y la imagen si la hay.
- **Slack**: el título como cabecera, el texto de la plantilla, la imagen si la hay y un botón **Ver más** con el enlace.

Por eso las plantillas por defecto de Discord y Slack no repiten el título ni el enlace: ya salen aparte.

## Si algo no sale bien

- **«Error al enviar el mensaje de prueba: HTTP 404»** (o 401, 403): la URL del webhook no es correcta o el webhook se ha borrado. Crea otro y pega su URL.
- **No llega nada a Slack**: comprueba que el webhook es del canal que miras; cada webhook entrante publica en un solo canal.

[[action:filament.admin.configuracion.pages.notification-channel-settings|Abrir las notificaciones a canales]]
