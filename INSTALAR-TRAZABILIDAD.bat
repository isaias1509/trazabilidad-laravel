@echo off
chcp 65001 >nul
title Instalando sistema de trazabilidad M^&V
cd /d C:\xampp\htdocs\trazabilidad-laravel
set "PATH=C:\xampp\php;C:\xampp\mysql\bin;%PATH%"

echo [1/5] Configurando la conexion a MySQL en .env ...
powershell -NoProfile -Command "$p='.env'; $s=[IO.File]::ReadAllText($p); $s=$s -replace 'DB_CONNECTION=sqlite','DB_CONNECTION=mysql' -replace '# DB_HOST=','DB_HOST=' -replace '# DB_PORT=','DB_PORT=' -replace '# DB_DATABASE=laravel','DB_DATABASE=mv_trazabilidad' -replace '# DB_USERNAME=','DB_USERNAME=' -replace '# DB_PASSWORD=','DB_PASSWORD=' -replace 'APP_LOCALE=en','APP_LOCALE=es'; [IO.File]::WriteAllText($p,$s)"

echo [2/5] Encendiendo MySQL si esta apagado ...
netstat -ano | findstr ":3306" >nul
if errorlevel 1 (
  start "MySQL de XAMPP" /min cmd /c C:\xampp\mysql_start.bat
  timeout /t 10 /nobreak >nul
)

echo [3/5] Creando la base de datos mv_trazabilidad ...
mysql -u root -e "CREATE DATABASE IF NOT EXISTS mv_trazabilidad CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;" > instalar-log.txt 2>&1

echo [4/5] Creando tablas y datos de ejemplo ...
php artisan config:clear >> instalar-log.txt 2>&1
php artisan migrate:fresh --seed --force >> instalar-log.txt 2>&1
set ERR=%errorlevel%
type instalar-log.txt
if not "%ERR%"=="0" (
  echo.
  echo *** Hubo un error. Avisale a Claude: ya puede leer el archivo instalar-log.txt ***
  pause
  exit /b 1
)

echo.
echo [5/5] Listo. Abriendo http://127.0.0.1:8000  (usuario lorenzo@mvtextil.pe / demo123)
echo No cierres esta ventana mientras uses el sistema.
start "" http://127.0.0.1:8000
php artisan serve
