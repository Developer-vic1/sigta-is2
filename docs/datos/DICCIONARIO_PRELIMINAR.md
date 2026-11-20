# Diccionario de datos preliminar — SIGTA

**GENERADO DESDE EL SCRIPT DDL, NO EXPORTADO DE UN MOTOR MYSQL EN EJECUCIÓN.** Para la evaluación, ejecuta `php bin/export_dictionary.php` en un MySQL 8 instalado y entrega el `DICCIONARIO_EXPORTADO_MYSQL.csv`.

Tablas en DDL: **39**. Columnas identificadas: **206**.

| Tabla | Columnas | FKs declaradas | Clave primaria |
|---|---:|---:|---|
| `users` | 7 | 0 | `definida en columna` |
| `roles` | 3 | 0 | `definida en columna` |
| `user_roles` | 2 | 2 | `user_id,role_id` |
| `careers` | 4 | 0 | `definida en columna` |
| `staff_careers` | 2 | 2 | `user_id,career_id` |
| `students` | 3 | 2 | `definida en columna` |
| `teachers` | 2 | 1 | `definida en columna` |
| `specialties` | 2 | 0 | `definida en columna` |
| `teacher_specialties` | 2 | 2 | `teacher_id,specialty_id` |
| `modalities` | 7 | 0 | `definida en columna` |
| `career_modalities` | 2 | 2 | `career_id,modality_id` |
| `modality_grade_weights` | 3 | 1 | `modality_id,assignment_role` |
| `career_regulations` | 7 | 2 | `definida en columna` |
| `calls` | 9 | 2 | `definida en columna` |
| `call_modalities` | 2 | 2 | `call_id,modality_id` |
| `requirements` | 4 | 0 | `definida en columna` |
| `modality_requirements` | 2 | 2 | `modality_id,requirement_id` |
| `applications` | 14 | 4 | `definida en columna` |
| `requirement_verifications` | 6 | 3 | `application_id,requirement_id` |
| `document_types` | 3 | 0 | `definida en columna` |
| `documents` | 7 | 3 | `definida en columna` |
| `document_versions` | 10 | 2 | `definida en columna` |
| `document_reviews` | 6 | 2 | `definida en columna` |
| `assignments` | 9 | 3 | `definida en columna` |
| `milestones` | 8 | 2 | `definida en columna` |
| `milestone_submissions` | 5 | 3 | `definida en columna` |
| `rubrics` | 4 | 1 | `definida en columna` |
| `rubric_criteria` | 4 | 1 | `definida en columna` |
| `evaluations` | 6 | 2 | `definida en columna` |
| `evaluation_scores` | 3 | 2 | `evaluation_id,criterion_id` |
| `rooms` | 4 | 0 | `definida en columna` |
| `defenses` | 8 | 3 | `definida en columna` |
| `defense_scores` | 5 | 2 | `defense_id,assignment_id` |
| `acts` | 7 | 2 | `definida en columna` |
| `binding_authorizations` | 7 | 3 | `definida en columna` |
| `resolutions` | 8 | 2 | `definida en columna` |
| `notifications` | 6 | 2 | `definida en columna` |
| `audit_logs` | 8 | 1 | `definida en columna` |
| `login_attempts` | 5 | 0 | `definida en columna` |

Descripción de cada atributo, tipo SQL, nulabilidad, claves y expresiones: ver CSV adjunto. Las cardinalidades se muestran en el MER editable y en las FKs del esquema.
