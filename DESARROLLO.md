# OBJETIVO GENERAL

Implementar la lógica completa del plugin **Cideapps CF7 Mailjet** sobre la estructura existente generada con wppb.me.

El plugin debe:
1. Escuchar envíos exitosos de Contact Form 7
2. Enviar un **auto-reply** usando Mailjet (Send API v3.1)
3. Guardar el contacto en una **lista de Mailjet**
4. Contar con **pantalla de configuración en WP Admin**
5. Ser **reutilizable para futuros clientes**
6. Aplicar buenas prácticas de seguridad, validación y rate limiting

---

# CONTEXTO TÉCNICO

- WordPress con Contact Form 7 instalado
- reCAPTCHA v3 ya activo en CF7
- Mailjet configurado con dominio validado
- Plugin base ya creado con wppb.me
- NO usar microservicio externo
- Todo debe ejecutarse server-side en WordPress

---

# ESTRUCTURA EXISTENTE (NO CAMBIAR)

El plugin ya existe en:

```

wp-content/plugins/cideapps-cf7-mailjet

````

Estructura base:

- `includes/`
- `admin/`
- `public/`
- `class-cideapps-cf7-mailjet.php`
- `class-cideapps-cf7-mailjet-admin.php`
- `class-cideapps-cf7-mailjet-public.php`

Trabajar **dentro de esta estructura**, NO rehacer el plugin.

---

# ARQUITECTURA A IMPLEMENTAR

## 1. ADMIN (CONFIGURACIÓN)

Agregar una página en:

**WP Admin → Ajustes → CF7 Mailjet**

### Campos de configuración (guardar en wp_options):

#### Mailjet
- mailjet_public_key
- mailjet_private_key
- mailjet_from_email
- mailjet_from_name

#### Autorespuesta
- enable_autoreply (bool)
- mailjet_template_id

#### Lista
- enable_contact_list (bool)
- mailjet_list_id
- on_existing_contact:
  - update_properties
  - skip

#### CF7
- enabled_form_ids (array de IDs CF7)
- form_mail_modes (array `[ form_id => 'cf7_mail' | 'mailjet_only' ]`, default: `cf7_mail`)
- email_field (default: your-email)
- name_field (default: your-name)
- phone_field (default: your-phone)
- service_field (default: service)
- message_field (default: your-message)
- enable_submission_metadata (bool, default: off) — metadata CF7 en variables Mailjet
- dynamic_mappings (UI con filas repetibles, una por mapeo `origen -> variable_mailjet`)

#### Seguridad
- rate_limit_email_minutes (default: 10)
- rate_limit_ip_minutes (default: 10)
- debug_logs (bool)

---

## 2. LISTENER DE CONTACT FORM 7

### Hooks

```php
wpcf7_skip_mail   // maybe_skip_cf7_mail — solo si modo mailjet_only
wpcf7_mail_sent   // handle_form_submission → process_submission()
```

### Canal de notificación interna por formulario

| Canal (valor interno) | Comportamiento |
|------|----------------|
| `cf7_mail` (default) | **Email nativo de Contact Form 7**: CF7 envía correo nativo (`wp_mail`). Si tiene éxito, corre Mailjet para módulos independientes. |
| `mailjet_only` | **Mailjet API**: `wpcf7_skip_mail` omite correo CF7. CF7 marca `mail_sent` y corre Mailjet vía HTTPS API. |

**Limitación en `mailjet_only`:** la pestaña Mail de CF7 no notifica al administrador.

**Notificación administrativa:** en canal `mailjet_only` se envía por Mailjet (plantilla Mailjet o HTML por defecto; Reply-To = email del lead desde v1.3.1). Desde v1.3.3 ya no depende de checkbox adicional de activación.

### Flujo (`process_submission`):

1. Verificar que el formulario enviado esté habilitado
2. Obtener `WPCF7_Submission`
3. Sanitizar campos
4. Validar email
5. Idempotencia (transient 5 min: form_id + email hash + posted_data hash)
6. Aplicar rate limit (email + IP)
7. Ejecutar acciones Mailjet:
   - **mailjet_only:** (1) notificación negocio → (2) lista → (3) autorespuesta
   - **cf7_mail:** lista + autorespuesta (correo CF7 ya enviado)

**Nota de separación de responsabilidades (v1.3.3):**
- El canal solo define **quién envía la notificación interna**.
- Lista, autorespuesta, metadata y adjuntos permanecen como módulos independientes (según sus propios toggles).

### Variables Mailjet (`{{var:clave}}`)

Clase: `includes/class-cideapps-cf7-mailjet-submission-data.php`

**Core (siempre):**

| Clave | Origen |
|-------|--------|
| `name` | Campo mapeado `name_field` |
| `email` | Campo mapeado `email_field` |
| `phone` | Campo mapeado `phone_field` |
| `service` | Campo mapeado `service_field` (opcional label) |
| `message` | Campo mapeado `message_field` (textarea, conserva saltos de línea) |
| `form_id` | ID del formulario CF7 |

**Metadata CF7 (opt-in, `enable_submission_metadata`):**

| Clave | Origen |
|-------|--------|
| `source_url` | `$submission->get_meta('url')` |
| `source_page` | Título + permalink del post contenedor |
| `submitted_at` | Fecha/hora del envío (formato WP) |
| `user_agent` | Navegador del visitante |
| `remote_ip` | IP enmascarada (último octeto / último grupo IPv6) |
| `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content` | Query string de la URL de origen |

### Etapa 2 (cerrada — validación en `PRUEBAS-MANUALES.md`)

Mejoras opcionales posteriores: `ACTIVIDADES-FUTURAS.md`.

- UI en admin para mapear **campos CF7 adicionales** a claves Mailjet arbitrarias
  - Opción: `cideapps_cf7_mailjet_dynamic_mappings`
  - Formato: una línea por mapeo `origen:variable_mailjet`
  - Ejemplos: `your-company:company`, `your-budget:budget`
- Soporte de mail-tags especiales básicos:
  - `[_remote_ip]`, `[_user_agent]`, `[_url]`, `[_date]`, `[_time]`
  - Ejemplo: `[_remote_ip]:visitor_ip`
- Adjuntos: URLs de archivos subidos (copia a `uploads/cideapps-cf7-mailjet/`, no binarios en API Mailjet)
  - Opción: `cideapps_cf7_mailjet_enable_attachment_urls`
  - Mapeo repetible: `cideapps_cf7_mailjet_attachment_mappings` (`campo_file:variable_mailjet`)
  - Variable global: `attachments_all` (todas las URLs)
  - Auto-mapeo si no hay filas: `{campo}_url`

### Logs (si `debug_logs` activo)

- `Delivery mode for form {id}: {mode}`
- `wpcf7_skip_mail applied: yes/no`
- `Skipped: submission already processed...`

---

- Checklist de pruebas (completado): `PRUEBAS-MANUALES.md`
- Backlog y mejoras opcionales: `ACTIVIDADES-FUTURAS.md`

---

## 3. MAILJET – FUNCIONALIDAD

### 3.1 Guardar contacto en lista

Usar Mailjet Contacts API:

* Crear o actualizar contacto por email
* Agregar a la lista indicada
* Guardar propiedades:

  * name
  * phone
  * service
  * source = "CF7"
  * form_id
  * created_at

Si el contacto ya existe:

* Respetar configuración `on_existing_contact`

---

### 3.2 Enviar autorespuesta

Usar Mailjet Send API v3.1:

* From fijo (configurado)
* To = email del usuario
* Reply-To = From Email
* TemplateID
* Variables: ver tabla en sección **Variables Mailjet** (core + metadata opcional)

NO usar el email del usuario como From.

---

## 4. RATE LIMITING

Implementar rate limiting usando transients:

* Email:

  * key: `cf7_mj_email_{hash}`
* IP:

  * key: `cf7_mj_ip_{hash}`

Si se excede:

* NO enviar autorespuesta
* NO guardar en lista
* Salir silenciosamente

---

## 5. LOGS

Si `debug_logs` está activo:

* Usar `error_log()`
* Prefijo: `[CIDEAPPS-CF7-MAILJET]`

Loggear:

* Envío exitoso
* Error Mailjet API
* Rate limit activado
* Datos inválidos

---

## 6. BUENAS PRÁCTICAS

* No exponer API keys en JS
* No usar `wp_mail()`
* Validar capacidades `manage_options` en admin
* Escapar todo output en admin
* Sanitizar todo input
* Código compatible con PHP 8.x

---

# ENTREGABLE ESPERADO

El plugin debe:

* Funcionar sin modificar CF7
* Ser configurable desde WP Admin
* Ser reutilizable en otros sitios
* Permitir usar distintos formularios
* Enviar auto-reply con Mailjet
* Guardar contactos en listas Mailjet
* Tener código limpio y mantenible

---

# IMPORTANTE

NO crear microservicios.
NO modificar el theme.
NO hardcodear API keys.
NO usar funciones deprecated.

Implementar todo dentro del plugin existente.

