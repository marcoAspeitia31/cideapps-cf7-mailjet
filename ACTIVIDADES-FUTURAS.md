# Actividades futuras — Cideapps CF7 Mailjet

Roadmap oficial de evolución del producto **después de v1.3.4**.

Documentos relacionados:

- Validación funcional: `PRUEBAS-MANUALES.md`
- Arquitectura técnica: `DESARROLLO.md`
- Reglas de trabajo del proyecto: `REGLAS-TRABAJO.md`
- Blueprint UX admin v1.4: `docs/UX-NAVIGATION-BLUEPRINT-v1.4.md`

---

# Cambio de dirección (post v1.3.4)

## Contexto

Base estable etiquetada:

```txt
v1.3.4
commit/tag: 5397d628d8b1508435295d2263688218b0aa305b
```

El plugin dejó de ser solo una integración interna y se acerca a un producto reutilizable (Contact Form 7 + Mailjet). Técnicamente es sólido; el cuello de botella actual es la **experiencia de administración**.

## Prioridad del proyecto

```txt
v1.4.0 = Rediseño UX/Admin
```

**Pausa en nuevas features técnicas** hasta reorganizar la interfaz. No se reescribe el plugin ni el runtime en esta etapa: se **evoluciona** lo existente.

## Principios de producto

1. **Claridad** — cada pantalla con una responsabilidad clara.
2. **Configuración centrada en formularios** — el formulario CF7 es la unidad de trabajo del administrador.
3. **Separación de responsabilidades** — Mailjet, Formularios y Seguridad no deben mezclarse en una sola vista densa.
4. **Compatibilidad** — mismos `wp_options`, fallback global, sin migraciones agresivas.
5. **Evolución, no reescritura** — aprovechar API Mailjet, runtime, field selector, `Form_Settings` y QA ya validados.

## Visión del admin objetivo

### Tab 1 — Mailjet

Solo cuenta y remitente:

- API Key / Secret Key
- From Email / From Name
- Prueba de conexión

Sin formularios, mappings, autorespuesta, lista, metadata ni seguridad.

### Tab 2 — Formularios

- Tabla de formularios CF7 detectados (nombre, estado, canal resumido, acciones).
- Acciones: **Editar** configuración del formulario / **Restablecer** configuración del formulario.
- Vista detalle por formulario (ver abajo).

### Tab 3 — Seguridad

Solo operación y protección:

- Rate limit por email / IP
- Debug logs
- Retención y limpieza de adjuntos
- Opciones de uninstall

### Vista detalle por formulario

Configuración agrupada por secciones:

| Sección | Contenido |
| ------- | --------- |
| **General** | Activar integración; canal de notificación interna (`cf7_mail` / `mailjet_only`) |
| **Notificación interna** | Email destino negocio; modo template / HTML; template ID; asunto |
| **Autorespuesta** | Activar; template ID |
| **Lista Mailjet** | Activar guardado; list ID; estrategia contacto existente |
| **Campos y variables** | Campos CF7 detectados → variables Mailjet (centro de la configuración) |
| **Metadata** | source_url, source_page, submitted_at, user_agent, remote_ip, UTMs |
| **Adjuntos** | Copiar uploads; URLs públicas; retención (donde aplique por formulario en fases posteriores) |

### Modelo de mapeo (dirección a medio plazo)

```txt
Campo CF7 detectado (your-name, your-email, …)
        ↓
Variable Mailjet (customer_name, customer_email, …)
```

Los cinco slots lógicos actuales (`email`, `name`, `phone`, `service`, `message`) se mantienen en runtime como **capa de compatibilidad**, pero la UI debe orientar al usuario hacia campos reales del formulario, no hacia nombres fijos globales.

---

# Estado actual del plugin

## Capacidades entregadas (no son trabajo pendiente)

| Área | Estado | Release / nota |
| ---- | ------ | -------------- |
| Integración CF7 ↔ Mailjet | Completado | v1.3.x |
| Canal notificación interna (`cf7_mail` / `mailjet_only`) | Completado | v1.3.3 |
| Autorespuesta Mailjet | Completado | global |
| Notificación interna al negocio | Completado | global |
| Variables dinámicas y metadata | Completado | global |
| Adjuntos por URL + limpieza cron | Completado | v1.3.2 |
| Desinstalación limpia | Completado | v1.3.2 |
| Selector visual campos CF7 | Completado | v1.3.4 |
| Mappings por formulario (5 slots + fallback global) | Completado | v1.3.4 |
| Resolver y runtime per-form (mappings + canal) | Completado | v1.3.4 |
| QA manual staging / producción | Completado | `PRUEBAS-MANUALES.md` |
| Guía despliegue y changelog | Completado | — |

## Brecha conocida (producto, no bug)

| Qué ya funciona en código | Qué falta en producto |
| ------------------------- | --------------------- |
| `Form_Settings` + resolver per-form para 5 mappings y canal | UI centrada en formularios (tabla + detalle) |
| `Cf7_Field_Selector` con tags reales | Mapeo visual CF7 → Mailjet como centro de la pantalla |
| Opciones globales para template, lista, autorespuesta, metadata, adjuntos | Esas opciones siguen editándose en pantalla global mezclada |
| Tabs actuales: Mailjet, Autorespuesta, Lista, CF7, Seguridad | Objetivo: 3 tabs + detalle por formulario |

---

# Releases

| Release | Descripción |
| ------- | ----------- |
| `v1.3.1` | Cierre etapa funcional principal |
| `v1.3.2` | Mantenimiento: uploads + uninstall |
| `v1.3.3` | Refactor UX canal de notificación interna |
| `v1.3.4` | Selector campos CF7 + mappings/resolver/runtime per-form |
| **`v1.4.0`** | **Rediseño UX/Admin (este documento)** |
| `v1.4.1+` | Persistencia extendida per-form (después de cerrar v1.4.0) |

---

# Roadmap v1.4 — Rediseño UX/Admin (prioridad única)

> **Congelado hasta tag `v1.4.0`:** no iniciar features del backlog post-v1.4 (mail-tags extra, export JSON, webhooks, etc.) salvo mantenimiento o bugs.

Cada epic = **una responsabilidad** = **una fase** = **un commit funcional** cuando llegue la implementación (ver `REGLAS-TRABAJO.md`). Esta sección define **estrategia**; la implementación es posterior.

---

## Epic A — Documentación y alineación

| Fase | Entregable |
| ---- | ----------- |
| A0 | Este roadmap actualizado (`ACTIVIDADES-FUTURAS.md`) — **completado** |
| A1 | Alinear intro y checklist v1.4 en `PRUEBAS-MANUALES.md` — **completado** (sección 16) |

---

## Epic B0 — UX Navigation Blueprint — **completado**

| Entregable | Ubicación |
| ---------- | --------- |
| Mapa de navegación, wireframes, flujos, riesgos UX | `docs/UX-NAVIGATION-BLUEPRINT-v1.4.md` |

**Decisiones de producto cerradas:**

- Tab por defecto: **Formularios**
- Notificación interna: solo en **detalle** (`form_id`); no en global
- Autorespuesta / lista: **global** en v1.4.0; detalle informativo hasta v1.4.1+
- Detalle: query args `tab=forms&form_id={id}`

---

## Epic B — Arquitectura de navegación (3 tabs)

**Objetivo:** separar responsabilidades sin cambiar comportamiento del runtime.  
**Referencia:** `docs/UX-NAVIGATION-BLUEPRINT-v1.4.md`

| Fase | Entregable | QA esperado | Estado |
| ---- | ----------- | ----------- | ------ |
| B0 | Blueprint UX navegación | Aprobación producto | **Completado** |
| B1 | Shell: tabs **Mailjet** \| **Formularios** \| **Seguridad**; `#forms-list`, `#form-detail`; query args | Guardar no pierde datos | **Completado** (validado staging, junio 2026) |
| B2 | Tab **Mailjet** limpio: solo credenciales y From | Sin mezcla con formularios | **Completado** (validado staging, junio 2026) |
| B3 | Tab **Seguridad** limpio: rate limit, logs, retención, uninstall | Sin regresión cron/logs | **Completado** (validado staging, junio 2026) |

**Epic B (B0–B3):** **cerrado y validado en staging** (junio 2026).

**Nota v1.4.0:** Autorespuesta y lista permanecen en **configuración global del sitio** (colapsada en `#forms-list`). En `#form-detail` solo mensaje/enlace hasta persistencia per-form (v1.4.1+).

---

## Epic C — Tab Formularios (lista)

| Fase | Entregable | QA esperado | Estado |
| ---- | ----------- | ----------- | ------ |
| C1 | Tabla simple: Formulario, Estado, Canal, Editar (`form_id` en URL) | Coincide con `enabled_form_ids` y `form_mail_modes` | **Completado y validado** (staging, junio 2026) |
| C2 | Acción Restablecer por `form_id` (confirmación; sin tocar global/Mailjet) | Solo un formulario | **Completado y validado** (staging, junio 2026) |

**Epic C (C1–C2):** **cerrado y validado en staging** (junio 2026).

### Plan técnico C1 (alcance reducido — aprobado)

**Sí:** tabla en `#forms-list`; columnas Formulario, Estado de integración, Canal, Acción (Editar → `tab=forms&form_id={id}`); inputs ocultos para preservar `enabled_form_ids` y `form_mail_modes` al guardar; global colapsado y mappings globales sin cambios.

**No en C1:** Restablecer; columna Mappings; badges Hereda/Personalizado; filtros; búsqueda; edición inline en tabla.

**Archivos:** `admin/partials/cideapps-cf7-mailjet-admin-display.php`, `admin/css/cideapps-cf7-mailjet-admin.css`.

**No tocar:** POST handler, runtime, `Form_Settings`, resolver, Mailjet API.

---

## Epic D — Vista detalle por formulario

| Fase | Entregable | Reutiliza | Estado |
| ---- | ----------- | --------- | ------ |
| D1 | Pantalla o ruta detalle (esqueleto + navegación volver) | — | **Completado y validado** (staging, junio 2026) |
| D2 | Sección **General**: activar integración; canal interno | Opciones actuales per-form | **Completado y validado** (staging, junio 2026) |
| D3 | Sección **Campos y variables**: mappings con field selector | `Cf7_Field_Selector`, `Form_Settings` | Pendiente |
| D4 | Sección **Notificación interna** (UI) | Options globales actuales; mismo runtime | Pendiente |
| D5 | Sección **Autorespuesta** y **Lista** (UI) | Options globales; copy claro «global hasta v1.4.1» | Pendiente |
| D6 | Sección **Metadata** y **Adjuntos** (UI) | Options globales actuales | Pendiente |

---

## Epic E — Mapeo centrado en campos CF7 detectados

| Fase | Entregable |
| ---- | ----------- |
| E1 | Tabla visual: campo CF7 → variable Mailjet (además o encima de los 5 slots legacy en UI) |
| E2 | Integrar mappings dinámicos repetibles en la misma sección del detalle |
| E3 | Mejoras UX: agrupación, tooltips, validación inline, placeholders (sin bloquear C–D) |
| E4 | Opcional post-v1.4.0: refresco dropdowns sin recargar (JS); filtro por tipo de tag |

**Norte:** cualquier formulario CF7 sin depender conceptualmente de `email` / `name` / `phone` / `service` / `message` como único modelo mental.

---

## Epic F — Reducción de dependencia visual de globals

| Fase | Entregable |
| ---- | ----------- |
| F1 | Colapsar u ocultar mappings globales con mensaje: «Configura por formulario» |
| F2 | Indicadores en lista/detalle: **Personalizado** vs **Hereda global** |
| F3 | Documentar fallback en `GUIA-DESPLIEGUE-CLIENTE.md` |

Runtime y `wp_options` globales **permanecen** como fallback hasta fases v1.4.1+.

---

## Epic G — Cierre release v1.4.0

| Fase | Entregable |
| ---- | ----------- |
| G1 | QA completo admin (nueva sección en `PRUEBAS-MANUALES.md`) |
| G2 | `CHANGELOG.md` + tag `v1.4.0` |
| G3 | Actualizar `GUIA-DESPLIEGUE-CLIENTE.md` con flujo por formulario |

---

# Roadmap v1.4.1+ — Persistencia per-form (después de v1.4.0)

Solo cuando Epic A–G estén **validados** (regla: no avanzar de fase sin QA).

Extender almacenamiento y resolver en `Form_Settings` (o equivalente) con fallback a globals:

| Fase | Configuración per-form |
| ---- | ---------------------- |
| P1 | Notificación interna: destino, modo, template, asunto |
| P2 | Autorespuesta: activar, template ID |
| P3 | Lista: activar, list ID, estrategia contacto existente |
| P4 | Metadata y adjuntos: flags por formulario |
| P5 | Deprecación UI de opciones globales duplicadas (solo cuando fallback esté probado) |

---

# Backlog post v1.4 (congelado)

Actividades del roadmap anterior reclasificadas. **No iniciar** hasta tag `v1.4.0` salvo bugfix o mantenimiento.

## Prioridad media (después de v1.4.0)

| Tema | Origen roadmap anterior | Motivo del aplazamiento |
| ---- | ----------------------- | ------------------------ |
| Internacionalización (i18n) | Antes «prioridad media» | Conviente tras estabilizar textos de la nueva UI |
| README público WordPress.org | Antes «prioridad baja» | Producto público requiere admin claro primero |
| Panel de estado del plugin | Antes «prioridad media» | Encaja en tab Seguridad tras Epic B3 |
| Exportar / importar configuración JSON | Antes «prioridad media» | Útil agencia; añade complejidad antes de UX clara |

## Prioridad baja / futura

| Tema | Origen roadmap anterior |
| ---- | ----------------------- |
| Más mail-tags CF7 (`[_post_title]`, `[_site_url]`, etc.) | Antes «prioridad alta» — **rebajado** |
| Propiedades avanzadas en contacto Mailjet | Antes «prioridad media» |
| Webhooks y eventos (`do_action` after submission) | Antes «prioridad baja» |
| Tests automatizados (PHPUnit, mocks) | Antes «prioridad baja» |
| Compatibilidad multisite | Antes «prioridad baja» |

## Descartadas o no prioritarias (sin cambio)

- Adjuntos privados con URLs firmadas — pospuesto.
- Endpoint temporal de descarga protegida — enterprise/premium futuro.
- Reglas avanzadas nginx para adjuntos — no prioritario.

---

# Reclasificación del roadmap anterior

Referencia para quien leía la versión pre-v1.4 del documento.

| Antes (roadmap v1.3.x) | Decisión | Ubicación en este documento |
| ---------------------- | -------- | --------------------------- |
| 1. Selector visual campos CF7 | **Completado** | Capacidades entregadas |
| 2. Configuración avanzada por formulario | **Dividido** | Epic C–D (UI v1.4.0) + v1.4.1+ (persistencia) |
| 3. Mejoras UX mappings dinámicos | **Reagrupado** | Epic E (centro del detalle) |
| 4. Más mail-tags CF7 | **Prioridad baja** | Backlog post v1.4 |
| 5. Propiedades avanzadas Mailjet | **Prioridad baja** | Backlog post v1.4 |
| 6. Export/import JSON | **Prioridad media** | Backlog post v1.4 |
| 7. Panel estado | **Prioridad media** | Backlog; candidato tab Seguridad |
| 8. i18n | **Prioridad media** | Backlog post v1.4.0 |
| 9. Webhooks | **Prioridad baja** | Sin cambio |
| 10. Tests automatizados | **Prioridad baja** | Sin cambio |
| 11. Multisite | **Prioridad baja** | Sin cambio |
| 12. README público | **Prioridad media** | Tras UX estable |

### Actividades que ya no tienen sentido como prioridad inmediata

- **«Siguiente: más features técnicas por formulario»** sin rediseñar admin — sustituido por v1.4.0 UX.
- **Listar el selector CF7 en prioridad alta** como trabajo pendiente — ya está en v1.3.4.
- **Tratar mappings globales como flujo principal** — pasa a legacy visual con deprecación gradual (Epic F).

### Mejoras diferidas del selector (v1.3.4) — siguen vivas, no bloquean

- Refresco dropdowns sin recargar (JS)
- Entrada manual explícita además del desplegable
- Filtrado por tipo de tag en UI

Integrar en Epic E cuando corresponda.

---

# Qué se conserva (sin tirar el plugin)

**Runtime y datos:**

- Integración Mailjet API
- Canales `cf7_mail` y `mailjet_only`
- Autorespuesta, lista, notificación interna, metadata, adjuntos
- Limpieza automática de uploads y uninstall
- `Cideapps_Cf7_Mailjet_Cf7_Field_Selector`
- `Cideapps_Cf7_Mailjet_Form_Settings` y fallback global
- Mappings dinámicos repetibles (options actuales)
- Rate limit, logger, QA y documentación existente

**Enfoque de implementación futura:**

- Evolucionar `admin/partials/cideapps-cf7-mailjet-admin-display.php` por fases
- No crear v2 ni rewrite del handler CF7 en v1.4.0

---

# Deprecación gradual (solo producto/UI; no borrado inmediato)

| Elemento | Estrategia |
| -------- | ---------- |
| Mappings globales (`email_field`, `name_field`, …) en pantalla principal | Ocultar o colapsar en Epic F; runtime sigue leyendo global si el formulario hereda |
| Cinco slots fijos como modelo mental | Mantener en BD; UI muestra campos CF7 reales y variables Mailjet |
| Tab CF7 monolítico actual | Sustituir por tab Formularios + detalle |
| Tabs Autorespuesta / Lista separados del todo | Absorber en detalle o marcar «global» hasta v1.4.1 |
| Configuración «todo en Guardar configuración» | Evaluar guardado por sección o por formulario en implementación (riesgo UX) |

**No deprecar en v1.4.0:** nombres de `wp_options`, comportamiento de envío, ni datos ya guardados en producción.

---

# Transición v1.3.4 → v1.4.0

```txt
v1.3.4  Runtime per-form + UI monolítica
   ↓
v1.4.0  Reorganización admin (mismos options, mismo runtime)
   ↓
v1.4.1+ Extender Form_Settings + resolver (fallback global)
```

Reglas:

1. Sin migración agresiva de base de datos.
2. Instalaciones existentes deben comportarse igual hasta que el usuario edite por formulario.
3. PR solo con fase cerrada y QA (`REGLAS-TRABAJO.md`).
4. Commits de documentación separados de commits funcionales.

---

# Mantenimiento recomendado

| Actividad | Frecuencia |
| --------- | ---------- |
| Revisar compatibilidad CF7 | Trimestral |
| Revisar compatibilidad WordPress | Trimestral |
| Revisar límites Mailjet | Cuando crezcan templates |
| Actualizar CHANGELOG | Cada release |
| QA manual completo | Antes de deploy cliente y antes de tag v1.4.0 |

---

# Resumen ejecutivo

El plugin **funciona** y está validado hasta **v1.3.4**. El siguiente salto de valor no es agregar opciones a la pantalla actual, sino **v1.4.0 = rediseño UX/Admin**: tres áreas claras (Mailjet, Formularios, Seguridad), tabla de formularios CF7, detalle por formulario y mapeo centrado en campos detectados.

Las features técnicas del roadmap anterior (mail-tags extra, propiedades lista, export JSON, webhooks, etc.) quedan en **backlog congelado** hasta cerrar v1.4.0. La persistencia completa por formulario (template, lista, metadata flags) es **v1.4.1+**, después de validar la nueva experiencia.

Priorizar siempre: **claridad, mantenibilidad y adopción por terceros** por encima de más configuración o complejidad técnica.
