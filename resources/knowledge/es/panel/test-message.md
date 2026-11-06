---
id: channel-notifications.panel.test-message
title: Enviar un mensaje de prueba
summary: Comprueba que un canal está bien conectado con un mensaje de ejemplo, antes o después de guardar.
category: site-settings
audience: panel
order: 65
keywords: [prueba, probar, enviar prueba, mensaje de prueba, comprobar, test, no llega, no funciona, error, fallo, conexión, ejemplo]
related: [channel-notifications.panel.overview, channel-notifications.panel.templates]
conditions:
  permissions:
    all: ['channel-notifications:notifications.manage']
---
La prueba manda un mensaje de ejemplo al canal para que veas si llega y cómo queda, sin esperar a publicar nada.

## Enviar la prueba

1. En el menú lateral, abre **Configuración** y elige **Notificaciones a canales**.
2. Abre la pestaña del canal y comprueba que **Canal activo** está encendido; el botón solo aparece entonces.
3. Pulsa **Enviar prueba**.
4. En **Tipo de contenido**, elige qué ejemplo quieres: **Eventos**, **Artículos**, **Galerías** o **Encuestas**, y confirma.

Sale un aviso con «Mensaje de prueba enviado correctamente.» o con el error que ha devuelto el servicio.

## Qué tiene en cuenta la prueba

- Usa lo que hay escrito en la página en ese momento, aunque no lo hayas guardado. Así puedes probar unas credenciales o una plantilla antes de guardarlas.
- Usa tu plantilla de ese tipo, o la de por defecto si está vacía, con datos de ejemplo y el nombre del sitio.
- No lleva imagen, y el enlace apunta a la página de inicio de la web.
- No mira **Tipos de contenido**: puedes probar un tipo aunque no esté marcado.
- En WhatsApp, va solo al primer número de **Destinatarios** y, si lo hay, al webhook.

> [!IMPORTANT]
> La prueba no guarda nada. Los anuncios reales usan lo guardado: pulsa **Guardar** al terminar.

## Si algo no sale bien

- **«Error al enviar el mensaje de prueba: …»**: lo que sigue es la respuesta del servicio. Suele ser un token, un ID o una URL de webhook mal copiados. Las guías de cada canal explican los errores más comunes.
- **«El canal no está configurado. Guarda la configuración primero.»**: en WhatsApp, faltan los destinatarios y la URL del webhook.
- **La prueba llega, pero los anuncios no**: revisa que guardaste y que el tipo de contenido está marcado. Los anuncios reales salen en segundo plano; si siguen sin llegar, avisa a quien mantiene la web para que revise la cola de tareas.

[[action:filament.admin.pages.notification-channel-settings|Abrir las notificaciones a canales]]
