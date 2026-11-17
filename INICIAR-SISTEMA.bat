@echo off
chcp 65001 >nul
title Sistema de trazabilidad M^&V (piloto)
cd /d C:\xampp\htdocs\trazabilidad-laravel
set "PATH=C:\xampp\php;C:\xampp\mysql\bin;%PATH%"

rem Este archivo SOLO enciende el sistema. No borra ni recrea datos.
rem (INSTALAR-TRAZABILIDAD.bat si borra todo: no lo uses antes de la supervision.)

echo [1/3] Encendiendo MySQL si esta apagado ...
netstat -ano | findstr ":3306" >nul
if errorlevel 1 (
  start "MySQL de XAMPP" /min cmd /c C:\xampp\mysql_start.bat
  timeout /t 10 /nobreak >nul
)

echo [2/3] Limpiando la cache de configuracion ...
php artisan config:clear >nul

echo [3/3] Abriendo http://127.0.0.1:8000  (lorenzo@mvtextil.pe / demo123)
echo No cierres esta ventana mientras uses el sistema.
start "" http://127.0.0.1:8000
php artisan serve
