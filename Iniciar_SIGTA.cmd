@echo off
setlocal
cd /d "%~dp0"
if not exist ".env" (
 echo [SIGTA] Primero copia .env.example a .env y ejecuta bin\setup.php.
 pause
 exit /b 1
)
where php >nul 2>nul
if errorlevel 1 (
 echo [SIGTA] PHP no esta en PATH. Abre este archivo desde la Terminal de Laragon.
 pause
 exit /b 1
)
echo [SIGTA] Iniciando servidor local en http://127.0.0.1:8000/login
php -S 127.0.0.1:8000 -t public public/router.php
endlocal
