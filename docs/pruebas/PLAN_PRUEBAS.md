# Protocolo de pruebas — SIGTA

## Niveles

**Estáticas (sin MySQL):** `php -l` sobre todos los archivos PHP; `php tests/run.php` para hashing, validación de claves, escape HTML, validación de IDs y generación PDF. No sustituyen pruebas de integración.

**Integración manual en MySQL 8:** completar los siguientes casos y adjuntar captura y hora real de ejecución. Los campos «Obtenido» y «Estado» quedan **pendientes**, no se inventan.

| Caso | Preparación | Pasos | Esperado | Obtenido / evidencia |
|---|---|---|---|---|
| CP01 Autenticación y roles | Crear admin, estudiante, director y tutor | Intentar entrar con clave errónea; luego válida; acceder a `/admin/usuarios` como estudiante; descargar versión ajena | Inicio válido solo con credenciales; 403/denegado para funciones y archivos ajenos | Pendiente de ejecutar con MySQL |
| CP02 Regla docente y habilitación | Convocatoria, expediente aprobado, tres docentes | Asignar docente Tutor; intentar asignarlo también Tribunal; registrar solo un dictamen y tratar de programar; completar los tres | Doble asignación rechazada; defensa bloqueada antes de suficiencia y permitida al completar requisitos | Pendiente de ejecutar con MySQL |
| CP03 Seguridad y agenda | Dos expedientes aprobados, sala común, perfiles válidos | Programar defensas solapadas; POST sin CSRF; enviar tema con `<script>` | Cruce rechazado; token CSRF obligatorio; script escapado y no ejecutado | Pendiente de ejecutar con MySQL |

## Casos complementarios

- CP04: subir documento con MIME `.php`, `image/svg+xml` o tamaño superior al máximo ⇒ rechazo.
- CP05: reemplazar versión de CI tras requisito aprobado ⇒ regresa a pendiente.
- CP06: Tutor y dos Tribunales registran nota final ⇒ promedio ponderado correcto; acta PDF es descarga autorizada.
- CP07: Examen de Grado sin tutor ⇒ la condición de defensa usa dos tribunales.
- CP08: Excelencia Académica ⇒ cierre por resolución, sin defensa.
- CP09: caducidad ⇒ cambiar `last_progress_at` a una fecha antigua en un entorno de laboratorio; ejecutar `php bin/expire.php`, verificar estado y auditoría.
- CP10: dos peticiones concurrentes en agenda ⇒ sin duplicación de sala/docente.
- CP11: director asignado solo a SIS no puede ver expediente de otra carrera.
- CP12: combinar especialidades y roles ⇒ no duplicar filas de usuarios por join indebido.

## Evidencias por prueba

Captura de pantalla del resultado, nombre y fecha del probador, estado «Aprobado/Rechazado», salida SQL/CLI cuando corresponda, descripción de incidencia, hash o commit Git de la versión. **No marcar pruebas como realizadas sin evidencia real.**

## Preparación de entorno

`php bin/check.php`; `php bin/setup.php`; `php bin/demo_data.php` en APP_ENV=local. Iniciar servidor y completar por rol. Limpiar datos reales de cualquier captura antes de compartir.

- CP13: con dictámenes suficientes y `FINAL`, autorizar empastado; verificar PDF y hash referenciado; sin suficiencia debe rechazar.
- CP14: carta de designación accesible al estudiante y docente del expediente; inaccesible a cuentas externas.
- CP15: registrar regla de carrera desde Dirección; impedir registrar para otra carrera.
- CP16: `bin/expire.php` no duplica alertas del mismo día de plazo al ejecutarse dos veces.
