<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas del sistema de trazabilidad (ver bd/mv_trazabilidad.sql y el modelo físico del informe).
 * Se respetan los nombres en español del modelo físico.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $t) {
            $t->increments('id_usuario');
            $t->string('nombre', 100);
            $t->string('correo', 120)->unique();
            $t->string('contrasena', 255);
            $t->enum('rol', ['ADMINISTRADOR', 'CONTROL_MATERIALES', 'ALMACEN', 'VENTAS', 'CONSULTA']);
            $t->boolean('activo')->default(true);
            $t->rememberToken();
        });

        Schema::create('proveedores', function (Blueprint $t) {
            $t->increments('id_proveedor');
            $t->string('razon_social', 150);
            $t->char('ruc', 11)->nullable()->unique();
            $t->string('telefono', 20)->nullable();
            $t->string('rubro', 80)->nullable();
        });

        Schema::create('costureros', function (Blueprint $t) {
            $t->increments('id_costurero');
            $t->string('nombre', 100);
            $t->string('zona', 60)->nullable();
            $t->string('telefono', 20)->nullable();
        });

        Schema::create('materiales', function (Blueprint $t) {
            $t->increments('id_material');
            $t->string('nombre', 120)->unique();
            $t->enum('tipo', ['TELA', 'INSUMO', 'CONSUMIBLE', 'EMPAQUE']);
            $t->string('unidad', 20);
            $t->decimal('stock_actual', 10, 2)->default(0);
            $t->decimal('stock_minimo', 10, 2)->default(0);
            $t->decimal('costo_promedio', 10, 2)->default(0);
        });

        Schema::create('productos', function (Blueprint $t) {
            $t->increments('id_producto');
            $t->string('nombre', 100);
            $t->string('talla_tamano', 40)->nullable();
        });

        // Receta: cuánto de cada material lleva una unidad del producto
        Schema::create('materiales_producto', function (Blueprint $t) {
            $t->increments('id_material_producto');
            $t->unsignedInteger('id_producto');
            $t->unsignedInteger('id_material');
            $t->decimal('consumo_unitario', 8, 3);
            $t->foreign('id_producto')->references('id_producto')->on('productos');
            $t->foreign('id_material')->references('id_material')->on('materiales');
        });

        Schema::create('compras', function (Blueprint $t) {
            $t->increments('id_compra');
            $t->unsignedInteger('id_proveedor');
            $t->unsignedInteger('id_usuario');
            $t->date('fecha');
            $t->decimal('total', 10, 2)->default(0);
            $t->foreign('id_proveedor')->references('id_proveedor')->on('proveedores');
            $t->foreign('id_usuario')->references('id_usuario')->on('usuarios');
        });

        Schema::create('detalle_compra', function (Blueprint $t) {
            $t->increments('id_detalle');
            $t->unsignedInteger('id_compra');
            $t->unsignedInteger('id_material');
            $t->decimal('cantidad', 10, 2);
            $t->decimal('cantidad_rechazada', 10, 2)->default(0);
            $t->decimal('precio_unitario', 10, 2);
            $t->foreign('id_compra')->references('id_compra')->on('compras');
            $t->foreign('id_material')->references('id_material')->on('materiales');
        });

        Schema::create('pedidos', function (Blueprint $t) {
            $t->increments('id_pedido');
            $t->string('cliente', 120);
            $t->string('telefono_cliente', 20)->nullable();
            $t->unsignedInteger('id_producto');
            $t->text('especificaciones')->nullable();
            $t->integer('cantidad');
            $t->date('fecha_entrega');
            $t->enum('estado', ['REGISTRADO', 'EN_PRODUCCION', 'ENTREGADO', 'ANULADO'])->default('REGISTRADO');
            $t->foreign('id_producto')->references('id_producto')->on('productos');
        });

        Schema::create('ordenes', function (Blueprint $t) {
            $t->increments('id_orden');
            $t->unsignedInteger('id_pedido');
            $t->date('fecha_inicio');
            $t->date('fecha_limite');
            $t->enum('estado', ['PENDIENTE', 'EN_CORTE', 'EN_CONFECCION', 'EN_ESTAMPADO', 'TERMINADO', 'ENTREGADO', 'CANCELADA'])->default('PENDIENTE');
            $t->decimal('costo_total', 10, 2)->default(0);
            $t->foreign('id_pedido')->references('id_pedido')->on('pedidos');
        });

        Schema::create('materiales_orden', function (Blueprint $t) {
            $t->increments('id_material_orden');
            $t->unsignedInteger('id_orden');
            $t->unsignedInteger('id_material');
            $t->decimal('cantidad_requerida', 10, 2)->default(0);
            $t->decimal('cantidad_entregada', 10, 2)->default(0); // neto: entregado - devuelto
            $t->foreign('id_orden')->references('id_orden')->on('ordenes');
            $t->foreign('id_material')->references('id_material')->on('materiales');
            $t->unique(['id_orden', 'id_material']);
        });

        Schema::create('asignaciones', function (Blueprint $t) {
            $t->increments('id_asignacion');
            $t->unsignedInteger('id_orden');
            $t->unsignedInteger('id_costurero');
            $t->string('maquina', 60)->nullable();
            $t->integer('cantidad_piezas');
            $t->date('fecha');
            $t->foreign('id_orden')->references('id_orden')->on('ordenes');
            $t->foreign('id_costurero')->references('id_costurero')->on('costureros');
        });

        Schema::create('movimientos', function (Blueprint $t) {
            $t->increments('id_movimiento');
            $t->unsignedInteger('id_material');
            $t->unsignedInteger('id_orden')->nullable();
            $t->unsignedInteger('id_usuario');
            $t->enum('tipo', ['ENTRADA', 'SALIDA_ORDEN', 'DEVOLUCION', 'MERMA', 'AJUSTE', 'ANULACION']);
            $t->decimal('cantidad', 10, 2);
            $t->decimal('efecto', 10, 2)->default(0);   // + entra, - sale, 0 merma en taller
            $t->decimal('saldo', 10, 2);
            $t->string('motivo', 150)->nullable();
            $t->boolean('anulado')->default(false);     // nunca se borra
            $t->unsignedInteger('id_movimiento_anulado')->nullable();
            $t->dateTime('fecha');
            $t->foreign('id_material')->references('id_material')->on('materiales');
            $t->foreign('id_orden')->references('id_orden')->on('ordenes');
            $t->foreign('id_usuario')->references('id_usuario')->on('usuarios');
            $t->foreign('id_movimiento_anulado')->references('id_movimiento')->on('movimientos');
        });

        Schema::create('estados_orden', function (Blueprint $t) {
            $t->increments('id_estado');
            $t->unsignedInteger('id_orden');
            $t->unsignedInteger('id_usuario');
            $t->string('estado', 20);
            $t->dateTime('fecha');
            $t->string('observacion', 200)->nullable();
            $t->foreign('id_orden')->references('id_orden')->on('ordenes');
            $t->foreign('id_usuario')->references('id_usuario')->on('usuarios');
        });
    }

    public function down(): void
    {
        foreach (['estados_orden', 'movimientos', 'asignaciones', 'materiales_orden', 'ordenes', 'pedidos',
                  'detalle_compra', 'compras', 'materiales_producto', 'productos', 'materiales',
                  'costureros', 'proveedores', 'usuarios'] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
