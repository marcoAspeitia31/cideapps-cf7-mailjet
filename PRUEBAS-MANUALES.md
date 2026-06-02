# Pruebas manuales — Cideapps CF7 Mailjet

Documento de validación manual del plugin. Incluye pruebas del plan original y las realizadas durante el desarrollo (modos de envío, notificación al negocio, variables Mailjet, metadata, mapeos dinámicos y adjuntos).

**Versión plugin probada:** 1.3.4 (`v1.3.4` — selector campos CF7)  
**Última actualización:** junio 2026  
**Estado v1.3.x:** **completada** (staging + producción). Validado: refactor canal (sección 14), selector campos CF7 (sección 15). Tag base post-v1.3.4: `5397d628d8b1508435295d2263688218b0aa305b`.

**Siguiente objetivo de producto:** `v1.4.0` = **rediseño UX/Admin**. Roadmap: `ACTIVIDADES-FUTURAS.md`; blueprint: `docs/UX-NAVIGATION-BLUEPRINT-v1.4.md`. Checklist QA v1.4: **sección 16** (Epic **B** validado; **Epic C1** implementado — validar en staging; **C2+** pendiente).

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
| Reply-To notificación (template) | OK (v1.3.1) | Reply-To = email del lead |
| form_id, UTM, HTML file links, sin correo CF7 en mailjet_only | OK | Validado en staging |
| Mail-tags especiales (`[_remote_ip]`, etc.) | OK | Mapeo dinámico + plantilla Mailjet |
| Producción IONOS VPS (SMTP 25 bloqueado) | OK | Solo canal **Mailjet API**; sin correo CF7 nativo |
| Producción cPanel | OK | **Email nativo CF7** y **Mailjet API** |
| Fase 1 — Limpieza adjuntos (cron retención) | OK | Commit `817de74`; staging local mayo 2026 (§11) |
| Fase 2 — Uninstall limpio y seguro | OK | Commit de código Fase 2; validado en servidor de pruebas mayo 2026 (§12) |
| Fase 3 — Guía despliegue + CHANGELOG | Docs | `docs/GUIA-DESPLIEGUE-CLIENTE.md`, `CHANGELOG.md` (§13) |
| Refactor canal notificación interna | OK | UI + runtime simplificados; validado manualmente (§14) |
| v1.3.4 — Selector visual campos CF7 | OK | PHP-only; `scan_form_tags`; validado manualmente (sección 15) |
| v1.4.0 — Epic B1 shell admin (3 tabs) | OK | Validado staging junio 2026; sección 16 |
| v1.4.0 — Epic B2 tab Mailjet aislado | OK | Validado staging junio 2026; sección 16 |
| v1.4.0 — Epic B3 tab Seguridad aislado | OK | QA estático código junio 2026; confirmar cron en staging |
| v1.4.0 — Rediseño UX/Admin (restante) | En curso | Epic C–G pendientes; `ACTIVIDADES-FUTURAS.md` |

---

## 1. Modos de envío

### 1.1 Canal `cf7_mail` (Email nativo de Contact Form 7)

- [x] Formulario con canal **Email nativo de Contact Form 7**
- [x] Con SMTP/`wp_mail` funcionando: mensaje de éxito en front
- [x] Contacto agregado a Mailjet (si lista habilitada)
- [x] Autorespuesta recibida (si habilitada)
- [x] Log: `Delivery mode for form {id}: cf7_mail`
- [x] Log: `wpcf7_skip_mail applied: no`

### 1.2 Canal `mailjet_only` (Mailjet API)

- [x] Formulario con canal **Mailjet API**
- [x] Sin depender de SMTP: mensaje de **éxito** en front (no `mail_sent_ng` / `wpcf7mailfailed`)
- [x] Contacto agregado a Mailjet (si lista habilitada)
- [x] Autorespuesta recibida por Mailjet (si habilitada)
- [x] Log: `Delivery mode for form {id}: mailjet_only`
- [x] Log: `wpcf7_skip_mail applied: yes`
- [x] Log: `Mailjet-only path completed for form ID {id}`
- [x] **No** se espera correo desde la pestaña Mail de CF7 al administrador (sustituido por notificación Mailjet si está activa)

### 1.3 Formulario no habilitado

- [x] Formulario sin checkbox en ajustes del plugin
- [x] Envío CF7 normal (sin integración Mailjet)
- [x] Log: `Form ID X is not enabled. Skipping.`

---

## 2. Notificación al negocio (canal `mailjet_only`)

- [x] Email destino negocio configurado y válido
- [x] Modo **Template ID de Mailjet**: correo recibido con variables correctas
- [x] Modo **HTML por defecto del plugin**: correo recibido con tabla de campos
- [x] Orden respetado: (1) notificación negocio → (2) lista → (3) autorespuesta
- [x] Remitente **From** según `from_email` / `from_name` del plugin (verificado)

### Reply-To en notificación al negocio

Comportamiento actual del plugin:

| Modo notificación | Reply-To en API | ¿Responder llega al cliente? |
|-------------------|-----------------|------------------------------|
| **Template Mailjet** | Email del lead (`$lead_email`) | Sí — responde al prospecto |
| **HTML por defecto** | Email del lead (`$lead_email`) | Sí — responde al prospecto |

- [x] **Template Mailjet:** Reply-To = email del cliente del formulario (desde v1.3.1)
- [x] **HTML por defecto:** Reply-To = email del cliente del formulario
- [x] Revalidar en inbox: To = negocio, Reply-To = email del prospecto (ej. `cideapps@gmail.com`)

La autorespuesta al cliente sigue usando Reply-To = `from_email` a propósito (el cliente debe responder al negocio, no a sí mismo).

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
- [x] `{{var:form_id}}` — ID del formulario CF7

---

## 4. Metadata CF7 (opt-in)

Activar: **Metadata CF7 en Mailjet**.

- [x] `{{var:source_url}}`
- [x] `{{var:source_page}}`
- [x] `{{var:submitted_at}}`
- [x] `{{var:user_agent}}`
- [x] `{{var:remote_ip}}` (IP parcial enmascarada)
- [x] `{{var:utm_source}}`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content` (probar con URL con query UTM en la página del formulario)

---

## 5. Campos dinámicos (mapeo CF7 → Mailjet)

UI: **Ajustes → CF7 Mailjet → pestaña CF7 → Campos Dinámicos (CF7 -> Mailjet)** — filas repetibles (origen → variable).

- [x] Mapeo campo CF7 normal (ej. `your-company` → `company`)
- [x] Guardar configuración y conservar filas al recargar admin
- [x] Valores llegan a plantilla Mailjet (`{{var:company}}`, etc.)

### Mail-tags especiales CF7 (cómo configurarlos)

Los mail-tags **no** son campos del formulario. Son datos que CF7 guarda en el envío (IP, URL, fecha, etc.). Hay que **mapearlos** en el plugin igual que un campo normal, pero en **origen** escribes el tag con corchetes.

**Paso 1 — Plugin (WordPress)**  
En **Campos Dinámicos**, pulsa **Add** y crea una fila por cada dato. Ejemplo:

| Origen (campo izquierdo) | Variable Mailjet (campo derecho) |
|--------------------------|----------------------------------|
| `[_remote_ip]` | `visitor_ip` |
| `[_user_agent]` | `browser` |
| `[_url]` | `landing_url` |
| `[_date]` | `submit_date` |
| `[_time]` | `submit_time` |

- El **origen** debe coincidir exactamente (incluye `[_` y `]`).
- La **variable Mailjet** solo admite letras minúsculas, números y `_` (el plugin la normaliza al guardar).
- Guarda con **Guardar Configuración**.

**Paso 2 — Plantilla Mailjet**  
En el editor de la plantilla (negocio o autorespuesta), usa la sintaxis de Mailjet con el nombre de la **columna derecha**:

```text
IP visitante: {{var:visitor_ip}}
Navegador: {{var:browser}}
Página: {{var:landing_url}}
Fecha: {{var:submit_date}}
Hora: {{var:submit_time}}
```

**Paso 3 — Probar**  
1. Envía el formulario desde el front (idealmente desde una URL con parámetros si también usas UTM vía metadata).  
2. Revisa el correo o el preview de Mailjet: deben verse los valores.  
3. Si una variable sale vacía, comprueba el tag en origen y que exista en el envío (con `debug_logs` activo no suele hace falta más).

**Nota:** Si ya tienes activo **Metadata CF7 en Mailjet**, parte de esto se solapa (`source_url` ≈ `[_url]`, `user_agent` ≈ `[_user_agent]`, `remote_ip` ≈ IP enmascarada vs `[_remote_ip]` completa). Usa metadata **o** mail-tags mapeados, no dupliques lo mismo con nombres distintos salvo que lo necesites en la plantilla.

**Checklist mail-tags:**

- [x] `[_remote_ip]` → ej. `visitor_ip` → `{{var:visitor_ip}}`
- [x] `[_user_agent]` → ej. `browser` → `{{var:browser}}`
- [x] `[_url]` → ej. `landing_url` → `{{var:landing_url}}`
- [x] `[_date]` / `[_time]` → ej. `submit_date` / `submit_time`

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
- [x] Notificación **HTML por defecto**: campo file muestra enlace clicable (no hash CF7)
- [x] Plantilla Mailjet: no referenciar `attachments_all` / `*_url` si no hay archivo, salvo `{{var:clave:""}}`

### Retención y limpieza automática (Fase 1 — validado)

Relacionado con §11. Requiere commit `feat(attachment-retention): …` (`817de74`).

- [x] Campo **Días de retención de adjuntos** en admin (pestaña CF7)
- [x] Valor `0`: no borra archivos; cron desprogramado tras guardar
- [x] Valor `30`: cron diario `cideapps_cf7_mailjet_upload_cleanup` visible en `wp cron event list`

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

## 9. Producción (IONOS / VPS / cPanel)

### VPS IONOS (puerto SMTP 25 bloqueado)

Escenario típico: el servidor **no** permite envío SMTP saliente; CF7 nativo fallaría sin relay. Usar canal **Mailjet API**.

- [x] HTTPS saliente a `api.mailjet.com` disponible
- [x] Formulario en producción con canal **Mailjet API**
- [x] Mensaje de éxito en front sin `mail_sent_ng`
- [x] Notificación al negocio recibida en inbox real
- [x] Autorespuesta y lista Mailjet operativas
- [x] Panel Mailjet: envíos en *sent* (no *blocked* por variables de plantilla)
- [x] Adjuntos: URLs con dominio público (no solo `.local`)

### cPanel (SMTP / `wp_mail` disponible)

- [x] Canal **Email nativo de Contact Form 7**: correo CF7 + acciones Mailjet
- [x] Canal **Mailjet API**: mismo comportamiento que en staging

---

## 10. Troubleshooting rápido

| Síntoma | Qué revisar |
|---------|-------------|
| CF7 `mail_sent_ng` en VPS | Canal **Mailjet API**; SMTP no requerido |
| No llega notificación al negocio | Checkbox activo, email destino, template ID; logs plugin |
| Mailjet *blocked* / sin correo | Variables en plantilla que no van en API; usar `{{var:x:""}}` o quitar |
| `{{var:message}}` vacío | `message_field` = nombre real del campo CF7 |
| Segundo envío “no hace nada” | Idempotencia o rate limit; ver `debug.log` |
| Archivo no en Medios | Normal: ruta `uploads/cideapps-cf7-mailjet/` |
| Prueba lista fallaba al guardar | Usar solo botón **Probar conexión** (formulario separado) |
| Cron cleanup `errors=2`, `deleted=0` | Ejecutar WP-CLI como `www-data`: `sudo -u www-data wp cron event run cideapps_cf7_mailjet_upload_cleanup` |

---

## 11. Sprint mantenimiento — Fase 1: limpieza de adjuntos (cron)

**Feature:** retención configurable y borrado automático en `uploads/cideapps-cf7-mailjet/`  
**Commit:** `817de74` — `feat(attachment-retention): implement attachment retention settings and cron management`  
**Entorno:** staging local (`cideapps.local`), mayo 2026  
**Resultado:** **validación exitosa** — no continuar Fase 2 hasta revisar este apartado.

### Admin

- [x] **Ajustes → CF7 Mailjet → CF7:** campo **Días de retención de adjuntos** visible
- [x] Descripción clara: `0` = desactivado; recomendado `30`
- [x] Guardar retención **30** persiste option `cideapps_cf7_mailjet_attachment_retention_days`
- [x] Guardar retención **0** desactiva limpieza (sin borrar archivos existentes por sí solo)

### WP-Cron

- [x] Con retención **30**: `wp cron event list` muestra `cideapps_cf7_mailjet_upload_cleanup` (recurrencia `1 day`)
- [x] Con retención **0** tras guardar: evento de cleanup no programado (o eliminado tras `reschedule_cron`)

### Limpieza de archivos (prueba manual)

Preparación:

- [x] Archivos de prueba con antigüedad &gt; 30 días (`touch -d '40 days ago'`), dueño `www-data:www-data`
- [x] Rutas bajo `wp-content/uploads/cideapps-cf7-mailjet/` (ej. `archivo.pdf`, `2026/04/test.pdf`)

Ejecución:

```bash
sudo -u www-data wp cron event run cideapps_cf7_mailjet_upload_cleanup
```

Resultados observados:

- [x] Log: `Upload cleanup finished (retention 30 days): deleted=2, skipped=6, errors=0`
- [x] `archivo.pdf` y `2026/04/test.pdf` **eliminados**
- [x] `.htaccess` en `cideapps-cf7-mailjet/` **conservado**
- [x] Contenido en `uploads/2026/05/` (fuera de la carpeta del plugin) **sin cambios**

### Nota QA — permisos WP-CLI

- [x] `wp cron event run …` **sin** `sudo -u www-data` sobre archivos `www-data`: `deleted=0`, `errors=2` (permisos; **no** bug de lógica)
- [x] Mismo comando **como `www-data`**: borrado correcto (equivalente al usuario del servidor web en producción)

### Regresión (no tocado en Fase 1)

- [x] Envío CF7 con adjuntos (copia a `uploads/cideapps-cf7-mailjet/`) — sin cambios en handler/API en este commit
- [x] Modos `cf7_mail` y `mailjet_only` — sin cambios en este commit

**Fase 2 completada:** uninstall limpio validado (ver §12).

---

## 12. Sprint mantenimiento — Fase 2: uninstall limpio

**Feature:** desinstalación segura (options, transients y cron del plugin), con borrado opcional de uploads mediante checkbox opt-in.  
**Commit (código):** `feat(uninstall): clean plugin options on uninstall`  
**Entorno:** servidor de pruebas independiente, mayo 2026  
**Resultado:** **validación exitosa**.

### Escenario probado (con opt-in de uploads activado)

Estado previo:

- [x] Option presente: `wp option get cideapps_cf7_mailjet_public_key` devuelve valor
- [x] Cron presente: `wp cron event list | grep cideapps` muestra `cideapps_cf7_mailjet_upload_cleanup`
- [x] Carpeta de plugin en uploads presente: `wp-content/uploads/cideapps-cf7-mailjet/`

Resultado tras desinstalar:

- [x] Option eliminada: `Error: Could not get ... option`
- [x] Cron eliminado: `wp cron event list | grep cideapps` sin resultados
- [x] Uploads eliminados por opt-in: `ls .../uploads/cideapps-cf7-mailjet/` → `No such file or directory`

### Reglas validadas de seguridad

- [x] `uninstall.php` protegido con `if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }`
- [x] Whitelist explícita de options (`cideapps_cf7_mailjet_*`) en clase uninstall
- [x] Transients solo por prefijos exactos (`cf7_mj_email_`, `cf7_mj_ip_`, `cf7_mj_proc_`)
- [x] Borrado de uploads limitado al directorio `uploads/cideapps-cf7-mailjet/`
- [x] Borrado de uploads por defecto OFF; solo se ejecuta con checkbox activado

### Nota operativa QA

- [x] Esta validación se ejecutó en sitio de pruebas separado para evitar borrar el plugin del entorno de desarrollo local.

---

## 13. Sprint mantenimiento — Fase 3: documentación de despliegue

**Alcance:** solo documentación (sin cambios PHP).  
**Entregables:** `docs/GUIA-DESPLIEGUE-CLIENTE.md`, `CHANGELOG.md`.  
**Release de referencia:** `v1.3.2`.

Este apartado **no sustituye** las pruebas funcionales de §11–12. El checklist operativo para instalar en un sitio cliente está en la guía de despliegue.

### Contenido de la guía (revisión editorial)

- [x] Requisitos previos (WordPress, CF7, Mailjet, DNS, accesos).
- [x] DNS: SPF (único registro), DKIM, DMARC.
- [x] Mailjet: API Keys, sender, lista, plantillas, variables.
- [x] Variables mínimas y opcionales + fallbacks.
- [x] Modos `cf7_mail` y `mailjet_only` (incl. IONOS/VPS).
- [x] Pasos de configuración WordPress (retención adjuntos, uninstall opt-in).
- [x] Checklist de despliegue pre-producción.
- [x] Troubleshooting común.

### Dónde probar funcionalidad

| Necesidad | Documento |
|-----------|-------------|
| QA cron retención / uninstall | §11 y §12 de este archivo |
| Instalar en cliente nuevo | `docs/GUIA-DESPLIEGUE-CLIENTE.md` §9 |
| Cambios por versión | `CHANGELOG.md` |

**Roadmap siguiente:** `v1.4.0` rediseño UX/Admin (`ACTIVIDADES-FUTURAS.md`, Epics B–G). QA en sección 16. Backlog técnico (mail-tags extra, export JSON, etc.) congelado hasta tag `v1.4.0`.

---

## 14. Refactor prioritario — canal de notificación interna

**Alcance:** simplificar UI y runtime del canal de notificación interna (sin tocar lista/autorespuesta/metadata/adjuntos).  
**Resultado:** **validación exitosa**.

### Cambios funcionales validados

- [x] UI renombrada: `Modo de envío` → `Canal de notificación interna`
- [x] Etiquetas:
  - `CF7 + Mailjet` → `Email nativo de Contact Form 7`
  - `Solo Mailjet` → `Mailjet API`
- [x] Checkbox eliminado de UI: `Notificación al Negocio (Solo Mailjet)`
- [x] En runtime, con canal `mailjet_only`, la notificación interna por Mailjet ya no depende de `owner_notify_enabled`
- [x] Campos de configuración mantenidos:
  - `Email destino negocio`
  - `Modo de notificación negocio`
  - `Template ID negocio`
  - `Asunto (HTML por defecto)`

### Validaciones mínimas en `mailjet_only`

- [x] Email destino faltante/inválido → warning claro
- [x] Modo template sin template ID → warning claro
- [x] Modo html_default sin asunto → warning claro
- [x] Envío válido con datos completos → notificación al negocio enviada

### Regresión controlada

- [x] Canal `cf7_mail` no dispara notificación interna por Mailjet
- [x] Lista, autorespuesta, metadata y adjuntos mantienen comportamiento

---

## 15. v1.3.4 — Selector visual de campos CF7

**Release:** `v1.3.4` (distinto de `v1.3.3`, que corresponde al refactor del canal de notificación interna — sección 14).  
**Alcance:** admin únicamente; lectura de tags CF7 vía `scan_form_tags()`; mismas options globales de mapping. Sin cambios en handler de envío, Mailjet API ni estructura de option names.  
**Commit (código):** `feat(admin): add CF7 field selector for Mailjet mappings`  
**Commit (docs):** `docs: document v1.3.4 CF7 field selector QA`  
**Entorno:** staging / desarrollo local, junio 2026  
**Resultado:** **validación exitosa**.

### Checklist QA validado

- [x] Los dropdowns muestran correctamente los campos reales detectados desde CF7
- [x] La fila **Campos CF7 detectados** refleja la fuente y el conteo correcto de nombres únicos
- [x] Al cambiar formularios habilitados y guardar, la lista se actualiza (recarga de página, sin JS)
- [x] Los valores previamente guardados que ya no existen en CF7 se conservan y siguen apareciendo seleccionados (`valor guardado`)
- [x] No se detectaron regresiones en autorespuesta, lista, metadata, adjuntos ni runtime

### Detalle adicional (admin)

- [x] Con CF7 activo, los cinco mappings principales usan `<select>`; sin CF7, inputs de texto legacy
- [x] Cada opción del dropdown muestra `nombre (tipo)` según el tag CF7
- [x] Sin formularios habilitados: se listan campos de todos los formularios CF7 del sitio
- [x] Opción **— Sin asignar —** permite vaciar un mapping
- [x] Mapeos dinámicos y canal por formulario sin cambios en esta versión

---

## 16. v1.4.0 — Rediseño UX/Admin (checklist pendiente)

**Release objetivo:** `v1.4.0`  
**Alcance:** reorganización de la pantalla de administración. **Sin cambiar runtime** en las fases iniciales (Epics B–F): mismos `wp_options`, mismo comportamiento de envío.  
**Roadmap:** `ACTIVIDADES-FUTURAS.md` (Epics A–G).  
**Reglas de trabajo:** `REGLAS-TRABAJO.md` (QA obligatorio antes de commit; un epic ≈ una fase).  
**Estado de este checklist:** pendiente — marcar ítems al validar cada fase en staging.

### Criterio de cierre v1.4.0

- [ ] Epics B–F implementados y validados según subsecciones siguientes
- [ ] Regresión global (secciones 1–15) sin fallos nuevos atribuibles al rediseño admin
- [ ] `CHANGELOG.md` actualizado y tag `v1.4.0` creado
- [ ] `docs/GUIA-DESPLIEGUE-CLIENTE.md` refleja flujo por formulario (Epic G3)

### Epic B — Navegación (3 tabs)

**B1 — Shell Mailjet | Formularios | Seguridad** — **validado** (staging, junio 2026)

- [x] Solo existen tres tabs principales (Mailjet, Formularios, Seguridad); tabs Autorespuesta/Lista/CF7 eliminados como navegación raíz
- [x] Navegación por query args (`tab`, `form_id`); tab por defecto Formularios
- [x] Contenedores `#forms-list` y `#form-detail`; notificación interna solo en detalle
- [x] Global autorespuesta/lista en `<details>`; retención en tab Seguridad (markup)
- [x] POST handler sin cambios (revisión estática código)
- [x] Guardar configuración desde cada tab no borra datos guardados en otros tabs
- [x] Tras actualizar plugin en sitio con configuración existente, los valores previos siguen presentes

**B2 — Tab Mailjet** — **validado** (staging, junio 2026)

- [x] Tab Mailjet muestra únicamente API Key, Secret Key, From Email y From Name
- [x] Subsecciones **Credenciales** y **Remitente** visibles y agrupadas
- [x] Copy del remitente aclara autorespuestas y envíos vía Mailjet API
- [x] La prueba de lista **no** está en Mailjet; sigue en Formularios → Configuración global → Lista Mailjet
- [x] Copy y enlace orientativo desde Mailjet hacia Formularios (prueba de lista / List ID)
- [x] Guardar desde Mailjet persiste API keys y From; otros tabs sin pérdida de datos
- [x] No muestra formularios, mappings, metadata, rate limit, autorespuesta ni lista en `#mailjet-settings`

**B3 — Tab Seguridad** — **validado** (staging, junio 2026)

- [x] Tab Seguridad muestra únicamente límites de envío, depuración, retención de adjuntos y desinstalación
- [x] Subsecciones **Límites de envío**, **Depuración**, **Adjuntos**, **Desinstalación**
- [x] Copy de cron WP-Cron al guardar retención; uninstall vs desactivar plugin explícito
- [x] Retención **no** editable en Formularios (solo enlace a tab Seguridad en sección Adjuntos)
- [x] POST handler y `reschedule_cron()` sin cambios; mismos `name` de inputs de seguridad
- [x] `#security-settings` sin credenciales, formularios habilitados, mappings, autorespuesta ni lista
- [x] Guardar desde Seguridad persiste valores y cron de retención (sección 11)

### Epic C — Tab Formularios (lista)

**C1 — Tabla simple (alcance reducido)**

- [x] Tab **Formularios** muestra tabla `wp-list-table` (sin acordeón ni checkboxes visibles por formulario)
- [x] Se listan todos los formularios CF7 detectados (título + ID en columna Formulario)
- [x] Columna **Estado** (Activo/Inactivo) coincide con `cideapps_cf7_mailjet_enabled_form_ids`
- [x] Columna **Canal** coincide con `cideapps_cf7_mailjet_form_mail_modes` (`cf7_mail` → texto nativo CF7; `mailjet_only` → Mailjet API)
- [x] **Editar** enlaza a `?page=cideapps-cf7-mailjet&tab=forms&form_id={id}` y abre `#form-detail` del formulario correcto
- [x] **Volver a formularios** desde detalle regresa a la lista sin error
- [x] Guardar configuración desde la lista **no** vacía `enabled_form_ids` ni cambia canales (bloque oculto de preservación)
- [x] Configuración global colapsada y mappings globales siguen visibles debajo de la tabla
- [x] **No** en C1: Restablecer, badges herencia, columna mappings, filtros, búsqueda, métricas

**Historial de validación C1**

- [x] **Staging (junio 2026):** validación integral completada por producto/QA; C1 se cierra como entregado.
- [x] Evidencia funcional confirmada: tabla, columnas (Formulario/Estado/Canal), navegación Editar/Volver y preservación al guardar.
- [x] Alcance respetado: sin Restablecer, sin badges de herencia, sin indicadores de mappings, sin filtros, sin búsqueda, sin métricas.

**C2 — Restablecer por formulario**

- [x] Tabla Formularios muestra acción **Restablecer** por fila junto a **Editar**
- [x] Acción protegida con nonce propio y validación `manage_options`
- [x] Confirmación UX antes de ejecutar el restablecimiento
- [x] Restablecer elimina solo `cideapps_cf7_mailjet_form_settings[form_id]`
- [x] Restablecer elimina solo ese `form_id` de `cideapps_cf7_mailjet_enabled_form_ids`
- [x] Restablecer elimina solo `cideapps_cf7_mailjet_form_mail_modes[form_id]`
- [x] Redirección posterior a `?page=cideapps-cf7-mailjet&tab=forms`
- [x] Notice visible de éxito/error tras redirección
- [x] Restablecer no muestra warning `Cannot modify header information`
- [x] Bloque hidden de preservación no reinyecta `form_mail_modes[form_id]` tras reset
- [x] No modifica credenciales/globales/seguridad/logs/retención ni otros formularios

**Historial de validación C2**

- [x] **QA técnico local (junio 2026):** revisión de flujo, nonce/capability, limpieza de opciones por `form_id`, redirección y notices.
- [x] **Bugfix C2 (junio 2026):** procesamiento de reset movido a `admin_init` (antes de output) para evitar warnings de headers.
- [x] **Validación staging (junio 2026):** restablecimiento probado en múltiples formularios; sin warnings de headers, con redirección y notices correctos.

**Epic C — Cierre**

- [x] Epic C (**C1 + C2**) completado y validado en staging (junio 2026).

### Epic D — Vista detalle por formulario

**D1 — Navegación**

- [x] Vista detalle accesible desde la tabla y vuelve a la lista sin error
- [x] El título o encabezado identifica el formulario CF7 editado

**D2 — General**

- [ ] Activar/desactivar integración para el formulario equivale al comportamiento actual de formularios habilitados
- [ ] Canal de notificación interna por formulario se guarda y aplica igual que en v1.3.4

**D3 — Campos y variables**

- [ ] Mappings per-form (cinco slots + heredar global) funcionan con `Cf7_Field_Selector`
- [ ] Tags mostrados corresponden al `form_id` del detalle, no a otro formulario
- [ ] Valores `(valor guardado)` se conservan si el tag ya no existe en el formulario

**D4 — Notificación interna (UI)**

- [ ] Campos visibles: email destino negocio, modo template/HTML, template ID, asunto
- [ ] Envío en canal `mailjet_only` se comporta igual que v1.3.4 (validar warnings de sección 14)
- [ ] Canal `cf7_mail` no envía notificación interna por Mailjet API

**D5 — Autorespuesta y lista (UI)**

- [ ] Opciones de autorespuesta y lista accesibles desde el detalle (o aviso explícito si siguen globales en v1.4.0)
- [ ] Autorespuesta y alta en lista siguen funcionando en envío real (regresión secciones 3–4)

**D6 — Metadata y adjuntos (UI)**

- [ ] Toggles/metadata y adjuntos accesibles desde el detalle (o sección equivalente)
- [ ] Metadata y URLs de adjuntos en Mailjet sin regresión (secciones 7–8)

### Epic E — Mapeo centrado en CF7 → Mailjet

- [ ] La sección de campos prioriza nombres reales de tags CF7 (`your-name`, etc.) sobre labels técnicos globales
- [ ] Mappings dinámicos repetibles editables en el mismo contexto del formulario (cuando Epic E2 esté implementado)
- [ ] Variables Mailjet usadas en plantillas siguen resolviéndose en envío de prueba

### Epic F — Menor dependencia visual de globals

- [ ] Mappings globales no son la vista principal; mensaje o UI invita a configurar por formulario
- [ ] Lista o detalle indica **Personalizado** vs **Hereda global** cuando aplique
- [ ] Formulario que hereda global se comporta igual que v1.3.4 sin reconfigurar

### Regresión obligatoria post-rediseño (muestreo)

Ejecutar al menos un formulario en staging con canal **Mailjet API** y otro con **Email nativo CF7** si el entorno lo permite:

- [ ] Autorespuesta (sección 3)
- [ ] Lista Mailjet (sección 4)
- [ ] Notificación al negocio template y HTML (secciones 5–6)
- [ ] Metadata opt-in (sección 7)
- [ ] Adjuntos por URL (sección 8)
- [ ] Mapeos dinámicos (sección 9)
- [ ] Rate limit no confunde QA (pre-requisitos)

### Notas para v1.4.1+ (fuera de cierre v1.4.0)

Persistencia per-form de template, lista, autorespuesta y flags metadata/adjuntos requiere checklist aparte cuando exista en `ACTIVIDADES-FUTURAS.md` (Epic P1–P5). No bloquea el tag `v1.4.0` si la UI deja claro qué opciones siguen siendo globales.

---

## Referencia rápida de variables

Ver detalle técnico en `DESARROLLO.md` (sección **Variables Mailjet**).

**Siempre (core):** `name`, `email`, `phone`, `service`, `message`, `form_id`  
**Metadata (opt-in):** `source_url`, `source_page`, `submitted_at`, `user_agent`, `remote_ip`, `utm_*`  
**Adjuntos (opt-in):** `{campo}_url`, mapeos custom, `attachments_all`  
**Dinámicos:** según mapeo en admin
