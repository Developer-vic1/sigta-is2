# Guía UI/UX — SIGTA

## Diseño

Identidad visual propia, profesional, sin copiar escudos o logotipos oficiales. Paleta: azul marino `#142b49`, azul de acción `#245ba8`, fondo gris claro `#f5f7fb`, verde éxito `#147d60`, rojo error `#bb3c49`, texto `#26364c`. Tipografía sistema con fallbacks; no descarga fuentes externas. CSS responsiva sin frameworks externos obligatorios.

## Principios

- Navegación lateral persistente (deslizable en móviles), encabezado de sección, tarjetas descriptivas.
- Menús por rol; datos académicos siempre vinculados a expediente.
- Colores de estado acompañados de texto (`Pendiente`, `Aprobado`, `Observado`, etc.), no dependen solo del color.
- Etiquetas visibles de formulario, controles nativos, feedback de éxito/error, validación duplicada en servidor.
- Tablas con búsqueda client-side, desbordamiento controlado y enlaces claros.
- `:focus-visible`, adaptación a pantallas pequeñas y `prefers-reduced-motion`.
- Confirmación para cierre irreversible de defensa y resolución. Falta una evaluación formal de usabilidad con usuarios reales y auditoría WCAG completa.

## Mapa por rol

| Rol | Menús frecuentes | Tarea prioritaria |
|---|---|---|
| Estudiante | Panel / Postulaciones / Notificaciones / Perfil | Crear expediente y presentar documentos |
| Tutor | Panel / Postulaciones asignadas / Agenda | Dictamen, seguimiento e hitos |
| Tribunal | Panel / Proyectos asignados / Defensas | Evaluaciones y calificación |
| Dirección | Panel / Postulaciones / Convocatorias / Reportes | Aprobar y designar |
| Secretaría | Panel / Requisitos en expedientes / Defensas / Reportes | Verificar, programar y emitir acta |
| Admin | Panel / Usuarios / Convocatorias / Reportes | Seguridad y parametrización |

## Navegación funcional

- `/login`: autenticación (ingreso).
- `/`: resumen visible por rol.
- `/postulaciones`: tabla y formulario de creación para estudiante.
- `/postulaciones/{id}`: línea de estados, requisitos, versiones, hitos, asignaciones, dictámenes, defensa y acta.
- `/defensas`: tabla de eventos con fecha/sala/estado.
- `/admin/usuarios`: creación de usuarios (admin).
- `/admin/convocatorias`: publicación de fechas/modalidades (Dirección/admin).
- `/reportes`: indicadores filtrados por competencia.
- `/notificaciones`: avisos internos.
- `/perfil`: identidad y cambio de clave.

## Evaluación heurística pendiente

Antes de defender: probar navegación con teclado, contraste en móvil, textos largos de 220 caracteres, formularios inválidos, fallos de red y mensajes visibles; tomar capturas reales del prototipo (no de un mockup). Para publicar, añadir paginación servidor, ayuda contextual, gestión de roles avanzada, carga masiva y pruebas de accesibilidad automatizadas.
