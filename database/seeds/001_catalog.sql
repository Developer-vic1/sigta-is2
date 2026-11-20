INSERT INTO roles(code,name) VALUES
 ('admin','Administrador técnico'),('director','Dirección de Carrera'),('secretaria','Secretaría Académica'),
 ('estudiante','Estudiante'),('tutor','Tutor'),('tribunal','Tribunal Evaluador');
INSERT INTO careers(code,name) VALUES ('SIS','Ingeniería en Sistemas');
INSERT INTO modalities(code,name,requires_profile,requires_tutor,requires_tribunal,requires_defense) VALUES
 ('TESIS','Tesis de Grado',1,1,1,1),('PROYECTO','Proyecto de Grado',1,1,1,1),
 ('DIRIGIDO','Trabajo Dirigido',1,1,1,1),('EXAMEN','Examen de Grado',0,0,1,1),
 ('EXCELENCIA','Excelencia Académica',0,0,0,0);
INSERT INTO career_modalities(career_id,modality_id) SELECT c.id,m.id FROM careers c CROSS JOIN modalities m;
INSERT INTO modality_grade_weights(modality_id,assignment_role,weight_percent)
 SELECT id,'tutor',40 FROM modalities WHERE requires_tutor=1;
INSERT INTO modality_grade_weights(modality_id,assignment_role,weight_percent)
 SELECT id,'tribunal',60 FROM modalities WHERE requires_tribunal=1 AND requires_tutor=1;
INSERT INTO modality_grade_weights(modality_id,assignment_role,weight_percent)
 SELECT id,'tribunal',100 FROM modalities WHERE requires_tribunal=1 AND requires_tutor=0;
INSERT INTO requirements(code,name,description) VALUES
 ('CI','Cédula de identidad','Documento de identificación del postulante'),
 ('KARDEX','Historial académico','Kardex o constancia académica vigente');
INSERT INTO modality_requirements(modality_id,requirement_id) SELECT m.id,r.id FROM modalities m CROSS JOIN requirements r;
INSERT INTO document_types(code,name) VALUES
 ('CI','Documento de identidad'),('KARDEX','Kardex académico'),('PERFIL','Perfil de proyecto'),
 ('AVANCE','Informe de avance'),('FINAL','Documento final'),('OTRO','Documento adicional');
INSERT INTO specialties(name) VALUES ('Ingeniería de Software'),('Redes y Telecomunicaciones'),('Bases de Datos'),('Sistemas de Información');
INSERT INTO rooms(name,capacity) VALUES ('Aula de defensa A',35),('Sala académica B',25);
INSERT INTO rubrics(modality_id,name) SELECT id,CONCAT('Rúbrica ',name) FROM modalities;
INSERT INTO rubric_criteria(rubric_id,label,weight_percent)
 SELECT r.id,'Calidad académica',40 FROM rubrics r;
INSERT INTO rubric_criteria(rubric_id,label,weight_percent)
 SELECT r.id,'Rigor metodológico',35 FROM rubrics r;
INSERT INTO rubric_criteria(rubric_id,label,weight_percent)
 SELECT r.id,'Presentación y defensa',25 FROM rubrics r;
