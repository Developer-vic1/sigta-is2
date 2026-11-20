# Auditoría inicial — 20/11/2026

## Punto de partida

Remoto Developer-vic1/sigta-is2 sin referencias; main local sin commits. Código ya extraído en el workspace. No se localizaron BASE_CODIGO, el ZIP original, PROMPT_MAESTRO_SIGTA_CODEX.md, ROLES_INGRESO_SIGTA.txt ni el checklist independiente dentro del proyecto. Se recibió el prompt completo como Texto pegado.txt y seis imágenes (dos repetidas), es decir cinco vistas distintas. No se afirma haber comparado diez referencias.

No existe .env. PHP 8.4.13, pdo_mysql y MySQL 8.0.30 disponibles; conexión local responde, base sigta inexistente. DocumentRoot previsto public/. No hay .codex en el inventario; se excluye globalmente por nombre antes de importar.

## Baseline antes de modificar código

- php -v: 8.4.13.
- php -l de app/bin/public/tests: todos correctos.
- php tests/run.php: 7 OK, 0 fallos.
- php bin/check.php: extensiones y almacenamiento correctos; falta .env.
- python tests/schema_static.py: 39 tablas; FKs e índices presentes. No certifica 5FN.
- php tests/integration_mysql.php: ERROR 1049 Unknown database sigta, registrado como pendiente.

## Inventario y brechas

| Módulo / ruta | Fuente y tablas | Estado inicial / riesgo | Entrega |
|---|---|---|---|
| Login, perfil, usuarios | Auth, Security, AuthController, AdminController; users/roles/user_roles/staff_careers | Parcial: RBAC y CSRF; manejo de errores y privilegios técnicos a revisar | HU01 seguridad y cuentas |
| Dashboard / | PortalController; applications/notifications | Parcial: métricas calculadas sobre lista limitada a 100 y símbolos heterogéneos | HU02 consultas completas y UI |
| /admin/convocatorias | AdminController; calls/call_modalities/career_regulations | Parcial: creación, sin borrador/cupos/edición | HU03 CRUD validado |
| /postulaciones y /postulaciones/{id} | Academic, AcademicController; applications/requirement_verifications | Parcial: borradores DB y transición; sin wizard/autoguardado | HU04 flujo por modalidad |
| /documentos/{id}/descargar | Documents, DocumentController; documents/document_versions/document_reviews | Parcial: privado/MIME/hash; falta concordancia extensión y control de concurrencia posterior a carga | HU05 versiones seguras |
| Designación y evaluaciones | Academic; assignments/rubrics/evaluations/evaluation_scores | Parcial: incompatibilidad DB; falta carga máxima y evaluación inmutable | HU06 docentes y rúbricas |
| /defensas | Defense, DefenseController; rooms/defenses/defense_scores | Parcial: GET_LOCK y solapamiento; calendario propio ausente; validación previa al bloqueo | HU07 agenda y concurrencia |
| Actas, resolución, empastado | Defense, Excellence, Letters, Pdf; acts/resolutions/binding_authorizations | Parcial: PDF simulado; prueba real pendiente y permisos admin excesivos | HU08 cierre seguro |
| /reportes, /notificaciones | AdminController; SQL y audit_logs | Parcial: cifras reales; sin CSV ni filtros/paginación | HU09 consulta y exportación |
| Sistema visual compartido | Layout.php, app.css, app.js | Parcial: responsive básico; sin drawer/modal accesible, tema, búsqueda global y transiciones propias | HU10 diseño accesible |

Router.php declara las rutas con permisos en controladores; servicios aplican ámbito de expediente. El esquema inicial declara 39 tablas con FKs, unicidad de asignación activa y CHECK; la normalización formal permanece pendiente. No se reemplazará el monolito ni se agregarán dependencias de compilación.

Prioridad: instalar BD local aislada, comprobar flujo real y corregir seguridad; después componentes compartidos y módulos. Cada resultado se registrará con pruebas ejecutadas; funciones incompletas se marcarán como tales. El usuario autorizó publicar esta base como versión 1 antes de las mejoras.
