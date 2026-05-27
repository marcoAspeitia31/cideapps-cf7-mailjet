# Pruebas manuales — Cideapps CF7 Mailjet

Documento de validación manual del plugin. Incluye pruebas del plan original y las realizadas durante el desarrollo (modos de envío, notificación al negocio, variables Mailjet, metadata, mapeos dinámicos y adjuntos).

**Versión plugin probada:** 1.3.0  
**Última actualización:** mayo 2026

---

## Pre-requisitos

- [x] Plugin activo con credenciales Mailjet válidas (dominio verificado en Mailjet)
- [x] Formulario CF7 habilitado en **Ajustes → CF7 Mailjet**
- [x] Campos core mapeados: email, nombre, teléfono, servicio, mensaje (según el formulario)
- [x] `debug_logs` activado en staging para revisar `wp-content/debug.log` (`[CIDEAPPS-CF7-MAILJET]`)
- [x] En staging, considerar **rate limit en 0** para no confundir bloqueos con bugs

---

## Historial de validación (hilo de desarrollo)

| Área | Resultado | Notas |
|------|-----------|--------|
| Modo `mailjet_only` en VPS (SMTP bloqueado) | OK | CF7 muestra éxito; Mailjet vía API |
| Prueba admin «Probar conexión» (lista) | OK | Corregido: no debe anidar submit con «Guardar» |
| Loader / submit en front | OK | v1.0.1: no deshabilitar botón en `click` (reCAPTCHA) |
| Notificación al negocio (template Mailjet) | OK | Orden: negocio → lista → autorespuesta |
| Notificación al negocio (HTML por defecto) | OK | Tabla con datos del formulario |
| Variable `{{var:message}}` | OK | Requiere campo mapeado `message_field` |
| Metadata CF7 en variables Mailjet | OK | Opt-in; URL, página, fecha, UA, IP parcial, UTM |
| Campos dinámicos (mapeo CF7 → Mailjet) | OK | UI repetible Add/Remove |
| Adjuntos como URLs | OK | Carpeta `uploads/cideapps-cf7-mailjet/` (no Media Library) |
| Plantilla Mailjet mal configurada | Diagnóstico | Variables en plantilla sin enviar en API → bloqueo Mailjet; no era bug del plugin |
| Modo `cf7_mail`, formulario deshabilitado, idempotencia, rate limit | OK | Validado en staging |
| Reply-To notificación (template) | Comportamiento conocido | Reply-To = `from_email`; mejora opcional: email del lead |

---

## 1. Modos de envío

### 1.1 Modo `cf7_mail` (CF7 + Mailjet)

- [x] Formulario con modo **CF7 + Mailjet**
- [x] Con SMTP/`wp_mail` funcionando: mensaje de éxito en front
- [x] Contacto agregado a Mailjet (si lista habilitada)
- [x] Autorespuesta recibida (si habilitada)
- [x] Log: `Delivery mode for form {id}: cf7_mail`
- [x] Log: `wpcf7_skip_mail applied: no`

### 1.2 Modo `mailjet_only` (VPS / SMTP bloqueado)

- [x] Formulario con modo **Solo Mailjet**
- [x] Sin depender de SMTP: mensaje de **éxito** en front (no `mail_sent_ng` / `wpcf7mailfailed`)
- [x] Contacto agregado a Mailjet (si lista habilitada)
- [x] Autorespuesta recibida por Mailjet (si habilitada)
- [x] Log: `Delivery mode for form {id}: mailjet_only`
- [x] Log: `wpcf7_skip_mail applied: yes`
- [x] Log: `Mailjet-only path completed for form ID {id}`
- [ ] **No** se espera correo desde la pestaña Mail de CF7 al administrador (sustituido por notificación Mailjet si está activa)

### 1.3 Formulario no habilitado

- [x] Formulario sin checkbox en ajustes del plugin
- [x] Envío CF7 normal (sin integración Mailjet)
- [x] Log: `Form ID X is not enabled. Skipping.`

---

## 2. Notificación al negocio (solo `mailjet_only`)

- [x] Checkbox **Notificación al Negocio** activo
- [x] Email destino negocio configurado y válido
- [x] Modo **Template ID de Mailjet**: correo recibido con variables correctas
- [x] Modo **HTML por defecto del plugin**: correo recibido con tabla de campos
- [x] Orden respetado: (1) notificación negocio → (2) lista → (3) autorespuesta
- [x] Remitente **From** según `from_email` / `from_name` del plugin (verificado)

### Reply-To en notificación al negocio

Comportamiento actual del plugin:

| Modo notificación | Reply-To en API | ¿Responder llega al cliente? |
|-------------------|-----------------|------------------------------|
| **Template Mailjet** | `from_email` (ej. `contacto@cide-apps.com`) | No — responde al mismo correo del negocio |
| **HTML por defecto** | Email del lead (`$lead_email`) | Sí — responde al prospecto |

- [x] **Template Mailjet:** Reply-To = `from_email` (comportamiento actual; coincide con captura: To y Reply-To iguales al correo del negocio)
- [x] **HTML por defecto:** Reply-To = email del cliente del formulario
- [ ] **Mejora recomendada (opcional):** en modo template, usar email del lead como Reply-To para que «Responder» contacte al prospecto (`cideapps@gmail.com` en el cuerpo)

La autorespuesta al cliente usa Reply-To = `from_email` a propósito (el cliente debe responder al negocio, no a sí mismo).

### Plantilla Mailjet (negocio / autorespuesta)

- [x] Variables core en plantilla: `name`, `email`, `phone`, `service`, `message`, `form_id`
- [x] Si usas metadata: `source_url`, `source_page`, `submitted_at`, `user_agent`, `remote_ip`, `utm_*`
- [x] Si usas mapeos dinámicos: mismas claves que definiste (ej. `company`, `budget`)
- [x] Si usas adjuntos: solo incluir `{{var:...}}` si la variable **siempre** se envía, o usar valor por defecto `{{var:clave:""}}`
- [x] **Incidencia resuelta:** plantilla con variables no enviadas en API → Mailjet bloquea el envío (revisar panel Mailjet y payload `Variables`)

---

## 3. Variables Mailjet — campos core

Configuración en **Ajustes → CF7 Mailjet → pestaña CF7**.

- [x] `{{var:name}}` — campo `name_field`
- [x] `{{var:email}}` — campo `email_field`
- [x] `{{var:phone}}` — campo `phone_field`
- [x] `{{var:service}}` — campo `service_field` (opcional: **Enviar label del servicio**)
- [x] `{{var:message}}` — campo `message_field` (default `your-message`; conserva saltos de línea)
- [ ] `{{var:form_id}}` — ID del formulario CF7

---

## 4. Metadata CF7 (opt-in)

Activar: **Metadata CF7 en Mailjet**.

- [x] `{{var:source_url}}`
- [x] `{{var:source_page}}`
- [x] `{{var:submitted_at}}`
- [x] `{{var:user_agent}}`
- [x] `{{var:remote_ip}}` (IP parcial enmascarada)
- [ ] `{{var:utm_source}}`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content` (probar con URL con query UTM en la página del formulario)

---

## 5. Campos dinámicos (mapeo CF7 → Mailjet)

UI: **Campos Dinámicos (CF7 -> Mailjet)** — filas repetibles (origen → variable).

- [x] Mapeo campo CF7 normal (ej. `your-company` → `company`)
- [x] Guardar configuración y conservar filas al recargar admin
- [x] Valores llegan a plantilla Mailjet (`{{var:company}}`, etc.)
- [ ] Mail-tags especiales en origen:
  - [ ] `[_remote_ip]` → ej. `visitor_ip`
  - [ ] `[_user_agent]`
  - [ ] `[_url]`
  - [ ] `[_date]` / `[_time]`

---

## 6. Adjuntos (URLs, no Media Library)

Activar: **Adjuntos CF7 (URLs en Mailjet)**.

**Importante:** los archivos se guardan en `wp-content/uploads/cideapps-cf7-mailjet/AAAA/MM/`, **no** en Medios de WordPress.

- [x] Envío **con** archivo: archivo visible en carpeta `uploads/cideapps-cf7-mailjet/`
- [x] URL pública abre/descarga el archivo en el navegador
- [x] Auto-mapeo sin filas: `{nombre_campo_file}_url` (ej. `your-cv` → `your_cv_url`)
- [x] Mapeo manual: `campo_file` → `cv_url` en filas repetibles
- [x] `{{var:attachments_all}}` — todas las URLs (una por línea) cuando hay archivos
- [x] Envío **sin** archivo: correos siguen enviándose si la plantilla **no** exige variables de adjunto vacías
- [ ] Notificación **HTML por defecto**: campo file muestra enlace clicable (no hash CF7)
- [x] Plantilla Mailjet: no referenciar `attachments_all` / `*_url` si no hay archivo, salvo `{{var:clave:""}}`

---

## 7. Lista Mailjet y autorespuesta

- [x] **Probar conexión** (formulario dedicado, no al guardar ajustes)
- [x] Alta/actualización de contacto en lista configurada
- [x] Autorespuesta con `template_id` y mismas variables que el envío
- [x] Comportamiento `on_existing_contact`: `update_properties` vs `skip`

---

## 8. Seguridad y estabilidad

### Idempotencia (5 minutos)

- [x] Mismo formulario + mismo email + mismos datos, doble envío en &lt; 5 min
- [x] Segundo envío: log `Skipped: submission already processed`
- [x] No duplicar contacto en lista ni autorespuestas

### Rate limit

- [x] Con límites activos: segundo envío desde misma IP/email dentro del plazo → log de rate limit y sin acciones Mailjet
- [x] Con rate limit en **0** en staging: no falsos positivos en pruebas repetidas

### Regresión admin

- [x] Pestañas Mailjet / Autorespuesta / Lista / CF7 / Seguridad
- [x] Guardar ajustes conserva modo por formulario y mapeos repetibles
- [x] Botón **Probar conexión** no dispara guardado general de ajustes

### Front (UX)

- [x] Loader visible al enviar; botón no bloqueado antes de `wpcf7submitting`
- [x] Estados `wpcf7sent` / `wpcf7failed` restauran el botón

---

## 9. Producción (IONOS / VPS)

- [ ] HTTPS saliente a `api.mailjet.com` disponible
- [ ] Formulario en producción con **Solo Mailjet**
- [ ] Notificación al negocio recibida en inbox real (revisar spam)
- [ ] Panel Mailjet: envíos en *sent* (no *blocked* por variables de plantilla)
- [ ] Adjuntos: URLs con dominio público (no solo `.local`)

---

## 10. Troubleshooting rápido

| Síntoma | Qué revisar |
|---------|-------------|
| CF7 `mail_sent_ng` en VPS | Modo **Solo Mailjet**; SMTP no requerido |
| No llega notificación al negocio | Checkbox activo, email destino, template ID; logs plugin |
| Mailjet *blocked* / sin correo | Variables en plantilla que no van en API; usar `{{var:x:""}}` o quitar |
| `{{var:message}}` vacío | `message_field` = nombre real del campo CF7 |
| Segundo envío “no hace nada” | Idempotencia o rate limit; ver `debug.log` |
| Archivo no en Medios | Normal: ruta `uploads/cideapps-cf7-mailjet/` |
| Prueba lista fallaba al guardar | Usar solo botón **Probar conexión** (formulario separado) |

---

## Referencia rápida de variables

Ver detalle técnico en `DESARROLLO.md` (sección **Variables Mailjet**).

**Siempre (core):** `name`, `email`, `phone`, `service`, `message`, `form_id`  
**Metadata (opt-in):** `source_url`, `source_page`, `submitted_at`, `user_agent`, `remote_ip`, `utm_*`  
**Adjuntos (opt-in):** `{campo}_url`, mapeos custom, `attachments_all`  
**Dinámicos:** según mapeo en admin
