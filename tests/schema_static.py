"""Revisión estática básica de nombres y referencias SQL; NO sustituye MySQL."""
import re,pathlib
sql=(pathlib.Path(__file__).parents[1]/'database/migrations/001_schema.sql').read_text()
tables={name:body for name,body in re.findall(r'CREATE TABLE\s+(\w+)\s*\((.*?)\)\s*ENGINE=InnoDB;',sql,re.S|re.I)}
assert len(tables)>=35, 'No están todas las tablas'
for name,body in tables.items():
    for dest in re.findall(r'REFERENCES\s+(\w+)\s*\(',body,re.I):
        assert dest in tables, f'FK desde {name} hacia tabla inexistente: {dest}'
assert 'uq_active_teacher' in sql and 'uq_active_app_role' in sql
assert 'FOREIGN KEY(call_id,modality_id) REFERENCES call_modalities' in sql
print(f'[OK] {len(tables)} tablas, FKs referencian tablas declaradas, índices críticos presentes')
print('[AVISO] No verifica semántica SQL MySQL ni certifica 5FN.')
