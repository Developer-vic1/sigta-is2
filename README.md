# SIGTA — Sistema Integral de Gestión de Titulación Académica

**Proyecto 07 | Ingeniería de Software 2 | UNIFRANZ (caso académico simulado) | 2026**

SIGTA es un prototipo web original para gestionar la inscripción de modalidad de graduación, requisitos, expedientes, documentos versionados, revisiones, tutores, tribunales, rúbricas, hitos, defensas, calificaciones, actas y resoluciones de Excelencia Académica. **No es software oficial de UNIFRANZ**. No usar datos estudiantiles reales ni emitir actas con valor institucional hasta completar validación legal, reglamentaria y de seguridad.

## 1. Requisitos de instalación

- Windows 10/11 con **Laragon**, PHP **8.2+** (extensiones `pdo_mysql`, `fileinfo`, `openssl`, `iconv`) y **MySQL 8.0.30+**. También funciona en Linux con PHP/Apache/MySQL compatibles.
- `mod_rewrite` habilitado si utilizas Apache, acceso al directorio `storage/private` fuera del DocumentRoot.
- No se necesita Composer, Node ni internet para el funcionamiento base; CSS y JavaScript están incluidos.

## 2. Instalación desde cero en Laragon — paso a paso

1. Descomprime `sigta-is2` dentro de `C:\laragon\www\sigta-is2`.
2. Abre Laragon, inicia **Apache** y **MySQL**.
3. En **MySQL Workbench** o HeidiSQL, abre `database/00_create_database.sql` y ejecútalo; crea la base de datos `sigta`.
4. Copia `.env.example` a `.env`. Configura `DB_HOST`, `DB_PORT`, `DB_DATABASE=sigta`, `DB_USERNAME` y `DB_PASSWORD`. El instalador generará una `APP_KEY` secreta si está vacía.
5. Abre la terminal de Laragon en la raíz del proyecto y ejecuta:

   ```powershell
   php bin/check.php
   php bin/setup.php
   ```

   Ingresa el correo y una contraseña fuerte para el primer administrador. **No existen cuentas de administrador predeterminadas.** El instalador es solo para una base vacía; no intenta borrar esquemas existentes.
6. Para cargar usuarios y convocatorias **ficticios de laboratorio** (opcional):

   ```powershell
   php bin/demo_data.php
   ```

   Genera claves aleatorias nuevas y las muestra una sola vez en consola. Guárdalas solo para tu laboratorio y no las incluyas en GitHub.
7. Configura un VirtualHost `sigta.test` en Apache con `DocumentRoot C:/laragon/www/sigta-is2/public` y tu entrada local `hosts`, o utiliza el servidor integrado:

   ```powershell
   php -S 127.0.0.1:8000 -t public public/router.php
   ```

   Accede a `http://127.0.0.1:8000/login` (en esta alternativa, actualiza `APP_URL` si lo deseas). Para Apache con VirtualHost accede a `http://sigta.test/login`.
8. Ingresa como administrador, crea cuentas o utiliza las demo. Crea una convocatoria si no cargaste los datos ficticios. Prueba los flujos descritos en `docs/pruebas/PLAN_PRUEBAS.md`.

**Importante:** establece el DocumentRoot en `public/`. **Nunca apuntes Apache a la carpeta raíz del repositorio**, porque expondrías `.env`, `storage/private` y scripts CLI.

## 3. Ejecución diaria

- En Laragon: iniciar MySQL y Apache; abrir la dirección del proyecto.
- Para simular caducidad reglamentaria:
  ```powershell
  php bin/expire.php
  ```
  Para automatizarlo, configura el **Programador de tareas de Windows** (por ejemplo, diariamente) ejecutando `php.exe` con argumento `C:\laragon\www\sigta-is2\bin\expire.php`. Se recomienda un job diario; el sistema no puede ejecutar cron por sí solo.
- Verificar código: `php tests/run.php` y `php bin/check.php`.
- Verificar salud del enrutador: GET `/salud` (solo disponibilidad del proceso HTTP; no valida MySQL ni seguridad integral).

## 4. Roles y recorrido completo

| Rol | Operaciones |
|---|---|
| Admin técnico | Registrar cuentas, roles iniciales, convocatoria, consulta global y auditoría |
| Dirección de Carrera | Aprobar/rechazar perfil, designar docente, crear hitos, programar defensas y gestionar convocatorias de sus carreras |
| Secretaría Académica | Verificar requisitos, revisar expedientes, programar defensa, emitir acta y ver estadísticas de sus carreras |
| Estudiante | Elegir modalidad, crear expediente, subir documentos/versiones, presentar, ver estados, descargar acta |
| Tutor | Revisar avances, generar hitos, emitir dictamen y nota final si está asignado |
| Tribunal | Dictamen con rúbrica y nota final de defensa si está asignado |

**Recorrido Tesis / Proyecto / Trabajo Dirigido:** crear convocatoria → estudiante crea expediente → adjunta CI/KARDEX/PERFIL → presenta → Secretaría verifica requisitos → Dirección revisa última versión de PERFIL y aprueba postulación → asigna un Tutor y dos Tribunales → tutor crea hitos → estudiante entrega AVANCE y FINAL → tutor/tribunales registran dictámenes suficientes → Secretaría programa defensa sin cruces → evaluadores registran notas → Secretaría genera acta PDF académica.

**Examen de Grado:** carece de perfil y tutor por configuración inicial; se designan dos tribunales y se programa defensa/evaluación según reglas de demostración.

**Excelencia Académica:** no requiere perfil, tutor, tribunal ni defensa por la configuración de laboratorio. Tras verificar requisitos, Dirección aprueba la postulación y emite resolución PDF **no oficial** con fundamento y promedio ingresado manualmente. Las exigencias reglamentarias reales pueden variar.

## 5. Base de datos y normalización

- Inicialización: `database/00_create_database.sql`, `database/migrations/001_schema.sql`, `002_constraints.sql`, `database/seeds/001_catalog.sql`.
- Entidades y claves: `docs/datos/DICCIONARIO_PRELIMINAR.md` y `.csv`; **son documentación derivada del SQL, no una exportación real de MySQL**. Para el cuarto hito, exporta el diccionario directamente desde MySQL Workbench una vez instalado.
- Justificación 1FN–5FN, dependencias funcionales y límites: `docs/datos/NORMALIZACION_5FN.md`. La 5FN no se certifica solamente contando tablas; requiere verificar dependencias de unión y reglas del dominio.
- Recomendamos exportar un backup de `sigta` desde MySQL Workbench antes de realizar cambios.

## 6. Documentación de ingeniería incluida

| Ruta | Contenido |
|---|---|
| `docs/REQUISITOS_Y_TRAZABILIDAD.md` | Alcance, RF/RNF, reglas, 8 HU, aceptación y rutas |
| `docs/arquitectura/ARQUITECTURA.md` | N capas, MVC, workflow, despliegue, decisiones técnicas |
| `docs/diagramas/` | PlantUML: casos de uso, MER, arquitectura, secuencia y mapa navegacional |
| `docs/datos/NORMALIZACION_5FN.md` | Evaluación razonada del modelo relacional |
| `docs/datos/DICCIONARIO_PRELIMINAR.*` | Tabla/columna/tipo/clave/descripción provisional |
| `docs/seguridad/SEGURIDAD.md` | Controles implementados, modelo de amenazas, pendientes |
| `docs/uiux/GUIA_UI_UX.md` | Diseño, roles, componentes y accesibilidad |
| `docs/pruebas/PLAN_PRUEBAS.md` | Pruebas automáticas y funcionales por ejecutar |
| `docs/scrum/` | Backlog y guía para evidencias SCRUM auténticas |
| `docs/informe/PLANTILLA_4TO_HITO.md` | Estructura oficial de cuatro capítulos |

## 7. Características de seguridad implementadas

- `password_hash` con Argon2id si existe, fallback bcrypt; `password_verify`; actualización automática de hash al iniciar sesión.
- Consultas preparadas PDO sin emulación; formularios con token CSRF por sesión.
- Cookies HttpOnly, SameSite=Lax; cookies Secure y HSTS cuando se ejecuta directamente sobre HTTPS; sesión regenerada al ingresar y periódicamente; inactividad de 45 min.
- Roles y alcance por carrera; acceso a expedientes por titular o docente asignado.
- Almacenamiento privado, tamaño y MIME verificados, nombres aleatorios, control de versiones y hash SHA-256 por archivo.
- Validaciones de reglas académicas en servidor; unicidad de asignaciones activa en DB; bloqueo MySQL de agenda en operación de programación.
- Límite de intentos de inicio de sesión; auditoría de cambios, IP seudonimizada mediante HMAC y notificaciones.
- CSP restrictiva, encabezados contra MIME sniffing / clickjacking, HTML escapado en vistas.

## 8. Consideraciones de producción

**Este ZIP es un prototipo académico, no un producto certificado para producción.** Antes de usarlo con datos reales se necesita: despliegue en HTTPS, revisión de reglamentos y de protección de datos, pruebas reales en MySQL 8, pentest, monitoreo, backups automáticos con restauración probada, recuperación de contraseñas segura, políticas institucionales de retención, firmas digitales verificables, pruebas de concurrencia en servidores reales y despliegue en entorno auditado. El PDF incluido se marca **simulación académica** y no representa un acta válida.

**Funcionalidades de alcance limitado:** la similitud temática solo compara títulos mediante `similar_text` (no detecta plagio ni compara contenidos); las notificaciones se muestran dentro del sistema, no se envían por correo/WhatsApp; la designación automática considera cargas y especialidad, pero no disponibilidad horaria ni aprobación contractual; los flujos exactos de cada modalidad son **configuraciones de laboratorio**, no reglamentos oficiales.

## 9. GitHub y SCRUM

Repositorio sugerido: `Developer-vic1/sigta-is2` (a crear por el usuario). Ramas `main` (estable), `develop` (integración), `feature/*` (módulos). Nunca subir `.env`, credenciales, documentos reales ni archivos de almacenamiento. El código suministra una base para construir y registrar evidencias reales del proceso SCRUM, no capturas ficticias de sprints.

## 10. Estado de validación del paquete

`php -l` sobre todos los scripts PHP y `php tests/run.php` son los controles ejecutables sin MySQL. El estado de las verificaciones realizadas durante la construcción se especifica en `docs/pruebas/RESULTADOS_VERIFICACION.md`. Los flujos de integración requieren instalación MySQL de destino y **no se presentan como probados si no se han ejecutado**.

**Licencia sugerida:** MIT, por definir antes de publicar. No se incorpora escudo/logotipo institucional ni datos personales reales.

## 11. Documentos adicionales del Proyecto 7

- **Cartas de designación:** PDF de demostración descargable desde cada asignación de docente.
- **Empastado autorizado:** se emite PDF con huella SHA-256 de documento final una vez que los dictámenes y requisitos estén aprobados; no es autorización universitaria oficial.
- **Reglamentos versionados:** Dirección registra texto y fecha de vigencia por carrera; son referencias administrativas y **todavía no funcionan como un motor de normas ejecutables**.
- **Alertas de caducidad:** `bin/expire.php` puede enviar avisos internos a 15, 7 y 1 día del límite si se ejecuta como tarea periódica. No envía correo externo.
- **GitHub:** comandos y ramas en `docs/GITHUB_Y_VERSIONES.md`.
