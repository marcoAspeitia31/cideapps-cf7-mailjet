# Guía de despliegue por cliente — Cideapps CF7 Mailjet

Documento operativo para instalar y configurar el plugin en sitios WordPress de clientes.  
Versión de referencia: **1.3.2** (tag `v1.3.2`).

**Documentación relacionada**

| Documento | Uso |
|-----------|-----|
| `DESARROLLO.md` | Arquitectura técnica interna |
| `PRUEBAS-MANUALES.md` | QA funcional (Fases 1 y 2 del sprint) |
| `CHANGELOG.md` | Historial de versiones |

---

## 1. Requisitos previos

Antes de instalar el plugin, confirmar:

- [ ] WordPress instalado y actualizado (5.8+ recomendado).
- [ ] **Contact Form 7** activo y al menos un formulario de contacto creado.
- [ ] Cuenta **Mailjet** activa con plan que permita API y envíos transaccionales.
- [ ] **Dominio del remitente** autorizado/verificado en Mailjet (Senders & Domains).
- [ ] Acceso al **DNS** del dominio (SPF, DKIM, DMARC).
- [ ] Acceso **administrador** a WordPress.
- [ ] Acceso al panel Mailjet para: API Keys, plantillas, listas y estadísticas de envío.
- [ ] HTTPS saliente desde el servidor hacia `https://api.mailjet.com` (firewall/VPS).

---

## 2. Configuración DNS

La entregabilidad depende del dominio del remitente (`From Email`). Los valores exactos los indica Mailjet en **Senders & Domains → Authenticate**.

### SPF

Autoriza a Mailjet (y, si aplica, al hosting) a enviar en nombre del dominio.

Ejemplo (adaptar según proveedor):

```txt
v=spf1 include:spf.mailjet.com a mx include:relay.mailchannels.net ~all
```

**Importante:** solo debe existir **un registro SPF** por dominio. Si ya hay uno, **fusionar** includes en un solo TXT; no crear un segundo SPF.

### DKIM

Mailjet genera el registro DKIM (CNAME o TXT) en su panel. Copiarlo tal cual en DNS y esperar propagación (minutos a 48 h).

### DMARC

Configuración inicial recomendada (modo observación):

```txt
v=DMARC1; p=none;
```

Con informes opcionales:

```txt
v=DMARC1; p=none; rua=mailto:contacto@dominio.com;
```

Subir gradualmente a `quarantine` o `reject` cuando SPF y DKIM estén estables.

---

## 3. Configuración Mailjet

### API Keys

1. Mailjet → **Account Settings** → **API Keys**.
2. Crear o usar par existente: **API Key** (Public) y **Secret Key** (Private).
3. En WordPress: **Ajustes → CF7 Mailjet → Mailjet** → pegar Public Key y Private Key.

No compartir las claves por email ni versionarlas en repositorios.

### Sender verificado

1. **Senders & Domains** → verificar dominio o dirección.
2. En el plugin: **From Email** y **From Name** deben coincidir con un remitente autorizado.

### Lista de contactos

1. Crear lista en Mailjet si se usará alta de leads.
2. Anotar **List ID**.
3. En el plugin: activar **Lista de contactos** e indicar el ID.

### Plantillas

| Plantilla | Uso en plugin |
|-----------|----------------|
| **Autorespuesta** | Template ID en pestaña Autorespuesta |
| **Notificación interna** | Template ID en notificación al negocio (modo `mailjet_only`) |

Crear plantillas en el idioma correcto (**Template Language** activo en API).

### Variables en plantillas

- **Requeridas (core):** ver §4.
- **Opcionales:** ver §5.
- Usar **fallbacks** (§6) para evitar bloqueos por variables ausentes.

---

## 4. Variables mínimas recomendadas

Mapear en el plugin los campos CF7 correspondientes y usar en plantilla Mailjet:

| Variable API | Uso típico |
|--------------|------------|
| `name` | Nombre del prospecto |
| `email` | Email del formulario |
| `phone` | Teléfono |
| `service` | Servicio / producto de interés |
| `message` | Mensaje o comentarios |

En plantilla:

```text
{{var:name:""}}
{{var:email:""}}
{{var:phone:""}}
{{var:service:""}}
{{var:message:""}}
```

---

## 5. Variables opcionales recomendadas

Activar **Metadata CF7 en Mailjet** en admin si se necesitan en plantilla.

| Variable | Descripción |
|----------|-------------|
| `source_url` | URL de la página del envío |
| `source_page` | Título (y enlace) de la página |
| `submitted_at` | Fecha/hora del envío |
| `user_agent` | Navegador |
| `remote_ip` | IP parcial (enmascarada) |
| `utm_source` | UTM (si la URL del formulario los trae) |
| `utm_medium` | UTM |
| `utm_campaign` | UTM |
| `utm_term` | UTM |
| `utm_content` | UTM |
| `whatsapp_url` | URL de WhatsApp (mapeo dinámico o campo custom) |
| `reply_email_url` | Enlace `mailto:` para responder (mapeo dinámico) |

También: `form_id`, mapeos dinámicos CF7→Mailjet, variables de adjuntos (`*_url`, `attachments_all`) si están habilitados.

---

## 6. Fallbacks en Mailjet

Mailjet puede **bloquear** envíos si la plantilla exige una variable que no llega en el payload. Usar valor por defecto:

```text
{{var:name:"Prospecto"}}
{{var:service:"No especificado"}}
{{var:phone:"No proporcionado"}}
{{var:message:"Sin mensaje"}}
```

Botones dinámicos (ejemplos):

```text
{{var:whatsapp_url:"https://dominio.com"}}
{{var:reply_email_url:"mailto:contacto@dominio.com"}}
```

Regla práctica: toda variable que **no siempre** se envía debe llevar `:"valor_por_defecto"` o no referenciarse en la plantilla.

---

## 7. Modos de envío

Configuración por formulario en **Ajustes → CF7 Mailjet → CF7**.

### `cf7_mail` — Email nativo de Contact Form 7

- Contact Form 7 envía su correo nativo (`wp_mail` / SMTP del hosting).
- Después, el plugin ejecuta lista Mailjet y autorespuesta (según ajustes).

**Usar cuando:**

- El hosting o VPS tiene **correo saliente funcional**.
- Existe SMTP bien configurado (plugin SMTP, cPanel, etc.).
- Se quiere conservar el flujo de la pestaña **Mail** de CF7.

### `mailjet_only` — Mailjet API

- CF7 **no** envía mail nativo (`wpcf7_skip_mail`).
- El plugin envía por API: notificación al negocio (si está activa) → lista → autorespuesta.

**Usar cuando:**

- VPS bloquea puerto SMTP (ej. IONOS puerto 25).
- `wp_mail()` no es fiable.
- Mailjet debe ser el canal principal de correo del formulario.

**Importante:** en este modo la pestaña **Mail** de CF7 **no** notifica al administrador. La notificación al negocio debe hacerse con **plantilla Mailjet** o **HTML por defecto** del plugin (checkbox en admin).

---

## 8. Configuración WordPress

Orden sugerido:

1. **Instalar y activar** el plugin (y CF7 si no está activo).
2. **Mailjet:** API Keys, From Email, From Name.
3. **Autorespuesta:** habilitar + Template ID (probar).
4. **Lista:** habilitar + List ID + política contacto existente.
5. **CF7 — Formularios:** marcar formularios habilitados.
6. **CF7 — Campos:** mapear `your-email`, `your-name`, etc.
7. **CF7 — Canal de notificación interna** por formulario: `cf7_mail` o `mailjet_only`.
8. **Metadata / dinámicos / adjuntos:** según necesidad del cliente.
9. **Notificación al negocio:** se configura para canal `mailjet_only` (sin checkbox adicional de activación).
10. **Seguridad:** rate limits (recomendado en producción; `0` en staging para pruebas).
11. **Seguridad — Retención de adjuntos:** días en `uploads/cideapps-cf7-mailjet/` (`0` = no borrar; `30` recomendado).
12. **Seguridad — Desinstalación:** checkbox borrar uploads **desmarcado** por defecto; activar solo si el cliente lo solicita.
13. **Probar conexión** (lista) con el botón dedicado (no confundir con Guardar).
14. Envío de prueba desde el front con URL real (y UTM si aplica).

---

## 9. Checklist de despliegue

Marcar antes de dar por cerrado el sitio:

- [ ] Dominio validado en Mailjet.
- [ ] SPF configurado (un solo registro).
- [ ] DKIM configurado y propagado.
- [ ] DMARC configurado (mínimo `p=none`).
- [ ] Template autorespuesta probado.
- [ ] Template interno probado (si `mailjet_only` + notificación activa).
- [ ] Lista Mailjet configurada (si aplica).
- [ ] Formulario CF7 habilitado en el plugin.
- [ ] Campos mapeados correctamente.
- [ ] Modo de envío elegido y documentado para el cliente.
- [ ] Envío de prueba exitoso (mensaje de éxito en front).
- [ ] Correo recibido en Gmail.
- [ ] Correo recibido en Outlook/Hotmail.
- [ ] Headers revisados: SPF/DKIM/DMARC PASS.
- [ ] Logs revisados (`debug_logs` solo en staging).
- [ ] Prueba UTM (URL con parámetros → variables en correo/plantilla).
- [ ] Retención de adjuntos configurada (si el sitio usa file uploads).
- [ ] `debug_logs` **desactivado** en producción.

---

## 10. Troubleshooting

| Problema | Qué revisar |
|----------|-------------|
| CF7 muestra `mail_sent_ng` en VPS | Cambiar a **Solo Mailjet**; SMTP no requerido |
| SMTP bloqueado en VPS / IONOS | Canal `mailjet_only`; HTTPS a `api.mailjet.com` |
| Mailjet *blocked* / template language | Template ID correcto; idioma de plantilla = API |
| Variables faltantes en template | Payload vs plantilla; usar `{{var:x:""}}` |
| Botones WhatsApp no reemplazan URL | Mapeo dinámico + fallback en plantilla |
| SPF duplicado | Un solo TXT SPF; fusionar includes |
| DKIM no propagado | Esperar DNS; verificar registro en panel Mailjet |
| DMARC sin configurar | Añadir registro mínimo `p=none` |
| Archivos adjuntos no accesibles | Dominio público HTTPS; carpeta `uploads/cideapps-cf7-mailjet/` |
| WP-Cron no ejecuta limpieza | Tráfico al sitio o cron del sistema; `wp cron event run` como `www-data` |
| Segundo envío “no hace nada” | Idempotencia o rate limit (ver `PRUEBAS-MANUALES.md`) |
| Prueba lista falla al guardar | Usar solo **Probar conexión**, no Guardar ajustes |

---

## Pendientes de producto (no bloquean despliegue)

Documentados en `ACTIVIDADES-FUTURAS.md`:

- **Fase 4:** propiedades avanzadas en lista Mailjet (`source`, `form_id`, UTM en contacto, etc.) — requiere cambio de código.

---

*CIDEAPPS DIGITAL — Uso interno y entrega a cliente.*
