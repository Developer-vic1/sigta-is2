# Análisis de normalización hacia Quinta Forma Normal (5FN)

## Qué significa exactamente 5FN

Una relación está en 5FN (o PJ/NF, forma normal de proyección-unión) cuando **toda dependencia de unión no trivial está implicada por claves candidatas**. Verificar 5FN exige conocer **todas** las dependencias semánticas del dominio y no puede deducirse automáticamente del número de tablas ni de una herramienta SQL. Este paquete implementa una descomposición orientada a 5FN bajo los requisitos conocidos, **no una certificación universal para todos los reglamentos universitarios**.

## Criterios 1FN, 2FN, 3FN, BCNF, 4FN y 5FN

- **1FN:** atributos escalares y filas individuales. No se guardan listas de docentes, roles o archivos concatenados.
- **2FN:** en tablas con clave compuesta (`user_roles`, `career_modalities`, `call_modalities`, `modality_requirements`, `teacher_specialties`, `evaluation_scores`), no se guardan atributos que dependan de una parte de la clave. Las descripciones viven en tablas maestras.
- **3FN:** los nombres de carrera, modalidad, usuario, sala o requisito no se replican en los expedientes; se referencian por FK.
- **BCNF:** cada determinante funcional admitido en las asociaciones puras es una clave candidata. Los atributos de cada entidad dependen de su ID, y las claves naturales `users.email`, `students.enrollment_code` y `modalities.code` son únicas.
- **4FN:** dependencias multivaluadas independientes se separan: un usuario puede tener varios roles (`user_roles`), un docente varias especialidades (`teacher_specialties`), una convocatoria varias modalidades (`call_modalities`), y una modalidad varios requisitos (`modality_requirements`). No se modelan multiplicaciones cartesianas de esos conjuntos.
- **5FN:** asociaciones independientes quedan como relaciones irreducibles por su propio significado. **Ejemplo:** no se mantiene una tabla gigante `(usuario,rol,especialidad)` porque los roles de usuario y las especialidades docentes son hechos independientes y su proyección-unión podría producir combinaciones falsas si se mezclan con condiciones adicionales.

## Dependencias y descomposiciones clave

| Relación | Clave candidata | Hecho atómico | Dependencia principal |
|---|---|---|---|
| `users` | `id`, `email` | Identidad + credenciales | `email → id,nombre,password_hash` |
| `user_roles` | `(user_id,role_id)` | Usuario tiene rol | Sin atributos no clave |
| `staff_careers` | `(user_id,career_id)` | Personal asignado a carrera | Sin atributos no clave |
| `students` | `user_id`, `enrollment_code` | Estudiante matriculado en carrera | `user_id → career_id,enrollment_code` |
| `teachers` | `user_id`, `employee_code` | Identidad laboral docente | `user_id → employee_code` |
| `teacher_specialties` | `(teacher_id,specialty_id)` | Docente conoce especialidad | Sin atributos no clave |
| `career_modalities` | `(career_id,modality_id)` | Carrera ofrece modalidad | Sin atributos no clave |
| `call_modalities` | `(call_id,modality_id)` | Modalidad habilitada en convocatoria | Sin atributos no clave |
| `modality_requirements` | `(modality_id,requirement_id)` | Modalidad requiere documento | Sin atributos no clave |
| `modality_grade_weights` | `(modality_id,assignment_role)` | Peso académico del tipo de evaluador | Clave compuesta → porcentaje |
| `applications` | `id`, `(student_id,call_id)` | Una postulación de estudiante en convocatoria | Clave natural → modalidad,tema,estado |
| `requirement_verifications` | `(application_id,requirement_id)` | Revisión de un requisito | Clave compuesta → estado,verificador,fecha |
| `documents` | `id`, `(application_id,type_id,milestone_key)` | Documento lógico del expediente | Identidad lógica → propietario/tipo/hito |
| `document_versions` | `id`, `(document_id,version_number)` | Una versión de archivo | Clave compuesta → hash,ruta,tamaño,MIME |
| `assignments` | `id` y claves únicas activas condicionadas | Designación de un docente a plaza | `id → app,docente,rol,plaza` |
| `evaluation_scores` | `(evaluation_id,criterion_id)` | Nota de criterio específico | Clave compuesta → nota |
| `defense_scores` | `(defense_id,assignment_id)` | Nota final de un evaluador | Clave compuesta → calificación |
| `acts` | `id`, `defense_id` | Acta emitida con nota histórica | Clave → resultado instantáneo + hash PDF |

## Restricciones de unión que merecen revisión

1. `applications` referencia `(call_id,modality_id)` con FK a `call_modalities`; la verificación `student.career_id=call.career_id` debe ejecutarse en servicio, dado que requiere relación transitiva de varias entidades.
2. `document_versions` no almacena nombre de estudiante ni modalidad; ambos se obtienen por uniones.
3. `assignments` no almacena nombres de docentes; usa FKs, lo que evita actualizaciones inconsistentes.
4. `defense_scores` no repite la nota final global; el acta persiste el resultado **histórico en el momento de la emisión**, distinto de un atributo operativo redundante.
5. `milestone_submissions` representa un hecho de vinculación de una versión con una entrega, distinto del hito y el documento general.
6. La configuración real de normativas, equivalencias entre requisitos, grupos de estudiantes y reglas específicas por programa **podría introducir nuevas dependencias de unión**, requiriendo revisar la descomposición.

## Protocolo de validación MySQL

1. Ejecutar el instalador en MySQL 8 y revisar claves `PRIMARY`, `UNIQUE`, `FOREIGN KEY`, `CHECK` e índices de asociación.
2. Inspeccionar `information_schema.KEY_COLUMN_USAGE` y `information_schema.REFERENTIAL_CONSTRAINTS`.
3. Crear datos de prueba que ejerciten todas las cardinalidades y comprobar que no hay registros espurios al unir `user_roles`, `teacher_specialties` o `modality_requirements`.
4. Verificar con casos reales de reglamento si las reglas semánticas identificadas son exhaustivas.
5. Exportar **diccionario directo desde Workbench** y entregar junto con respaldo SQL.

**Conclusión:** el diseño está descompuesto para eliminar dependencias de proyección/unión espurias identificables con la consigna. La afirmación de cumplimiento integral de 5FN requiere validación de reglas faltantes y pruebas sobre datos reales de la institución. Una 5FN declarada sin ese análisis sería injustificada.

### Extensiones de cierre documental

`career_regulations` usa clave natural única `(career_id, version_code)` para identificar texto normativo versionado; `binding_authorizations` mantiene un hecho de autorización único por expediente, que referencia la versión final documentada. Los hashes del PDF son evidencias inmutables del archivo emitido, no atributos que deban actualizarse al modificar otros datos.
