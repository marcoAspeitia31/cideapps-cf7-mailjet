# UX Navigation Blueprint — v1.4.0

Documento de referencia de arquitectura administrativa. Aprobado en análisis (Epic B0).

Relacionado: `ACTIVIDADES-FUTURAS.md`, `PRUEBAS-MANUALES.md` (sección 16), `REGLAS-TRABAJO.md`.

---

## Decisiones de producto (aprobadas)

| Tema | Decisión |
| ---- | -------- |
| Tab por defecto | **Formularios** |
| Notificación interna al negocio | Solo en **detalle del formulario**; no en configuración global |
| Autorespuesta y lista | **Global** en v1.4.0; en detalle solo estado + enlace informativo; per-form en v1.4.1+ |
| Navegación detalle | Query args: `?page=cideapps-cf7-mailjet&tab=forms&form_id=123` |
| Tabs nivel 1 | **Mailjet** \| **Formularios** \| **Seguridad** |

---

## Mapa de navegación

```txt
Ajustes › CF7 Mailjet
│
├─ Mailjet
│   ├─ Credenciales
│   └─ Remitente
│
├─ Formularios
│   ├─ LISTA (#forms-list)
│   │   ├─ Configuración global del sitio (colapsado)
│   │   │   ├─ Autorespuesta
│   │   │   ├─ Lista Mailjet (+ prueba)
│   │   │   ├─ Mappings globales
│   │   │   └─ Campos dinámicos globales
│   │   ├─ Enlaces → notificación por formulario
│   │   └─ Formularios habilitados (acordeón v1.4.0 → tabla Epic C)
│   └─ DETALLE (#form-detail) — ?form_id=
│       ├─ Notificación interna (editable)
│       ├─ Autorespuesta / Lista (solo lectura + enlace a global)
│       └─ (Epic D: General, Canal, Variables, Metadata, Adjuntos)
│
└─ Seguridad
    ├─ Rate limit
    ├─ Logs
    ├─ Retención adjuntos
    └─ Uninstall
```

---

## Query args

| Parámetro | Valores | Default |
| --------- | ------- | ------- |
| `page` | `cideapps-cf7-mailjet` | — |
| `tab` | `mailjet`, `forms`, `security` | `forms` |
| `form_id` | ID post CF7 | — (solo con `tab=forms`) |

Ejemplo detalle:  
`/wp-admin/options-general.php?page=cideapps-cf7-mailjet&tab=forms&form_id=123`

---

## Flujos

**Entrada a notificación interna:** Formularios → enlace por formulario → detalle (`form_id`) → sección Notificación interna.

**Regreso:** enlace «Volver a formularios» → lista sin `form_id`.

**Global:** Mailjet (cuenta); Formularios › global colapsado (autorespuesta, lista, mappings); Seguridad (operación).

**Primaria:** Editar formulario (enlace con `form_id`), Guardar configuración.

**Secundaria:** Configuración global del sitio, Probar lista, Restablecer (Epic C).

---

## Implementación por epic

| Epic | Navegación |
| ---- | ----------- |
| B0 | Este blueprint — **completado** |
| B1 | Shell 3 tabs, `#forms-list`, `#form-detail` (notificación en detalle; lista global) |
| B2 | Tab Mailjet aislado |
| B3 | Tab Seguridad + retención |
| C | Tabla en `#forms-list` |
| D | Secciones completas en `#form-detail` |
| E–F | Variables unificadas; ocultar global |

---

## Riesgos UX (seguimiento)

- Detalle con muchas secciones → acordeón o anclas (Epic D).
- Global vs per-form → badges y copy «global del sitio».
- Notificación solo en detalle sin tabla → enlaces por formulario hasta Epic C.
- Un solo formulario POST → todos los campos permanecen en el DOM al guardar.

---

## Historial

| Fecha | Evento |
| ----- | ------ |
| 2026-06 | Epic B0 aprobado; blueprint persistido |
| 2026-06 | Epic B1 — shell 3 tabs implementado |
