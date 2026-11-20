-- SIGTA / MySQL 8.0.30+ / utf8mb4 / InnoDB
-- Diseño de relaciones sin grupos repetidos; asociaciones independientes descompuestas.
-- 5FN se evalúa por dependencia de unión con el conjunto de reglas conocidas; ver docs/normalizacion-5fn.md.
SET NAMES utf8mb4;
-- Primera instalación solamente. Una migración no debe eliminar datos existentes.
CREATE TABLE users (
 id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
 full_name VARCHAR(150) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CHECK (active IN(0,1))
) ENGINE=InnoDB;
CREATE TABLE roles (
 id SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
 code VARCHAR(32) NOT NULL UNIQUE,
 name VARCHAR(70) NOT NULL
) ENGINE=InnoDB;
CREATE TABLE user_roles (
 user_id BIGINT UNSIGNED NOT NULL,
 role_id SMALLINT UNSIGNED NOT NULL,
 PRIMARY KEY(user_id,role_id),
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(role_id) REFERENCES roles(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
CREATE TABLE careers (
 id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
 code VARCHAR(20) NOT NULL UNIQUE,
 name VARCHAR(120) NOT NULL,
 active TINYINT NOT NULL DEFAULT 1
) ENGINE=InnoDB;
CREATE TABLE staff_careers (
 user_id BIGINT UNSIGNED NOT NULL,
 career_id INT UNSIGNED NOT NULL,
 PRIMARY KEY(user_id,career_id),
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(career_id) REFERENCES careers(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE students (
 user_id BIGINT UNSIGNED PRIMARY KEY,
 career_id INT UNSIGNED NOT NULL,
 enrollment_code VARCHAR(30) NOT NULL UNIQUE,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(career_id) REFERENCES careers(id)
) ENGINE=InnoDB;
CREATE TABLE teachers (
 user_id BIGINT UNSIGNED PRIMARY KEY,
 employee_code VARCHAR(30) NOT NULL UNIQUE,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE specialties (
 id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
 name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;
CREATE TABLE teacher_specialties (
 teacher_id BIGINT UNSIGNED NOT NULL,
 specialty_id INT UNSIGNED NOT NULL,
 PRIMARY KEY(teacher_id,specialty_id),
 FOREIGN KEY(teacher_id) REFERENCES teachers(user_id) ON DELETE CASCADE,
 FOREIGN KEY(specialty_id) REFERENCES specialties(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE modalities (
 id SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
 code VARCHAR(35) NOT NULL UNIQUE,
 name VARCHAR(90) NOT NULL,
 requires_profile TINYINT NOT NULL DEFAULT 1,
 requires_tutor TINYINT NOT NULL DEFAULT 1,
 requires_tribunal TINYINT NOT NULL DEFAULT 1,
 requires_defense TINYINT NOT NULL DEFAULT 1
) ENGINE=InnoDB;
CREATE TABLE career_modalities (
 career_id INT UNSIGNED NOT NULL,
 modality_id SMALLINT UNSIGNED NOT NULL,
 PRIMARY KEY(career_id,modality_id),
 FOREIGN KEY(career_id) REFERENCES careers(id) ON DELETE CASCADE,
 FOREIGN KEY(modality_id) REFERENCES modalities(id)
) ENGINE=InnoDB;
CREATE TABLE modality_grade_weights (
 modality_id SMALLINT UNSIGNED NOT NULL,
 assignment_role VARCHAR(12) NOT NULL,
 weight_percent DECIMAL(5,2) NOT NULL,
 PRIMARY KEY(modality_id, assignment_role),
 FOREIGN KEY(modality_id) REFERENCES modalities(id) ON DELETE CASCADE,
 CHECK(assignment_role IN('tutor','tribunal')),
 CHECK(weight_percent BETWEEN 0 AND 100)
) ENGINE=InnoDB;
CREATE TABLE career_regulations (
 id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
 career_id INT UNSIGNED NOT NULL,
 version_code VARCHAR(35) NOT NULL,
 title VARCHAR(160) NOT NULL,
 regulation_text TEXT NOT NULL,
 effective_at DATE NOT NULL,
 created_by BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_career_version(career_id,version_code),
 FOREIGN KEY(career_id) REFERENCES careers(id),
 FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE calls (
 id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
 career_id INT UNSIGNED NOT NULL,
 title VARCHAR(160) NOT NULL,
 opens_at DATETIME NOT NULL,
 closes_at DATETIME NOT NULL,
 progress_days SMALLINT UNSIGNED NOT NULL DEFAULT 180,
 active TINYINT NOT NULL DEFAULT 1,
 created_by BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(career_id) REFERENCES careers(id),
 FOREIGN KEY(created_by) REFERENCES users(id),
 CHECK (opens_at<closes_at),
 CHECK (progress_days BETWEEN 1 AND 1460)
) ENGINE=InnoDB;
CREATE TABLE call_modalities (
 call_id BIGINT UNSIGNED NOT NULL,
 modality_id SMALLINT UNSIGNED NOT NULL,
 PRIMARY KEY(call_id,modality_id),
 FOREIGN KEY(call_id) REFERENCES calls(id) ON DELETE CASCADE,
 FOREIGN KEY(modality_id) REFERENCES modalities(id)
) ENGINE=InnoDB;
CREATE TABLE requirements (
 id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
 code VARCHAR(35) NOT NULL UNIQUE,
 name VARCHAR(120) NOT NULL,
 description VARCHAR(240) NULL
) ENGINE=InnoDB;
CREATE TABLE modality_requirements (
 modality_id SMALLINT UNSIGNED NOT NULL,
 requirement_id INT UNSIGNED NOT NULL,
 PRIMARY KEY(modality_id,requirement_id),
 FOREIGN KEY(modality_id) REFERENCES modalities(id) ON DELETE CASCADE,
 FOREIGN KEY(requirement_id) REFERENCES requirements(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE applications (
 id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
 student_id BIGINT UNSIGNED NOT NULL,
 call_id BIGINT UNSIGNED NOT NULL,
 modality_id SMALLINT UNSIGNED NOT NULL,
 topic VARCHAR(220) NOT NULL,
 abstract_text TEXT NULL,
 status VARCHAR(32) NOT NULL DEFAULT 'borrador',
 decision_note TEXT NULL,
 decided_by BIGINT UNSIGNED NULL,
 submitted_at DATETIME NULL,
 approved_at DATETIME NULL,
 last_progress_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_student_call(student_id,call_id),
 KEY idx_status(status,updated_at),
 FOREIGN KEY(student_id) REFERENCES students(user_id),
 FOREIGN KEY(call_id,modality_id) REFERENCES call_modalities(call_id,modality_id),
 FOREIGN KEY(decided_by) REFERENCES users(id),
 CHECK(status IN ('borrador','presentada','observada','aprobada','en_desarrollo','habilitada','programada','defendida','concluida','rechazada','caducada'))
) ENGINE=InnoDB;
CREATE TABLE requirement_verifications (
 application_id BIGINT UNSIGNED NOT NULL,
 requirement_id INT UNSIGNED NOT NULL,
 status VARCHAR(16) NOT NULL DEFAULT 'pendiente',
 notes VARCHAR(300) NULL,
 verified_by BIGINT UNSIGNED NULL,
 verified_at DATETIME NULL,
 PRIMARY KEY(application_id, requirement_id),
 FOREIGN KEY(application_id) REFERENCES applications(id) ON DELETE CASCADE,
 FOREIGN KEY(requirement_id) REFERENCES requirements(id),
 FOREIGN KEY(verified_by) REFERENCES users(id),
 CHECK(status IN ('pendiente','aprobado','rechazado'))
) ENGINE=InnoDB;
CREATE TABLE document_types (
 id SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
 code VARCHAR(35) NOT NULL UNIQUE,
 name VARCHAR(110) NOT NULL
) ENGINE=InnoDB;
CREATE TABLE documents (
 id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
 application_id BIGINT UNSIGNED NOT NULL,
 document_type_id SMALLINT UNSIGNED NOT NULL,
 milestone_id BIGINT UNSIGNED NULL,
 milestone_key BIGINT UNSIGNED GENERATED ALWAYS AS (COALESCE(milestone_id,0)) STORED,
 created_by BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_document_slot(application_id,document_type_id,milestone_key),
 KEY idx_app_type(application_id,document_type_id),
 FOREIGN KEY(application_id) REFERENCES applications(id) ON DELETE CASCADE,
 FOREIGN KEY(document_type_id) REFERENCES document_types(id),
 FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE document_versions (
 id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
 document_id BIGINT UNSIGNED NOT NULL,
 version_number SMALLINT UNSIGNED NOT NULL,
 storage_key VARCHAR(140) NOT NULL UNIQUE,
 original_filename VARCHAR(180) NOT NULL,
 mime_type VARCHAR(80) NOT NULL,
 file_size INT UNSIGNED NOT NULL,
 sha256 CHAR(64) NOT NULL,
 uploaded_by BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_document_version(document_id,version_number),
 FOREIGN KEY(document_id) REFERENCES documents(id) ON DELETE CASCADE,
 FOREIGN KEY(uploaded_by) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE document_reviews (
 id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
 version_id BIGINT UNSIGNED NOT NULL,
 reviewer_id BIGINT UNSIGNED NOT NULL,
 decision VARCHAR(20) NOT NULL,
 comments TEXT NOT NULL,
 reviewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(version_id) REFERENCES document_versions(id) ON DELETE CASCADE,
 FOREIGN KEY(reviewer_id) REFERENCES users(id),
 CHECK(decision IN('aprobado','observado','rechazado'))
) ENGINE=InnoDB;
CREATE TABLE assignments (
 id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
 application_id BIGINT UNSIGNED NOT NULL,
 teacher_id BIGINT UNSIGNED NOT NULL,
 role_code VARCHAR(12) NOT NULL,
 seat TINYINT UNSIGNED NOT NULL DEFAULT 1,
 assigned_by BIGINT UNSIGNED NOT NULL,
 assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 ended_at DATETIME NULL,
 active_marker TINYINT GENERATED ALWAYS AS (CASE WHEN ended_at IS NULL THEN 1 ELSE NULL END) STORED,
 UNIQUE KEY uq_active_app_role(application_id,role_code,seat,active_marker),
 UNIQUE KEY uq_active_teacher(application_id,teacher_id,active_marker),
 KEY idx_teacher_active(teacher_id,ended_at),
 FOREIGN KEY(application_id) REFERENCES applications(id) ON DELETE CASCADE,
 FOREIGN KEY(teacher_id) REFERENCES teachers(user_id),
 FOREIGN KEY(assigned_by) REFERENCES users(id),
 CHECK(role_code IN('tutor','tribunal','revisor')),
 CHECK(seat BETWEEN 1 AND 3)
) ENGINE=InnoDB;
CREATE TABLE milestones (
 id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
 application_id BIGINT UNSIGNED NOT NULL,
 title VARCHAR(140) NOT NULL,
 sequence_no SMALLINT UNSIGNED NOT NULL,
 due_at DATETIME NOT NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'pendiente',
 completed_at DATETIME NULL,
 created_by BIGINT UNSIGNED NOT NULL,
 UNIQUE KEY uq_app_sequence(application_id,sequence_no),
 FOREIGN KEY(application_id) REFERENCES applications(id) ON DELETE CASCADE,
 FOREIGN KEY(created_by) REFERENCES users(id),
 CHECK(status IN('pendiente','entregado','aprobado','observado'))
) ENGINE=InnoDB;
ALTER TABLE documents ADD CONSTRAINT fk_document_milestone FOREIGN KEY(milestone_id) REFERENCES milestones(id) ON DELETE SET NULL;
CREATE TABLE milestone_submissions (
 id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
 milestone_id BIGINT UNSIGNED NOT NULL,
 version_id BIGINT UNSIGNED NOT NULL,
 submitted_by BIGINT UNSIGNED NOT NULL,
 submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_milestone_version(milestone_id,version_id),
 FOREIGN KEY(milestone_id) REFERENCES milestones(id) ON DELETE CASCADE,
 FOREIGN KEY(version_id) REFERENCES document_versions(id) ON DELETE CASCADE,
 FOREIGN KEY(submitted_by) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE rubrics (
 id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
 modality_id SMALLINT UNSIGNED NOT NULL,
 name VARCHAR(120) NOT NULL,
 active TINYINT NOT NULL DEFAULT 1,
 FOREIGN KEY(modality_id) REFERENCES modalities(id)
) ENGINE=InnoDB;
CREATE TABLE rubric_criteria (
 id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
 rubric_id INT UNSIGNED NOT NULL,
 label VARCHAR(130) NOT NULL,
 weight_percent DECIMAL(5,2) NOT NULL,
 UNIQUE KEY uq_rubric_label(rubric_id,label),
 FOREIGN KEY(rubric_id) REFERENCES rubrics(id) ON DELETE CASCADE,
 CHECK(weight_percent BETWEEN 0 AND 100)
) ENGINE=InnoDB;
CREATE TABLE evaluations (
 id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
 assignment_id BIGINT UNSIGNED NOT NULL,
 rubric_id INT UNSIGNED NOT NULL,
 sufficient TINYINT NOT NULL,
 comments VARCHAR(1000) NOT NULL,
 evaluated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_assignment_evaluation(assignment_id),
 FOREIGN KEY(assignment_id) REFERENCES assignments(id),
 FOREIGN KEY(rubric_id) REFERENCES rubrics(id),
 CHECK(sufficient IN(0,1))
) ENGINE=InnoDB;
CREATE TABLE evaluation_scores (
 evaluation_id BIGINT UNSIGNED NOT NULL,
 criterion_id INT UNSIGNED NOT NULL,
 score DECIMAL(5,2) NOT NULL,
 PRIMARY KEY(evaluation_id,criterion_id),
 FOREIGN KEY(evaluation_id) REFERENCES evaluations(id) ON DELETE CASCADE,
 FOREIGN KEY(criterion_id) REFERENCES rubric_criteria(id),
 CHECK(score BETWEEN 0 AND 100)
) ENGINE=InnoDB;
CREATE TABLE rooms (
 id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
 name VARCHAR(100) NOT NULL UNIQUE,
 capacity SMALLINT UNSIGNED NOT NULL DEFAULT 20,
 active TINYINT NOT NULL DEFAULT 1
) ENGINE=InnoDB;
CREATE TABLE defenses (
 id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
 application_id BIGINT UNSIGNED NOT NULL UNIQUE,
 room_id INT UNSIGNED NOT NULL,
 starts_at DATETIME NOT NULL,
 ends_at DATETIME NOT NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'programada',
 scheduled_by BIGINT UNSIGNED NOT NULL,
 scheduled_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(application_id) REFERENCES applications(id),
 FOREIGN KEY(room_id) REFERENCES rooms(id),
 FOREIGN KEY(scheduled_by) REFERENCES users(id),
 CHECK(starts_at<ends_at),
 CHECK(status IN('programada','cancelada','finalizada'))
) ENGINE=InnoDB;
CREATE TABLE defense_scores (
 defense_id BIGINT UNSIGNED NOT NULL,
 assignment_id BIGINT UNSIGNED NOT NULL,
 grade DECIMAL(5,2) NOT NULL,
 comments VARCHAR(700) NULL,
 scored_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(defense_id,assignment_id),
 FOREIGN KEY(defense_id) REFERENCES defenses(id) ON DELETE CASCADE,
 FOREIGN KEY(assignment_id) REFERENCES assignments(id),
 CHECK(grade BETWEEN 0 AND 100)
) ENGINE=InnoDB;
CREATE TABLE acts (
 id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
 defense_id BIGINT UNSIGNED NOT NULL UNIQUE,
 final_grade DECIMAL(5,2) NOT NULL,
 storage_key VARCHAR(140) NOT NULL UNIQUE,
 sha256 CHAR(64) NOT NULL,
 issued_by BIGINT UNSIGNED NOT NULL,
 issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(defense_id) REFERENCES defenses(id),
 FOREIGN KEY(issued_by) REFERENCES users(id),
 CHECK(final_grade BETWEEN 0 AND 100)
) ENGINE=InnoDB;
CREATE TABLE binding_authorizations (
 id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
 application_id BIGINT UNSIGNED NOT NULL UNIQUE,
 final_version_id BIGINT UNSIGNED NOT NULL,
 storage_key VARCHAR(140) NOT NULL UNIQUE,
 sha256 CHAR(64) NOT NULL,
 authorized_by BIGINT UNSIGNED NOT NULL,
 authorized_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(application_id) REFERENCES applications(id),
 FOREIGN KEY(final_version_id) REFERENCES document_versions(id),
 FOREIGN KEY(authorized_by) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE resolutions (
 id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
 application_id BIGINT UNSIGNED NOT NULL UNIQUE,
 final_grade DECIMAL(5,2) NOT NULL,
 basis_note VARCHAR(800) NOT NULL,
 storage_key VARCHAR(140) NOT NULL UNIQUE,
 sha256 CHAR(64) NOT NULL,
 issued_by BIGINT UNSIGNED NOT NULL,
 issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(application_id) REFERENCES applications(id),
 FOREIGN KEY(issued_by) REFERENCES users(id),
 CHECK(final_grade BETWEEN 0 AND 100)
) ENGINE=InnoDB;
CREATE TABLE notifications (
 id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
 user_id BIGINT UNSIGNED NOT NULL,
 application_id BIGINT UNSIGNED NULL,
 content VARCHAR(500) NOT NULL,
 read_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_unread(user_id,read_at),
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(application_id) REFERENCES applications(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE TABLE audit_logs (
 id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
 actor_user_id BIGINT UNSIGNED NULL,
 event_code VARCHAR(70) NOT NULL,
 entity_type VARCHAR(70) NOT NULL,
 entity_id BIGINT UNSIGNED NOT NULL,
 details_json JSON NOT NULL,
 ip_hash CHAR(64) NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_entity(entity_type,entity_id),
 FOREIGN KEY(actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE TABLE login_attempts (
 id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
 identifier_hash CHAR(64) NOT NULL,
 ip_hash CHAR(64) NOT NULL,
 success TINYINT NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_login_identifier(identifier_hash,created_at),
 KEY idx_login_ip(ip_hash,created_at)
) ENGINE=InnoDB;
