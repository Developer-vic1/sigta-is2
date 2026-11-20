# Requerimientos y trazabilidad — SIGTA

## Fuente y alcance

Adaptación fiel de la consigna «Proyecto 7: Plataforma integral de gestión de titulación, modalidades de graduación y tribunales académicos» del cuarto hito de Ingeniería de Software 2. Destino: **prototipo académico** para escenario simulado de universidad privada en La Paz. Cinco modalidades: Tesis, Proyecto, Trabajo Dirigido, Examen, Excelencia Académica. Datos personales exclusivamente ficticios para pruebas.

## Actores

Estudiante, Tutor, Tribunal Evaluador, Dirección de Carrera, Secretaría Académica. Se añade Administrador técnico para provisionamiento y seguridad.

## Historias de usuario — 8 principales

| HU | Formulación | Criterios de aceptación principales | Módulos |
|---|---|---|---|
| HU01 | Como estudiante, quiero registrar una postulación en una convocatoria y modalidad vigente para iniciar mi proceso. | La carrera coincide; la convocatoria está abierta; una postulación por estudiante/convocatoria; título >=8 caracteres. | M02,M03 |
| HU02 | Como estudiante, quiero subir y versionar documentos para completar mi expediente de manera trazable. | PDF/JPG/PNG validado, <=8 MB por defecto; registro SHA-256; nueva versión; descarga solo autorizada. | M04 |
| HU03 | Como Secretaría, quiero verificar requisitos para impedir que una postulación incompleta sea aprobada. | Requisito con documento; responsable, estado y fecha; carga nueva exige re-verificación. | M03,M04 |
| HU04 | Como Director, quiero revisar el perfil y designar docentes para aprobar expedientes y distribuir la carga académica. | Se valida última versión revisada; un docente no puede ser Tutor y Tribunal del mismo caso; plazas limitadas. | M04,M05 |
| HU05 | Como Tutor o Tribunal, quiero registrar hitos, calificaciones de rúbrica y dictámenes para documentar el progreso. | Rúbrica pondera 100%; notas 0–100; dictamen vinculado a docente asignado. | M06 |
| HU06 | Como Secretaría, quiero programar defensas para coordinar sala y participantes sin conflictos. | Exige suficiencia Tutor+dos Tribunales según modalidad; documento final si corresponde; sin choques de sala/docente. | M07 |
| HU07 | Como evaluador y Secretaría, quiero registrar notas y emitir el acta de defensa para cerrar el expediente. | Todas las notas; pesos por modalidad suman 100%; PDF trazable de demostración. | M07,M08 |
| HU08 | Como Dirección, quiero controlar convocatorias, plazos, auditoría y reportes para administrar la titulación. | Configuración de fechas y modalidades; caducidad ejecutada mediante tarea; métricas por carrera. | M02,M09,M10 |

## Requisitos funcionales — matriz resumida

| ID | Requisito | Ubicación de implementación |
|---|---|---|
| RF01 | Iniciar/cerrar sesión por credenciales | `AuthController`, `Auth` |
| RF02 | Gestión de usuarios y roles | `AdminController::users/createUser`, tablas `users`, `roles`, `user_roles` |
| RF03 | Control de carreras y modalidades | tablas `careers`, `modalities`, `career_modalities` |
| RF04 | Convocatorias con modalidades y plazos | `AdminController::calls`, `createCall` |
| RF05 | Postulación única y validación de convocatoria | `Academic::create` |
| RF06 | Requisitos por modalidad | `modality_requirements`, `requirement_verifications` |
| RF07 | Carga privada y versionada de documentos | `Documents::upload` |
| RF08 | Descarga por permisos | `Documents::download` |
| RF09 | Revisiones/observaciones de documentos | `Documents::review` |
| RF10 | Resolución de perfil | `Academic::review` |
| RF11 | Asignación Tutor/Tribunal | `Academic::assign` |
| RF12 | Sugerencia automática por carga/especialidad | `Academic::assignBalanced` |
| RF13 | Prevención de rol doble activo | `assignments` (índices únicos) + servicio |
| RF14 | Hitos de avance | `AcademicController::milestone` |
| RF15 | Entregas de avance versionadas | `Documents::upload` y `milestone_submissions` |
| RF16 | Rúbricas y notas de suficiencia | `Academic::evaluate` |
| RF17 | Habilitación de defensa condicionada | `Academic::sufficiency` |
| RF18 | Agenda sin conflictos de aula y docentes | `Defense::schedule` |
| RF19 | Calificación final por evaluador | `Defense::score` |
| RF20 | Ponderación de actas | `Defense::finalize` |
| RF21 | Actas académicas PDF de prueba | `Pdf::make`, `Defense::downloadAct` |
| RF22 | Resolución de excelencia sin defensa | `Excellence::close` |
| RF23 | Notificaciones internas | `Academic::notify`, `AdminController::notifications` |
| RF24 | Reportes de carga y estados | `AdminController::reports` |
| RF25 | Bitácora de acciones relevantes | `Security::audit` |
| RF26 | Caducidad por plazo | `bin/expire.php` (requiere scheduler externo) |
| RF27 | Orientación de similitud temática | `Academic::similarity` — solo títulos y heurística |
| RF28 | Cambio de contraseña | `AuthController::password` |

## Requisitos no funcionales

| ID | Atributo | Condición verificable |
|---|---|---|
| RNF01 | Seguridad de contraseñas | Hash irreversible Argon2id/bcrypt, nunca texto plano en BD |
| RNF02 | Autorización | Usuario estudiante solo ve sus expedientes; docente solo asignados; gestión limitada por carrera |
| RNF03 | Integridad | Claves foráneas, unicidad de asignaciones, check de estado/nota, transacciones en operaciones compuestas |
| RNF04 | Privacidad | Documentos fuera de DocumentRoot y descargas autenticadas |
| RNF05 | Seguridad HTTP | CSRF en POST, escape de HTML, consultas preparadas, encabezados de seguridad |
| RNF06 | Trazabilidad | Versiones, SHA-256, auditoría y marcas temporales |
| RNF07 | Usabilidad | UI responsiva, formularios etiquetados, navegación por rol, tablas filtrables, mensajes de validación |
| RNF08 | Portabilidad | Windows/Laragon y Linux PHP/MySQL sin frameworks externos obligatorios |
| RNF09 | Concurrencia | Bloqueo de agenda para evitar colisión en programaciones concurrentes |
| RNF10 | Mantenibilidad | MVC modular y servicios, documentación SQL y plantillas |
| RNF11 | Disponibilidad | Se propone métrica de disponibilidad; **no fue medida** y no se promete SLA operativo |
| RNF12 | Rendimiento | Se propone medir P95 de consultas, cargas y generación PDF en servidor real |

## Reglas de negocio críticas

- **RN01:** Docente no puede ocupar simultáneamente más de una asignación activa en mismo expediente. Restricción DB mediante `uq_active_teacher` + validación de servicio.
- **RN02:** Defensa requiere dictamen suficiente de Tutor + dos Tribunales cuando la modalidad exige ambos; Examen exige dos Tribunales según configuración académica simulada.
- **RN03:** Caducidad por plazo sin avance definida en convocatoria, ejecutada por CLI programado.
- **RN04:** No existe doble ocupación de sala ni docente en defensas activas superpuestas.
- **RN05:** Revisión de perfil válida solo sobre la última versión cargada.
- **RN06:** Cambio de documento de requisito revoca estado de verificación previa.
- **RN07:** Expediente no puede finalizar defensa hasta contar con todas las notas del tribunal/tutor exigido.
- **RN08:** Documentos PDF generados en este prototipo incluyen texto explícito de simulación; carecen de firma digital institucional.

## Límites conocidos del dominio

Los reglamentos específicos de cada carrera, ponderaciones definitivas, número de evaluadores en Examen de Grado, condiciones de Excelencia Académica y validez de actas **no constan en la consigna**. El sistema trae valores de DEMOSTRACIÓN y debe ser parametrizado y aprobado por la entidad competente antes de usarlo en realidad.

## Extensiones vinculadas con el enunciado

| Requisito | Implementación |
|---|---|
| RF29 | Carta de designación de Tutor/Tribunal en PDF de demostración: `Letters::assignment` |
| RF30 | Autorización de empastado sujeta a requisitos, documentos y suficiencia: `Letters::binding` |
| RF31 | Registro versionado de reglamento por carrera: `career_regulations`, `AdminController::createRegulation` (sin motor de reglas) |
| RF32 | Avisos de vencimiento a 15, 7 y 1 día mediante `bin/expire.php` programado externamente |
