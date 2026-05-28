# Changelog

Todos los cambios notables de este proyecto se documentan en este archivo.

El formato se basa en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

## [1.3.2] - 2026-05-27

### Added

- Limpieza automática de adjuntos mediante WP-Cron (`cideapps_cf7_mailjet_upload_cleanup`).
- Ajuste de días de retención de adjuntos en admin (`cideapps_cf7_mailjet_attachment_retention_days`).
- Desinstalación limpia del plugin mediante `uninstall.php` y clase dedicada.
- Borrado opcional de la carpeta `uploads/cideapps-cf7-mailjet/` al desinstalar (opt-in en admin).
- Guía de despliegue por cliente en `docs/GUIA-DESPLIEGUE-CLIENTE.md`.

### Changed

- Documentación de QA manual actualizada (`PRUEBAS-MANUALES.md` — Fases 1 y 2).
- Roadmap del sprint de mantenimiento actualizado (`ACTIVIDADES-FUTURAS.md`).

### Security

- `uninstall.php` protegido con comprobación `WP_UNINSTALL_PLUGIN`.
- Borrado de uploads en desinstalación restringido al directorio del plugin.
- Eliminación de options del plugin mediante lista blanca explícita (31 options).
- Eliminación de transients solo por prefijos exactos (`cf7_mj_email_`, `cf7_mj_ip_`, `cf7_mj_proc_`).

## [1.3.1] - 2026-05

### Added

- Reply-To en notificación al negocio con plantilla Mailjet igual que en modo HTML (email del lead).

### Changed

- Etapa funcional CF7 + Mailjet validada en staging y producción (modos `cf7_mail` y `mailjet_only`).

## [1.3.0] y anteriores

Ver `DESARROLLO.md` y `PRUEBAS-MANUALES.md` para el detalle de funcionalidades previas (metadata, mapeos dinámicos, adjuntos por URL, notificación al negocio, etc.).
