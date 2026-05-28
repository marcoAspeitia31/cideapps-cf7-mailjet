# Actividades futuras — Cideapps CF7 Mailjet

Documento de seguimiento **posterior al cierre de la etapa de desarrollo** (v1.3.1).  
Lo validado en staging y producción está en `PRUEBAS-MANUALES.md`. La arquitectura técnica en `DESARROLLO.md`.

---

## Estado actual

| Ámbito | Estado |
|--------|--------|
| Integración CF7 ↔ Mailjet (lista, autorespuesta) | Completado |
| Modos `cf7_mail` / `mailjet_only` | Completado y probado (VPS IONOS + cPanel) |
| Notificación al negocio (template + HTML, Reply-To lead) | Completado |
| Variables core, metadata, dinámicos, adjuntos URLs | Completado |
| QA manual | Completado |

**El plugin está listo para uso en clientes** con la configuración documentada. Lo siguiente es mejora continua, no bloqueante.

---

## Prioridad alta (recomendado para operación en clientes)

### 1. Propiedades extra en lista Mailjet

`DESARROLLO.md` menciona `source`, `form_id`, `created_at` en contactos; hoy solo se envían `name`, `phone`, `service`.

- Definir qué propiedades existen en la cuenta Mailjet del cliente
- Mapearlas en `build_contact_properties()`
- Documentar en plantilla de onboarding

**Beneficio:** segmentación y trazabilidad en Mailjet sin depender solo del correo.

### 2. Limpieza de archivos subidos — **hecho (Fase 1, mayo 2026)**

- Commit: `817de74` — cron `cideapps_cf7_mailjet_upload_cleanup`, option `cideapps_cf7_mailjet_attachment_retention_days`
- QA manual: `PRUEBAS-MANUALES.md` §11 (validación exitosa en staging local)

**Beneficio:** evita llenar disco en sitios con muchos formularios con file.

### 3. `uninstall.php` y desinstalación limpia — **hecho (Fase 2, mayo 2026)**

- Commit: `feat(uninstall): clean plugin options on uninstall`
- QA manual: `PRUEBAS-MANUALES.md` §12 (validación exitosa en servidor de pruebas)

**Beneficio:** sitios limpios al quitar el plugin en agencias, sin tocar datos ajenos.

### 4. Guía de despliegue por cliente

README o wiki interna (no solo `DESARROLLO.md`):

- Checklist Mailjet (dominio, API keys, templates)
- Cuándo usar **Solo Mailjet** vs **CF7 + Mailjet**
- Variables mínimas en plantilla + `{{var:clave:""}}`
- Ejemplo IONOS (SMTP 25 cerrado)

**Beneficio:** reutilización multi-sitio sin releer el hilo de desarrollo.

---

## Prioridad media (producto / UX)

### 5. Configuración por formulario CF7

Hoy casi todo es global (mapeos, metadata, adjuntos).

- Mapeos y modo de envío ya son por formulario; extender metadata/adjuntos por ID de formulario si un sitio tiene varios CF7 distintos

### 6. Selector de campos CF7 en admin

En lugar de escribir a mano `your-email`, listar tags del formulario habilitado (dropdown).

**Beneficio:** menos errores de configuración para clientes no técnicos.

### 7. Más mail-tags CF7

Ampliar soporte más allá de `[_remote_ip]`, `[_user_agent]`, `[_url]`, `[_date]`, `[_time]` (p. ej. `[_post_title]`, `[_site_title]`).

### 8. Adjuntos: opciones de privacidad

- Enlaces firmados con expiración (transient + endpoint PHP)
- Opción “registrar en Media Library” además de carpeta del plugin
- Reglas nginx equivalentes al `.htaccess` actual (Apache)

### 9. Internacionalización (i18n)

Unificar cadenas admin (Add/Remove vs Añadir/Quitar) y preparar `.pot` si el plugin se distribuye.

### 10. Settings API de WordPress

Migrar guardado manual del admin a Settings API + sanitización centralizada (opcional; el flujo actual funciona).

---

## Prioridad baja (nice to have)

### 11. Tests automatizados

- PHPUnit para `Submission_Data` (mapeos, metadata, URLs)
- Tests de integración mock de Mailjet API

### 12. Exportar / importar configuración

JSON de opciones del plugin para clonar setup entre staging y producción.

### 13. Panel de estado en admin

Último envío procesado, último error API, enlace a logs si `debug_logs` activo.

### 14. Webhooks / eventos

Hook `cideapps_cf7_mailjet_after_submission` para CRM externos (Zapier, n8n) sin microservicio.

### 15. Multisite

Opciones por sitio en red WordPress.

### 16. README público del plugin

Actualizar `README.txt` (WordPress.org style) con features reales, no boilerplate wppb.

---

## Mantenimiento y releases

| Actividad | Cuándo |
|-----------|--------|
| Tag git `1.3.1` (o siguiente) en remoto | Al cerrar etapa |
| Changelog (`CHANGELOG.md`) | Cada release |
| Revisar compatibilidad CF7 / WP al actualizar dependencias | Trimestral o antes de deploy cliente |
| Revisar límites API Mailjet (variables, tamaño) | Si plantillas crecen mucho |

---

## Qué no está en alcance (decisión explícita)

- Microservicio externo (ver `DESARROLLO.md`)
- Adjuntos binarios embebidos en API Mailjet (solo URLs)
- Sustituir por completo Flamingo / almacén CF7 de entradas
- Modificar el theme del cliente

---

## Resumen ejecutivo

1. **Etapa v1.3.1:** cerrada (tag `v1.3.1`).  
2. **Sprint mantenimiento:** Fase 1 (limpieza uploads) y Fase 2 (uninstall limpio) **hechas y validadas** — ver `PRUEBAS-MANUALES.md` §11 y §12.  
3. **Siguiente:** Fase 3 guía cliente → Fase 4 propiedades Mailjet (una fase / commit / validación).  
4. **Backlog:** UX admin, más mail-tags, privacidad adjuntos, tests.

Para retomar trabajo, abrir este archivo y elegir ítems por prioridad con el cliente o product owner.
