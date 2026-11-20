# EVALUACIÓN PROCESUAL GRUPAL — 4TO HITO

**Universidad Privada Franz Tamayo — Ingeniería de Software 2**  
**Caso:** Proyecto 7 — Plataforma Integral de Gestión de Titulación, Modalidades de Graduación y Tribunales Académicos.  
**Prototipo:** SIGTA — Sistema Integral de Gestión de Titulación Académica.  
**Equipo SCRUM:** [Completar integrantes y roles reales].  
**Docente:** Ing. Álvaro Gabriel Coronel Centellas.  
**La Paz – Bolivia | Gestión 2026.**

> Preparar versión Word/PDF final respetando el PDF de referencia: carta, Arial 12 negro, tablas Arial 10, pies de figuras Arial 9, portada centrada, índice, numeración y referencias. Esta plantilla en MD no pretende reemplazar ese formato final.

## 1. INTRODUCCIÓN Y PLANTEAMIENTO (30 minutos)

**Problema y contexto:** Las postulaciones a modalidades de graduación pueden depender de expedientes fragmentados, correos y verificaciones manuales. En el escenario académico simulado, ello retrasa revisiones, dificulta asignaciones y puede generar conflictos de defensa.

**Objetivo general:** Diseñar y desarrollar durante 2026 una plataforma web académica para centralizar y controlar el ciclo de titulación de una universidad privada de La Paz, mediante arquitectura modular MVC, MySQL y metodología SCRUM, para mejorar la trazabilidad documental, la asignación de responsables y la gestión de defensas.

**Objetivos específicos (exactamente tres):** (1) Analizar requerimientos y arquitectura mediante ocho historias de usuario y reglas de negocio verificables; (2) diseñar/desarrollar un prototipo web y su base relacional para tramitar postulaciones, documentos, dictámenes y defensas; (3) ejecutar y documentar al menos tres pruebas funcionales y de seguridad para validar permisos, integridad académica y comportamiento ante errores.

**Justificación:** La centralización reduce duplicidades y permite auditar acciones. El diseño en capas facilita correcciones; las restricciones de BD y servicios reducen inconsistencias. Los resultados de ahorro y mejora son **beneficios esperados**, no medidos hasta ejecutar pruebas comparativas.

## 2. MARCO TEÓRICO Y METODOLOGÍA (25 minutos)

| Componente | Selección | Justificación |
|---|---|---|
| Backend | PHP 8.2+ | Compatible con Laragon y servicios modulares |
| Persistencia | MySQL 8 | Integridad referencial, transacciones, restricciones |
| Presentación | HTML/CSS/JS | Interfaz ligera, sin CDN, accesible |
| Arquitectura | MVC modular + N capas | Flujos y responsabilidades separadas |
| Metodología | Scrum | Entregas iterativas con revisión por historias |
| Versionamiento | Git/GitHub | Trazabilidad de tareas y cambios |

**SCRUM:** Integrantes y roles reales: [completar]. Backlog/Sprints: `docs/scrum/`. Capturas y actas: [adjuntar después de ejecutar]. No presentar las actividades planificadas como si ya hubieran sido realizadas.

## 3. DESARROLLO DEL PROYECTO — NÚCLEO (65 minutos)

### 3.1 Requisitos (15 minutos)

Adjuntar 8 HU y criterios de aceptación desde `docs/REQUISITOS_Y_TRAZABILIDAD.md`. Priorizar RN01 doble asignación, RN02 suficiencia y RN03 caducidad.

### 3.2 Diseño técnico (30 minutos)

**Arquitectura seleccionada:** monolito modular PHP MVC + capas. La interfaz depende de controladores, los controladores de servicios de dominio y los servicios de un repositorio relacional MySQL. Estado y workflow en servidor. Justificación en `docs/arquitectura/ARQUITECTURA.md`.

**Diagramas exigidos:**
1. Casos de uso → `docs/diagramas/01_casos_uso.png` / `.puml`.
2. Modelo entidad-relación → `docs/diagramas/02_erd.png` / `.puml`.
3. Arquitectura de software → `docs/diagramas/03_arquitectura.png` / `.puml`.

### 3.3 Calidad y costos (20 minutos)

**Presupuesto estimado ilustrativo en Bs., ajustar a las horas del equipo:**

| Rol / rubro | Horas o unidad | Tarifa Bs. | Subtotal Bs. |
|---|---:|---:|---:|
| Product Owner | 8 h | 45 | 360 |
| Scrum Master | 8 h | 45 | 360 |
| Backend + DB | 35 h | 45 | 1.575 |
| Frontend/UI | 25 h | 45 | 1.125 |
| QA/seguridad | 12 h | 40 | 480 |
| Documentación y UML | 12 h | 40 | 480 |
| Infraestructura académica | 1 unidad | 120 | 120 |
| **Total** | | | **4.500** |

**Tres pruebas documentadas a completar:** CP01 roles/IDOR, CP02 incompatibilidad y habilitación, CP03 CSRF/agenda. Ver `docs/pruebas/PLAN_PRUEBAS.md` y registrar resultados **obtenidos reales**.

## 4. CIERRE Y RESPALDOS (30 minutos)

**Tres conclusiones esperadas, ajustar tras ejecutar:** (1) el prototipo integra un flujo de postulación documentada y trazable; (2) MVC modular permite separar autorizaciones, reglas académicas y datos; (3) la validación real exige pruebas MySQL y contraste con reglamentos institucionales.

**Bibliografía base (3–5):** The Scrum Guide (Schwaber & Sutherland, 2020); PHP Manual (PHP Documentation Team); MySQL 8.0 Reference Manual (Oracle); OWASP Cheat Sheet Series (OWASP Foundation). Completar referencias APA 7 con URLs, fechas y ediciones verificadas al entregar.

**Anexos:** capturas **reales** del sistema, respaldos SQL reales, diccionario de datos **exportado de `information_schema` o MySQL Workbench**, enlace o capturas de GitHub Projects, resultados de las tres pruebas y evidencia de commits.
