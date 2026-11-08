---
id: channel-notifications.panel.whatsapp
title: Conectar WhatsApp
summary: Envía los anuncios a números de WhatsApp con la API de WhatsApp Business o a un webhook propio que los reenvíe a grupos.
category: site-settings
audience: panel
order: 63
keywords: [whatsapp, wasap, whatsap, api de whatsapp, whatsapp business, whatsapp cloud, token de acceso, id del número, destinatarios, números, grupo de whatsapp, comunidad, webhook, puente, conectar whatsapp]
related: [channel-notifications.panel.overview, channel-notifications.panel.templates, channel-notifications.panel.test-message]
conditions:
  permissions:
    all: ['channel-notifications:notifications.manage']
---
WhatsApp tiene dos formas de recibir los anuncios. Basta con configurar una, pero puedes usar las dos a la vez.

| Vía | Para qué sirve | Qué necesitas |
|---|---|---|
| **API de WhatsApp Business** | Mandar el anuncio a números concretos, uno a uno | Un **Token de acceso**, el **ID del número de teléfono** y los **Destinatarios** |
| **Webhook externo** | Publicar en grupos y comunidades, que la API no alcanza | La **URL del webhook** de un programa propio que reenvíe los mensajes |

## Conectarlo

1. En el menú lateral, abre **Sistema**, luego **Configuración** y elige **Notificaciones a canales**.
2. En la pestaña **WhatsApp**, activa **Canal activo**.
3. En **Credenciales**, rellena la vía que uses:
   - Para la API: el **Token de acceso** (permanente) y el **ID del número de teléfono**, los dos de tu cuenta de WhatsApp Business, y en **Destinatarios** los números separados por comas, con el código de país, como 34612345678, 34698765432.
   - Para el webhook: la **URL del webhook** de tu programa.
4. En **Tipos de contenido**, marca lo que quieres anunciar: **Eventos**, **Artículos**, **Galerías** o **Encuestas**.
5. Pulsa **Enviar prueba** para comprobarlo.
6. Pulsa **Guardar**.

Con la API, cada destinatario recibe su propio mensaje: con imagen y el texto debajo si el contenido tiene imagen, o solo texto si no. Con el webhook, la web le envía cada anuncio con el texto, la imagen, el título, el enlace y el tipo de contenido, y es tu programa quien lo publica.

## Si algo no sale bien

- **No llega a un número**: revisa que lleva el código de país. Meta solo entrega mensajes de texto libre a quien ha escrito a tu número de empresa en las últimas 24 horas; fuera de ese plazo, el mensaje no llega.
- **La prueba solo llega a una persona**: es normal. La prueba va solo al primer número de **Destinatarios**; los anuncios reales van a todos.
- **«Falta rellenar en el formulario: la URL del webhook, o bien …»**: no hay ni URL del webhook ni todos los datos de la API (destinatarios, token de acceso e ID del número de teléfono). Rellena una de las dos vías completa; no hace falta guardar para probar.

[[action:filament.admin.configuracion.pages.notification-channel-settings|Abrir las notificaciones a canales]]
