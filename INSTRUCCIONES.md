# Sistema de trazabilidad en Laravel · Cómo instalarlo (XAMPP en Windows)

Esta carpeta trae **solo los archivos del sistema**. Laravel se descarga con Composer y luego copias estos archivos encima.

## 1. Lo que necesitas (una sola vez)
- **XAMPP** con Apache y MySQL encendidos.
- **Composer**: descarga `Composer-Setup.exe` de getcomposer.org e instálalo eligiendo `C:\xampp\php\php.exe`.
- Comprueba en una terminal: `php -v` (8.2 o más) y `composer -V`.

## 2. Crear el proyecto Laravel
```
cd C:\xampp\htdocs
composer create-project laravel/laravel trazabilidad-laravel "^11.0"
```

## 3. Copiar estos archivos encima
Copia las carpetas `app`, `config`, `database`, `public`, `resources` y `routes` de este zip dentro de `C:\xampp\htdocs\trazabilidad-laravel` y acepta **reemplazar**.

## 4. Crear la base de datos
En **phpMyAdmin** (http://localhost/phpmyadmin) crea la base `mv_trazabilidad` con cotejamiento `utf8mb4_spanish_ci`.

## 5. Configurar el archivo `.env` del proyecto
Cambia estas líneas:
```
APP_NAME="Trazabilidad M&V"
APP_TIMEZONE=America/Lima
APP_LOCALE=es

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mv_trazabilidad
DB_USERNAME=root
DB_PASSWORD=
```
(Si las líneas DB_ empiezan con `#`, quítale el `#`.)

## 6. Crear las tablas y los datos de ejemplo
```
cd C:\xampp\htdocs\trazabilidad-laravel
php artisan migrate:fresh --seed
php artisan serve
```
Abre **http://127.0.0.1:8000** e ingresa con:

| Usuario | Correo | Contraseña |
|---|---|---|
| Administrador | lorenzo@mvtextil.pe | demo123 |
| Control de materiales | alejandro@mvtextil.pe | demo123 |
| Almacén | almacen@mvtextil.pe | demo123 |
| Ventas | ventas@mvtextil.pe | demo123 |

## Qué hace cada archivo
| Archivo | Para qué sirve |
|---|---|
| `database/migrations/...create_trazabilidad_tables.php` | Crea las tablas del modelo físico (usuarios, materiales, compras, pedidos, órdenes, movimientos…). |
| `database/seeders/DatabaseSeeder.php` | Carga los datos de ejemplo (mismos del prototipo). |
| `app/Models/*` | Una clase por tabla. |
| `app/Services/Inventario.php` | **Las reglas**: todo movimiento de stock pasa por aquí, valida que no quede negativo, guarda el kardex con saldo y anula sin borrar. |
| `app/Http/Controllers/*` | Una pantalla por controlador: Hoy, Órdenes, Materiales, Compras, Kardex, Reportes. |
| `config/permisos.php` | Qué puede hacer cada rol. |
| `resources/views/*` | Las pantallas (Blade). |
| `routes/web.php` | Las direcciones del sistema. |

## Si algo falla
- *"could not find driver"*: en `C:\xampp\php\php.ini` quita el `;` de `extension=pdo_mysql` y reinicia la terminal.
- *"Access denied for user root"*: revisa `DB_PASSWORD` en `.env`.
- *Error 419 al enviar un formulario*: recarga la página (la sesión expiró).
- Para empezar de cero con los datos de ejemplo: `php artisan migrate:fresh --seed`.
