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

---

# Releases

| Release  | Descripción                                                    |
| -------- | -------------------------------------------------------------- |
| `v1.3.1` | Cierre etapa funcional principal                               |
| `v1.3.2` | Sprint mantenimiento (uploads + uninstall)                     |
| `v1.3.3` | Refactor UX y simplificación del canal de notificación interna |

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

## 1. Selector visual de campos CF7

Actualmente los campos se configuran manualmente:

```txt
your-email
your-name
your-phone
```

### Objetivo

Detectar automáticamente tags del formulario CF7 habilitado y mostrar dropdowns visuales.

### Ejemplo esperado

```txt
Email      → [your-email ▼]
Nombre     → [your-name ▼]
Teléfono   → [your-phone ▼]
Asunto     → [your-subject ▼]
Mensaje    → [your-message ▼]
```

### Beneficios

* Reduce errores de configuración.
* Facilita onboarding de clientes no técnicos.
* Mejora percepción profesional del plugin.
* Reduce soporte manual.

---

## 2. Configuración avanzada por formulario CF7

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

Las siguientes etapas deben enfocarse principalmente en:

1. Simplificar aún más la configuración.
2. Reducir errores humanos.
3. Mejorar experiencia de administración.
4. Escalar correctamente a múltiples formularios.
5. Fortalecer reutilización entre clientes/agencia.

Las futuras funcionalidades deben priorizar:

* valor operativo real,
* facilidad de uso,
* claridad UX,
* y mantenibilidad,

antes que complejidad técnica innecesaria.
