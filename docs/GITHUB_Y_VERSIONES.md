# Repositorio GitHub y versionado — SIGTA

**Nombre sugerido:** `sigta-is2`  
**Ruta futura:** `https://github.com/Developer-vic1/sigta-is2`  
Esta URL solo será válida después de crear el repositorio en la cuenta indicada. El ZIP no crea ni publica un repositorio remotamente.

## Inicialización en la raíz del proyecto

```bash
git init -b main
git add .
git commit -m "feat: inicializar SIGTA - plataforma de titulacion"
git remote add origin https://github.com/Developer-vic1/sigta-is2.git
git push -u origin main
git checkout -b develop
git push -u origin develop
```

Crea primero el repositorio vacío desde GitHub. Si el repositorio viene con README preexistente, reconcilia los commits antes de subir. **Verifica `git status` y `.gitignore` antes del primer push** para no incluir `.env`, datos de prueba confidenciales, `storage/private` ni respaldos con información estudiantil.

## Ramas y convenciones

- `main`: versión verificable para entrega.
- `develop`: integración revisada.
- `feature/auth-rbac`, `feature/expedientes`, `feature/documentos`, `feature/evaluaciones`, `feature/defensas`, `feature/reportes`: trabajos aislados.
- PR con descripción, HU/RF asociados, capturas reales, test y revisión.
- Etiquetas: `must`, `should`, `bug`, `security`, `documentation`, `ui`, `testing`.

## Historial de versiones propuesto

- `v0.1.0` arquitectura, esquema y cuentas.
- `v0.2.0` postulaciones, documentos, revisiones.
- `v0.3.0` tribunales, evaluaciones y defensas.
- `v0.4.0` actas, reportes, auditoría y cierre de pruebas.

Estas etiquetas son una **planificación**; no indican versiones ya publicadas. Solo crea releases después de ejecutar los escenarios de aceptación en MySQL y generar evidencias.
