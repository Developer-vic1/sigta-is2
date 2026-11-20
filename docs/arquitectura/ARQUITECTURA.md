# Arquitectura de software — SIGTA

## Decisión arquitectónica ADR-001

**Estilo:** monolito modular en capas, con MVC en el borde HTTP y servicios de dominio (workflow académico) desacoplados de vistas; base de datos relacional MySQL. Alternativas evaluadas: microservicios (sobrecarga operativa injustificada), SPA+API (doble despliegue innecesario para el prototipo), monolito sin separación (menor mantenibilidad). Se selecciona MVC modular por facilidad de aprendizaje, prueba, mantenimiento, bajo presupuesto y despliegue con Laragon.

## Capas y responsabilidades

1. **Presentación:** `app/Views/Layout.php`, CSS y JS local. Formularios accesibles, navegación por rol; JS mejora progresiva, pero no decide permisos.
2. **Entradas:** `public/index.php` y `app/Core/Router.php`. GET/POST, tokens CSRF y control de errores.
3. **Controladores:** seis controladores que extraen parámetros, verifican rol y orquestan servicios.
4. **Dominio:** `app/Services/Academic.php`, `Documents.php`, `Defense.php`, `Excellence.php`, `Pdf.php`. Estados, reglas, ponderación y generación de documentos.
5. **Infraestructura:** `Db.php` usa PDO real, sentencias preparadas, transacciones; `storage/private` para ficheros, MySQL para datos.
6. **Tareas periódicas:** `bin/expire.php` ejecutado desde Programador de tareas de Windows/cron externo.

## Modelo de despliegue

Navegador → Apache (VirtualHost con DocumentRoot `public/`) → PHP 8.2+ → MySQL 8 y almacenamiento privado. No se requiere un servidor externo para las rutas. En producción: proxy HTTPS, certificados, backups, monitoreo, política de retención y controles de acceso a nivel de infraestructura.

## Workflows por estado

- Base: `borrador → presentada → aprobada → en_desarrollo → habilitada → programada → concluida`.
- Alternativas: `presentada → observada → presentada`, `presentada → rechazada`, `aprobada/en_desarrollo/habilitada → caducada`.
- Excelencia Académica: `borrador → presentada → aprobada → concluida` por resolución, sin defensa.
- Examen de Grado: `borrador → presentada → aprobada → en_desarrollo → habilitada → programada → concluida` con dos tribunales según configuración del laboratorio.

Transiciones admitidas deben depender del estado, rol y documentos. Los estados finales (`concluida`, `rechazada`, `caducada`) no permiten cargas normales de documentos. Se registran eventos críticos de auditoría.

## Coherencia y concurrencia

- `Db::transaction` evita estados parciales durante postulación, asignaciones, evaluaciones, cierre y emisión.
- Restricciones DB `uq_active_teacher` y `uq_active_app_role` protegen unicidad de docentes/plazas activas.
- Para programar defensas se usa un bloqueo nominal MySQL `GET_LOCK('sigta:defense:scheduling',10)`, consulta de solapamientos e inserción en transacción. Con muchos nodos esta estrategia deberá medirse y reemplazarse por planificación escalable.
- Para versiones documentales se bloquea la fila `applications` con `FOR UPDATE` y se incrementa la versión; el archivo se guarda fuera del directorio público y se registra SHA-256.
- Cierre de defensa exige notas individuales suficientes y pesos de modalidad que sumen 100%; el acta representa una instantánea histórica del resultado.

## Cambios futuros

1. Políticas reglamentarias versionadas por carrera/gestión, no solo flags de modalidad.
2. Flujo de firmas electrónicas institucionales y verificación externa de actas.
3. Detección de similitud real en documentos con resguardo de datos.
4. Envío de correo mediante cola y registros de entrega, con consentimiento.
5. Recuperación de contraseñas, MFA, accesibilidad WCAG validada, pruebas de carga y backups con recuperación.

## Arquitectura y 5FN

La estructura relacional separa datos maestros, relaciones muchos-a-muchos y registros históricos. Algunas proyecciones inmutables (acta con nota final y documento renderizado) son hechos de emisión, no fuentes primarias; no deben recalcularse retroactivamente. Véase `docs/datos/NORMALIZACION_5FN.md`.
