# Resultado verificable de construcción — SIGTA

**Fecha:** 20/11/2026  
**Entorno de validación:** Linux, PHP CLI 8.4.24, Node.js 22, Graphviz, Poppler.  
**Destino propuesto:** Laragon Windows / PHP 8.2+ / MySQL 8.0.30+.

| Verificación | Resultado observado | Estado |
|---|---|---|
| Lint de archivos PHP | 29 scripts comprobados con `php -l`, sin errores | OK |
| Pruebas unitarias PHP | 7 casos ejecutados, 7 aprobados | OK |
| Auditoría estática de SQL | 39 tablas referenciadas y restricciones críticas declaradas; no sustituye ejecución MySQL | OK estático |
| Diccionario DDL | 39 tablas, 206 atributos detectados en SQL | OK estático |
| JavaScript | `node --check public/assets/js/app.js` sin errores | OK |
| Enrutador `/login` | HTTP 200, 2.127 bytes de HTML | OK |
| `/salud` | HTTP 200, JSON con servicio y fecha | OK |
| CSS y JS | HTTP 200 para ambos archivos | OK |
| Acceso anónimo a `/postulaciones` | HTTP 303 hacia login | OK |
| Acceso anónimo a `/admin/usuarios` | HTTP 303 hacia login | OK |
| PDF de acta de prueba | Encabezado `%PDF-1.4`, una página carta, `pdfinfo` y `pdftotext` leen el contenido | OK |
| Instalación y carga MySQL 8 | No se probó: el contenedor de construcción no dispone de `pdo_mysql` ni servidor MySQL | PENDIENTE |
| CP01 roles/IDOR con cuentas reales de laboratorio | No ejecutado sin MySQL | PENDIENTE |
| CP02 regla de Tutor/Tribunal y dictámenes | No ejecutado sin MySQL | PENDIENTE |
| CP03 cruces de agenda / CSRF / SQL con BD | No ejecutado sin MySQL | PENDIENTE |
| Revisión reglamentaria por universidad | No se proporcionó reglamento institucional firmado | PENDIENTE |
| Certificación 5FN y pentest | No ejecutados; ver protocolo de normalización y seguridad | PENDIENTE |

**Interpretación:** «OK» solo cubre la prueba concreta indicada. No debe interpretarse como certificación funcional integral ni aprobación para producción.

## Próxima validación en destino

1. Ejecutar `php bin/check.php` y habilitar `pdo_mysql` en Laragon.
2. Crear `sigta`, ejecutar `php bin/setup.php` (base vacía), luego `php bin/demo_data.php`.
3. Ejecutar `php tests/integration_mysql.php` y `php bin/export_dictionary.php`.
4. Registrar CP01, CP02 y CP03 con resultados obtenidos y capturas genuinas.
5. Corregir defectos identificados antes de etiquetar un release de entrega.
