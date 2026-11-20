# Modelo de amenazas, seguridad y validaciones — SIGTA

## Activos protegidos

Credenciales, identidad estudiantil, registros académicos, documentos, dictámenes, calificaciones, actas, historial de auditoría y configuración de convocatorias.

## Controles implementados

| Riesgo | Control y ubicación | Verificación recomendada |
|---|---|---|
| Robo de claves desde BD | `password_hash` Argon2id o bcrypt y `password_verify` | Comprobar columna `password_hash` sin contraseñas en texto plano |
| Inyección SQL | PDO `ATTR_EMULATE_PREPARES=false` y parámetros enlazados | Enviar cadenas como `' OR 1=1 --` en campos de búsqueda/login |
| CSRF | Token secreto por sesión; POST requiere `_csrf` | POST sin `_csrf` devuelve error |
| XSS reflejado/almacenado | Escape HTML (`htmlspecialchars ENT_QUOTES`) en vistas | Registrar texto `<script>` en tema y comprobar que no se ejecuta |
| Acceso indebido/IDOR | `Academic::requireView`, permisos por rol/carrera | Cambiar ID de expediente o URL como otro usuario |
| Archivos expuestos | `storage/private` fuera de `public`, descargas autorizadas | Intentar acceso directo `/storage/private/...` → 404 |
| Upload malicioso | `finfo` MIME, tipos permitidos, límite tamaño, nombre aleatorio | Probar `.php`, `.svg`, PDF inválido y tamaño superior |
| Doble asignación docente | Servicio y claves únicas generadas en DB | Mismo docente tutor y tribunal debe rechazarse |
| Defensa con cruce | Bloqueo MySQL + cruces de sala/docentes | Crear dos defensas simultáneas con participantes o sala comunes |
| Ataques de fuerza bruta | Conteo de fallos por hash de email/IP (15 min) | 8 intentos fallidos bloquean temporalmente |
| Fijación de sesión | `session_regenerate_id(true)` en login, rotación y cierre | Verificar cambio de ID al autenticar |
| Exposición en HTTP | HttpOnly, SameSite y CSP, HTTPS recomendado | Revisar headers y cookies con navegador |
| Manipulación de documento | SHA-256 y registro inmutable de versiones | Recalcular checksum y comparar con BD |
| Falta de trazabilidad | `audit_logs` y notificaciones | Revisar evento y actor tras acciones sensibles |

## Endurecimiento pendiente antes de producción

- TLS/HTTPS obligatorio, proxy confiable y política robusta de sesiones.
- Recuperación de cuentas con enlaces de un solo uso, MFA y políticas de bloqueo/desbloqueo.
- Control granular de administración de roles y revocación, gestión de ciclo de vida de cuentas.
- Pruebas reales de `upload_max_filesize`, antivirus, sanitización PDF y prevención de cargas especiales.
- Cifrado en reposo, retención/borrado de datos académicos y backups cifrados.
- Protección de logs contra alteración por administradores del DB, registro de errores centralizado y monitoreo.
- Pentest independiente, pruebas DAST/SAST y evaluación de cumplimiento legal/reglamentario.
- Política de respaldo/restauración, pruebas de carga, disponibilidad y respuesta a incidentes.
- Documento PDF oficial requiere firma institucional verificable. El PDF de prueba no tiene validez jurídica.

## Uso de datos

Crear exclusivamente identidades de prueba. No publicar `.env`, claves, expedientes ni dumps con datos personales. Los documentos PDF generados están rotulados «prototipo académico». La detección de similitud basada solo en títulos no debe usarse para sanciones ni denuncias de plagio.
