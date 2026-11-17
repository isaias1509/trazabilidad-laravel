@echo off
chcp 65001 >nul
title Subir el sistema a GitHub
cd /d C:\xampp\htdocs\trazabilidad-laravel

echo.
echo  Crea primero un repositorio VACIO en https://github.com/new
echo  (nombre: trazabilidad-laravel, Public, SIN README ni .gitignore).
echo.
set "REPO="
set /p REPO=Pega aqui la URL del repositorio y presiona Enter: 
if "%REPO%"=="" (
  echo No escribiste la URL. Vuelve a abrir este archivo.
  pause
  exit /b 1
)

if not exist .git git init -b main
git config user.name >nul 2>&1 || git config user.name "Isais Cardenas Jimenez"
git config user.email >nul 2>&1 || git config user.email "isais.cardenas@users.noreply.github.com"

git add -A
git commit -m "Sistema de trazabilidad: correcciones, directorio, script SQL y despliegue"
git branch -M main
git remote remove origin >nul 2>&1
git remote add origin %REPO%

echo.
echo  Subiendo... si se abre una ventana de GitHub, inicia sesion y autoriza.
git push -u origin main
if errorlevel 1 (
  echo.
  echo  ERROR al subir. Toma captura de esta ventana y mandasela a Claude.
) else (
  echo.
  echo  LISTO. Tu codigo ya esta en %REPO%
)
pause
