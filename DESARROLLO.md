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
- email_field (default: your-email)
- name_field (default: your-name)
- phone_field (default: your-phone)
- service_field (default: service)

#### Seguridad
- rate_limit_email_minutes (default: 10)
- rate_limit_ip_minutes (default: 10)
- debug_logs (bool)

---

## 2. LISTENER DE CONTACT FORM 7

Usar el hook:

```php
wpcf7_mail_sent
````

### Flujo:

1. Verificar que el formulario enviado esté habilitado
2. Obtener `WPCF7_Submission`
3. Sanitizar campos
4. Validar email
5. Aplicar rate limit:

   * por email
   * por IP
6. Ejecutar acciones Mailjet:

   * Guardar contacto en lista (si está activo)
   * Enviar autorespuesta (si está activo)

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
* Variables:

  * name
  * email
  * phone
  * service

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

