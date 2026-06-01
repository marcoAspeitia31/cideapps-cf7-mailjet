# Actividades futuras — Cideapps CF7 Mailjet

Documento de seguimiento posterior al cierre del desarrollo base del plugin.

La validación funcional se encuentra en `PRUEBAS-MANUALES.md`.
La arquitectura técnica y decisiones internas en `DESARROLLO.md`.

---

# Estado actual del plugin

## Funcionalidades completadas

| Área                                                        | Estado     |
| ----------------------------------------------------------- | ---------- |
| Integración Contact Form 7 ↔ Mailjet                        | Completado |
| Canal de notificación interna (`cf7_mail` / `mailjet_only`) | Completado |
| Autorespuesta Mailjet                                       | Completado |
| Notificación interna al negocio                             | Completado |
| Variables dinámicas y metadata                              | Completado |
| Adjuntos por URL                                            | Completado |
| Limpieza automática de adjuntos                             | Completado |
| Desinstalación limpia (`uninstall.php`)                     | Completado |
| Guía de despliegue y changelog                              | Completado |
| QA manual staging / producción                              | Completado |
| Refactor UX del canal de notificación interna               | Completado |
| Selector visual de campos CF7 (dropdowns, PHP-only)         | Completado |

---

# Releases

| Release  | Descripción                                                    |
| -------- | -------------------------------------------------------------- |
| `v1.3.1` | Cierre etapa funcional principal                               |
| `v1.3.2` | Sprint mantenimiento (uploads + uninstall)                     |
| `v1.3.3` | Refactor UX y canal de notificación interna                    |
| `v1.3.4` | Selector visual de campos CF7 (dropdowns desde tags reales)  |

---

# Roadmap prioritario (valor comercial)

Las siguientes actividades se priorizan por:

* facilidad de configuración,
* reducción de errores humanos,
* reutilización multi-cliente,
* experiencia de usuario en admin,
* y valor operativo real para agencias.

---

# Prioridad alta

## 1. Selector visual de campos CF7 — **Completado**

**Estado:** implementado y validado en release `v1.3.4` (junio 2026). QA en `PRUEBAS-MANUALES.md` §15. La tag `v1.3.3` corresponde únicamente al refactor del canal de notificación interna.

### Entregado en v1.3.4

* Dropdowns en admin para mappings globales (`email`, `name`, `phone`, `service`, `message`).
* Lectura de tags con `scan_form_tags()` vía `Cideapps_Cf7_Mailjet_Cf7_Field_Selector`.
* Fuente: formularios habilitados, o todos los CF7 si ninguno está habilitado.
* Valores guardados inexistentes en el formulario: opción `(valor guardado)`.
* Iteración mínima: solo PHP (sin JS, CSS nuevo, AJAX ni transients).

### Mejoras diferidas (no bloquean la siguiente fase)

* Refresco de dropdowns sin recargar página (JS).
* Selectores por formulario individual (ver §2).
* Entrada manual explícita además del desplegable.
* Filtrado por tipo de tag (email vs textarea) en la UI.

---

## 2. Configuración avanzada por formulario CF7 — **Siguiente**

Actualmente varias configuraciones siguen siendo globales.

### Objetivo

Permitir configuración independiente por formulario:

* template Mailjet
* lista Mailjet
* canal de notificación interna
* metadata
* adjuntos
* mappings dinámicos
* autorespuesta

### Casos de uso

Un mismo sitio podría tener:

* formulario contacto
* cotización
* soporte
* bolsa de trabajo
* leads comerciales
* descarga de recursos

cada uno con comportamiento distinto.

### Beneficios

* Escalabilidad multi-formulario.
* Mejor reutilización entre clientes.
* Arquitectura más modular.

---

## 3. Mejoras UX para mappings dinámicos

Actualmente parte de los mappings y variables siguen siendo técnicos.

### Objetivo

Mejorar experiencia visual para:

* mappings dinámicos
* variables Mailjet
* metadata
* adjuntos
* labels de servicios

### Posibles mejoras

* botones añadir/eliminar más claros
* agrupación visual por secciones
* tooltips
* validaciones inline
* placeholders inteligentes

### Beneficio

Reducir complejidad percibida del plugin.

---

## 4. Más soporte de mail-tags CF7

Actualmente se soportan:

```txt
[_remote_ip]
[_user_agent]
[_url]
[_date]
[_time]
```

### Expandir soporte a:

```txt
[_post_title]
[_site_title]
[_site_url]
[_serial_number]
[_post_url]
```

### Beneficios

* Templates Mailjet más ricos.
* Mejor trazabilidad.
* Correos internos más útiles.

---

# Prioridad media

## 5. Propiedades avanzadas en Mailjet

Enviar propiedades adicionales al contacto:

```txt
source
form_id
created_at
landing_url
browser
visitor_ip
```

### Beneficios

* Segmentación avanzada.
* Automatizaciones futuras.
* Mejor contexto comercial.
* Integración CRM más útil.

---

## 6. Exportar / importar configuración

Permitir exportar settings del plugin en JSON.

### Casos de uso

* staging → producción
* replicar configuración entre clientes
* backups rápidos

### Beneficios

* Despliegues más rápidos.
* Menos errores manuales.

---

## 7. Panel de estado del plugin

Mostrar:

* último envío exitoso
* último error API
* estado Mailjet
* último cron ejecutado
* acceso rápido a logs

### Beneficios

* Menos debugging manual.
* Mejor soporte técnico.
* Más visibilidad operativa.

---

## 8. Internacionalización (i18n)

Preparar:

* `.pot`
* español
* inglés

### Objetivo

Facilitar distribución futura del plugin.

---

# Prioridad baja / futura

## 9. Webhooks y eventos

Hooks tipo:

```php
do_action(
  'cideapps_cf7_mailjet_after_submission',
  $submission_data
);
```

### Integraciones posibles

* n8n
* Zapier
* CRM externos
* microservicios
* automatizaciones internas

---

## 10. Tests automatizados

### Objetivo

Agregar:

* PHPUnit
* mocks Mailjet
* tests integración

---

## 11. Compatibilidad multisite

Configuración por sitio WordPress Network.

---

## 12. README público del plugin

Actualizar documentación estilo WordPress.org.

---

# Actividades descartadas o no prioritarias

Estas ideas se consideran fuera del enfoque inmediato del producto o de bajo valor comercial actual.

---

## Adjuntos privados con URLs firmadas

Pospuesto.

### Razones

* Mayor complejidad técnica.
* Poco impacto comercial inmediato.
* URLs públicas actuales son suficientes para la mayoría de clientes.

---

## Endpoint temporal de descarga protegida

Pospuesto para futura versión enterprise/premium.

---

## Reglas avanzadas nginx para adjuntos

No prioritario actualmente.

---

# Mantenimiento recomendado

| Actividad                        | Frecuencia               |
| -------------------------------- | ------------------------ |
| Revisar compatibilidad CF7       | Trimestral               |
| Revisar compatibilidad WordPress | Trimestral               |
| Revisar límites Mailjet          | Cuando crezcan templates |
| Actualizar CHANGELOG             | Cada release             |
| QA manual completo               | Antes de deploy cliente  |

---

# Resumen ejecutivo

El plugin ya se considera estable y reutilizable para clientes reales.

**Completado recientemente:** selector visual de campos CF7 (`v1.3.4`).

**Siguiente fase recomendada:** configuración avanzada por formulario CF7 (§2 de este documento), reutilizando `Cideapps_Cf7_Mailjet_Cf7_Field_Selector` para dropdowns por `form_id` sin duplicar la lógica global existente.

Las siguientes etapas deben enfocarse principalmente en:

1. Configuración avanzada por formulario CF7 (prioridad inmediata).
2. Simplificar aún más la configuración y reducir errores humanos.
3. Mejorar experiencia de administración en mappings dinámicos y metadata.
4. Escalar correctamente a múltiples formularios en un mismo sitio.
5. Fortalecer reutilización entre clientes/agencia.

Las futuras funcionalidades deben priorizar:

* valor operativo real,
* facilidad de uso,
* claridad UX,
* y mantenibilidad,

antes que complejidad técnica innecesaria.
