# Reglas de trabajo del proyecto

Estas reglas ya demostraron funcionar correctamente durante las versiones 1.3.2, 1.3.3 y 1.3.4.

Deben mantenerse para las siguientes fases.

---

## 1. No implementar sin plan previo

Antes de escribir código:

- analizar alcance
- identificar archivos
- definir riesgos
- definir QA esperado
- acordar estrategia

Primero plan.

Después implementación.

---

## 2. Fases pequeñas

Evitar desarrollos grandes.

Objetivo:

```txt
Una responsabilidad por fase
```

Ejemplos:

- Cleanup uploads
- Uninstall
- Refactor canal notificación
- Resolver per-form
- UI per-form
- Runtime per-form

No mezclar varias funcionalidades en una sola fase.

---

## 3. Commits pequeños

Evitar mega-commits.

Objetivo:

```txt
1 fase
=
1 commit
```

Siempre que sea posible.

Ejemplo:

```txt
feat(settings): add form-level field mapping storage and resolver
```

---

## 4. QA obligatorio antes de commit

No hacer commit inmediatamente después de programar.

Flujo:

```txt
Implementación
↓
Checklist QA
↓
Validación manual
↓
Commit
```

---

## 5. Documentación separada

La documentación no debe mezclarse con el código.

Flujo:

```txt
Commit funcional
↓
QA
↓
Commit documentación
```

Ejemplos:

```txt
feat(...)
docs(...)
```

---

## 6. No avanzar de fase sin validación

Si una fase no está validada:

- no continuar
- no programar la siguiente

Primero cerrar la fase actual.

---

## 7. Mantener backward compatibility

Prioridad alta.

Evitar:

- migraciones agresivas
- cambios de comportamiento inesperados
- romper instalaciones existentes

Preferir:

- fallback
- compatibilidad
- deprecación gradual

---

## 8. No reescrituras innecesarias

Antes de proponer:

```txt
rehacer
reiniciar
crear v2
```

evaluar:

```txt
¿se puede evolucionar lo existente?
```

La respuesta por defecto debe ser:

```txt
evolucionar
```

no

```txt
reescribir
```

---

## 9. UX antes que nuevas features

A partir de v1.3.4 el principal reto del plugin ya no es técnico.

Es:

```txt
UI
UX
organización
claridad
```

Antes de agregar nuevas funcionalidades:

- evaluar impacto en UX
- evaluar complejidad administrativa
- evaluar escalabilidad

---

## 10. PR únicamente con fase cerrada

No abrir Pull Request si:

- falta QA
- falta validación
- la funcionalidad está a medias

Objetivo:

```txt
Feature branch
↓
QA aprobado
↓
Commit
↓
PR
↓
Merge
```

---

## 11. Priorizar producto sobre implementación

El objetivo ya no es solamente integrar Mailjet.

El objetivo es construir un plugin público y fácil de usar.

Las decisiones futuras deben favorecer:

- claridad
- mantenibilidad
- adopción por terceros
- experiencia de usuario

por encima de agregar más configuraciones o más complejidad técnica.

---

## Flujo de referencia

```txt
Plan (alcance + archivos + riesgos + QA)
  ↓
Fase acotada (una responsabilidad)
  ↓
Implementación
  ↓
Checklist QA + validación manual
  ↓
Commit funcional (feat/fix/refactor)
  ↓
[Si aplica] Commit docs (docs)
  ↓
PR solo si la fase está cerrada y validada
```

Documentos relacionados:

- `ACTIVIDADES-FUTURAS.md` — backlog y fases planificadas
- `PRUEBAS-MANUALES.md` — checklists de QA manual
