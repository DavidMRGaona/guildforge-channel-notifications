---
id: channel-notifications.panel.telegram
title: Conectar un grupo o canal de Telegram
summary: Crea un bot, pega su token y el ID del chat, y elige qué se anuncia en Telegram.
category: site-settings
audience: panel
order: 61
keywords: [telegram, bot, botfather, token, token del bot, id del chat, chat id, grupo de telegram, canal de telegram, conectar telegram, telgram]
related: [channel-notifications.panel.overview, channel-notifications.panel.templates, channel-notifications.panel.test-message]
conditions:
  permissions:
    all: ['channel-notifications:notifications.manage']
---
Telegram publica los anuncios a través de un bot: un usuario automático que escribe en el chat que le digas.

## Antes de empezar

1. En Telegram, habla con **@BotFather** y crea un bot. Al terminar te da un token, una cadena larga con dos puntos en medio.
2. Añade el bot al grupo o canal donde quieres los anuncios. En un canal, hazlo administrador para que pueda publicar.
3. Consigue el ID de ese chat. En los grupos y canales suele ser un número negativo, como -1001234567890. En un canal público también vale su nombre con @.

## Conectarlo

1. En el menú lateral, abre **Sistema**, luego **Configuración** y elige **Notificaciones a canales**.
2. En la pestaña **Telegram**, activa **Canal activo**.
3. En **Credenciales**, pega el **Token del bot** y el **ID del chat**.
4. En **Tipos de contenido**, marca lo que quieres anunciar: **Eventos**, **Artículos**, **Galerías** o **Encuestas**.
5. Pulsa **Enviar prueba** para comprobarlo.
6. Pulsa **Guardar**.

Desde ese momento, cada contenido de los tipos marcados que se publique sale en el chat. Si tiene imagen, se envía como foto con el texto debajo.

## Formato del mensaje

Telegram lee el mensaje como HTML sencillo. En las plantillas puedes usar etiquetas como `<b>`, `<i>` o `<a href="{url}">`. Los valores que se rellenan solos (el título, el extracto…) se escapan, así que un título con «&» o «<» no rompe el mensaje.

## Si algo no sale bien

- **«Error al enviar el mensaje de prueba: Unauthorized»**: el token no es correcto. Cópialo de nuevo desde @BotFather.
- **«… chat not found»**: el ID del chat no es correcto, o el bot no está en ese grupo o canal.
- **El bot no puede publicar en el canal**: hazlo administrador del canal.

[[action:filament.admin.configuracion.pages.notification-channel-settings|Abrir las notificaciones a canales]]
