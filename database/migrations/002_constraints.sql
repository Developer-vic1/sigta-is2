-- Restricciones adicionales explícitas de unicidad para asociaciones independientes.
-- La coherencia de carrera y convocatoria, y los solapamientos de horarios,
-- requieren validación transaccional en servicios por involucrar varias tablas.
CREATE INDEX idx_defenses_window ON defenses(starts_at,ends_at,status);
CREATE INDEX idx_document_lookups ON document_versions(document_id,created_at);
CREATE INDEX idx_notifications_date ON notifications(created_at);
