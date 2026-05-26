# Pruebas manuales — Modos de envío CF7 Mailjet

## Pre-requisitos

- [ ] Plugin activo con credenciales Mailjet válidas
- [ ] Lista y/o autorespuesta configuradas según el caso
- [ ] Formulario CF7 habilitado en **Ajustes → CF7 Mailjet**
- [ ] `debug_logs` activado en staging para revisar `error_log`
- [ ] Campos del formulario mapeados correctamente (email, nombre, etc.)

---

## Modo `cf7_mail` (comportamiento legacy)

- [ ] Formulario con modo **CF7 + Mailjet**
- [ ] Con SMTP/`wp_mail` funcionando: mensaje de éxito en front
- [ ] Contacto agregado a Mailjet (si lista habilitada)
- [ ] Autorespuesta recibida (si habilitada)
- [ ] Log: `Delivery mode for form {id}: cf7_mail`
- [ ] Log: `wpcf7_skip_mail applied: no`

---

## Modo `mailjet_only` (VPS / SMTP bloqueado)

- [ ] Formulario con modo **Solo Mailjet**
- [ ] SMTP bloqueado o sin plugin SMTP: mensaje de **éxito** en front (no `mail_sent_ng`)
- [ ] Contacto agregado a Mailjet
- [ ] Autorespuesta recibida por Mailjet
- [ ] Log: `Delivery mode for form {id}: mailjet_only`
- [ ] Log: `wpcf7_skip_mail applied: yes`
- [ ] Log: `Mailjet-only path completed for form ID {id}`
- [ ] **No** se espera correo desde la pestaña Mail de CF7 al administrador

---

## Formulario no habilitado

- [ ] Formulario sin checkbox en ajustes del plugin
- [ ] Envío CF7 normal (sin integración Mailjet)
- [ ] Sin llamadas visibles en logs del plugin (`Form ID X is not enabled`)

---

## Idempotencia (reintentos rápidos)

- [ ] Mismo formulario, mismos datos, doble envío en &lt; 5 minutos
- [ ] Segundo envío: log `Skipped: submission already processed`
- [ ] No se duplican altas en Mailjet ni autorespuestas duplicadas

---

## Rate limit

- [ ] Segundo envío con email distinto después del límite configurado
- [ ] Log de rate limit si aplica

---

## Regresión admin

- [ ] **Probar conexión** en options page sigue funcionando
- [ ] Guardar ajustes conserva modo por formulario

---

## Producción (IONOS)

- [ ] HTTPS saliente a `api.mailjet.com` disponible
- [ ] Formulario de contacto en producción con **Solo Mailjet**
- [ ] Confirmar éxito UX y datos en panel Mailjet
