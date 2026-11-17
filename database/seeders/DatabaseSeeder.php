<?php

namespace Database\Seeders;

use App\Models\Asignacion;
use App\Models\Compra;
use App\Models\Costurero;
use App\Models\DetalleCompra;
use App\Models\EstadoOrden;
use App\Models\Material;
use App\Models\MaterialOrden;
use App\Models\MaterialProducto;
use App\Models\Orden;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\Inventario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Datos de ejemplo (los mismos del prototipo). Las fechas se calculan desde hoy
 * para que siempre haya una orden atrasada y otra por vencer.
 * Contraseña de todos los usuarios de prueba: demo123 (en el servidor se usa DEMO_PASSWORD del .env)
 */
class DatabaseSeeder extends Seeder
{
    private Inventario $inv;
    private array $u = [];

    public function run(): void
    {
        $this->inv = app(Inventario::class);

        foreach ([
            ['Lorenzo Medina', 'lorenzo@mvtextil.pe', 'ADMINISTRADOR'],
            ['Alejandro Medina', 'alejandro@mvtextil.pe', 'CONTROL_MATERIALES'],
            ['Almacén', 'almacen@mvtextil.pe', 'ALMACEN'],
            ['Ventas', 'ventas@mvtextil.pe', 'VENTAS'],
            ['Consulta', 'consulta@mvtextil.pe', 'CONSULTA'],
        ] as [$n, $c, $r]) {
            $this->u[$r] = User::create(['nombre' => $n, 'correo' => $c, 'contrasena' => Hash::make(env('DEMO_PASSWORD', 'demo123')), 'rol' => $r]);
        }

        $gam = Proveedor::create(['razon_social' => 'Textiles Gamarra S.A.C.', 'rubro' => 'Telas', 'telefono' => '987 654 321']);
        $avi = Proveedor::create(['razon_social' => 'Avíos y Etiquetas E.I.R.L.', 'rubro' => 'Insumos', 'telefono' => '956 123 478']);
        $c1 = Costurero::create(['nombre' => 'Costurera 1', 'zona' => 'Huaycán']);
        $c2 = Costurero::create(['nombre' => 'Costurera 2', 'zona' => 'Santa Clara']);
        $c3 = Costurero::create(['nombre' => 'Costurera 3', 'zona' => 'Collique']);

        $mat = [];
        foreach ([
            ['Tela raso rojo', 'TELA', 'm', 30, 7.5], ['Tela raso blanco', 'TELA', 'm', 30, 7.5],
            ['Tela gabardina plomo', 'TELA', 'm', 20, 9], ['Hilo poliéster', 'INSUMO', 'cono', 10, 6],
            ['Etiquetas de talla', 'EMPAQUE', 'ciento', 5, 12], ['Bolsas de empaque', 'EMPAQUE', 'ciento', 5, 10],
            ['Tiza de sastre', 'CONSUMIBLE', 'unidades', 10, 1.5],
        ] as $i => [$n, $t, $un, $min, $pr]) {
            $mat[$i + 1] = Material::create(['nombre' => $n, 'tipo' => $t, 'unidad' => $un, 'stock_minimo' => $min, 'costo_promedio' => $pr]);
        }

        $bandera = $this->producto('Bandera del Perú', '90×150', [1 => 1.2, 2 => 0.6]);
        $falda = $this->producto('Falda escolar plomo', 'Tallas 8 a 14', [3 => 0.8]);
        $disfraz = $this->producto('Disfraz de Halloween', 'Talla única', [1 => 1.2]);

        // Compras iniciales
        $this->compra(-30, $gam, 1, 120, 0, 7.5);
        $this->compra(-30, $gam, 2, 95, 0, 7.5);
        $this->compra(-30, $gam, 3, 84, 4, 9);
        $this->compra(-30, $avi, 4, 30, 0, 6);
        $this->compra(-30, $avi, 5, 8, 0, 12);
        $this->compra(-30, $avi, 6, 6, 0, 10);
        $this->compra(-30, $gam, 7, 12, 0, 1.5);

        // OP-001 · faldas en confección, vence en 3 días
        $o1 = $this->orden('I.E. San Martín', $falda, 40, 3, -21);
        $this->estado($o1, 'EN_CORTE', -20, 'Tendido y tizado');
        $this->mov(-20, 3, 'SALIDA_ORDEN', 34, -34, $o1, 'Entrega a la orden', 'ALMACEN');
        $this->mov(-20, 4, 'SALIDA_ORDEN', 4, -4, $o1, 'Entrega a la orden', 'ALMACEN');
        $this->mov(-18, 3, 'MERMA', 2.5, 0, $o1, 'Retazos de corte que no se pueden usar', 'CONTROL_MATERIALES');
        $this->mov(-17, 3, 'DEVOLUCION', 1.5, 1.5, $o1, 'Sobrante devuelto por el taller', 'ALMACEN');
        $this->asignar($o1, $c1, -17);
        $this->estado($o1, 'EN_CONFECCION', -17, 'Asignada a Costurera 1');

        // OP-002 · banderas pendientes
        $this->orden('Municipalidad de Ate', $bandera, 25, 10, -1);

        // OP-003 · disfraces entregados
        $o3 = $this->orden('Tienda Gamarra · campaña Halloween', $disfraz, 30, -6, -26);
        $this->estado($o3, 'EN_CORTE', -25, 'Tendido y tizado');
        $this->mov(-25, 1, 'SALIDA_ORDEN', 38, -38, $o3, 'Entrega a la orden', 'ALMACEN');
        $this->asignar($o3, $c2, -22);
        $this->estado($o3, 'EN_CONFECCION', -22, 'Asignada a Costurera 2');
        $this->mov(-14, 1, 'MERMA', 1, 0, $o3, 'Falla en la tela', 'CONTROL_MATERIALES');
        $this->estado($o3, 'EN_ESTAMPADO', -13, 'Estampado');
        $this->estado($o3, 'TERMINADO', -11, 'Prendas revisadas');
        $this->mov(-10, 5, 'SALIDA_ORDEN', 3, -3, $o3, 'Etiquetas para empaque', 'ALMACEN');
        $this->mov(-10, 6, 'SALIDA_ORDEN', 3, -3, $o3, 'Bolsas para empaque', 'ALMACEN');
        $this->estado($o3, 'ENTREGADO', -7, 'Entregado al cliente');

        // OP-004 · banderas atrasadas
        $o4 = $this->orden('I.E. José Olaya', $bandera, 25, -2, -10);
        $this->estado($o4, 'EN_CORTE', -9, 'Tendido y tizado');
        $this->mov(-9, 1, 'SALIDA_ORDEN', 31, -31, $o4, 'Entrega adicional (supera lo estimado)', 'ALMACEN');
        $this->mov(-9, 2, 'SALIDA_ORDEN', 16, -16, $o4, 'Entrega adicional (supera lo estimado)', 'ALMACEN');
        $this->asignar($o4, $c3, -7);
        $this->estado($o4, 'EN_CONFECCION', -7, 'Asignada a Costurera 3');
        $this->mov(-3, 4, 'SALIDA_ORDEN', 8, -8, $o4, 'Hilo para confección', 'ALMACEN');
        $this->estado($o4, 'EN_ESTAMPADO', -3, 'Estampado del escudo');

        // Ajuste por conteo físico, consumos recientes de OP-001 y una compra reciente
        $this->mov(-5, 7, 'AJUSTE', 2, -2, null, 'Conteo físico no cuadra · contado 4, el sistema decía 6', 'ADMINISTRADOR');
        $this->mov(-2, 5, 'SALIDA_ORDEN', 1, -1, $o1, 'Etiquetas de talla', 'ALMACEN');
        $this->mov(-2, 6, 'SALIDA_ORDEN', 1, -1, $o1, 'Bolsas de empaque', 'ALMACEN');
        $this->mov(-1, 7, 'SALIDA_ORDEN', 2, -2, $o1, 'Tizas para tizado', 'ALMACEN');
        $this->compra(-1, $gam, 2, 30, 0, 7.5);
    }

    private function como(string $rol): void { Auth::setUser($this->u[$rol]); }

    private function dia(int $n, string $h = '09:00'): Carbon { return Carbon::today()->addDays($n)->setTimeFromTimeString($h); }

    private function producto(string $n, string $talla, array $receta): Producto
    {
        $p = Producto::create(['nombre' => $n, 'talla_tamano' => $talla]);
        foreach ($receta as $idm => $c) MaterialProducto::create(['id_producto' => $p->id_producto, 'id_material' => $idm, 'consumo_unitario' => $c]);
        return $p;
    }

    private function compra(int $dia, Proveedor $p, int $idm, float $cant, float $rech, float $precio): void
    {
        $this->como('CONTROL_MATERIALES');
        $c = Compra::create(['id_proveedor' => $p->id_proveedor, 'id_usuario' => Auth::id(), 'fecha' => $this->dia($dia), 'total' => ($cant - $rech) * $precio]);
        DetalleCompra::create(['id_compra' => $c->id_compra, 'id_material' => $idm, 'cantidad' => $cant, 'cantidad_rechazada' => $rech, 'precio_unitario' => $precio]);
        $m = $this->inv->mover($idm, 'ENTRADA', $cant - $rech, $cant - $rech, null,
            "Compra a {$p->razon_social} · S/ " . number_format($precio, 2) . ' c/u' . ($rech ? " · $rech rechazados por falla" : ''));
        $m->update(['fecha' => $this->dia($dia)]);
    }

    private function mov(int $dia, int $idm, string $tipo, float $cant, float $ef, ?Orden $o, string $motivo, string $rol): void
    {
        $this->como($rol);
        $this->inv->mover($idm, $tipo, $cant, $ef, $o?->id_orden, $motivo)->update(['fecha' => $this->dia($dia, '10:00')]);
    }

    private function orden(string $cliente, Producto $p, int $cant, int $entrega, int $creada): Orden
    {
        $this->como('VENTAS');
        $pe = Pedido::create(['cliente' => $cliente, 'id_producto' => $p->id_producto, 'cantidad' => $cant,
            'fecha_entrega' => $this->dia($entrega), 'estado' => 'EN_PRODUCCION']);
        $o = Orden::create(['id_pedido' => $pe->id_pedido, 'fecha_inicio' => $this->dia($creada), 'fecha_limite' => $this->dia($entrega), 'estado' => 'PENDIENTE']);
        foreach ($p->receta as $x) {
            MaterialOrden::create(['id_orden' => $o->id_orden, 'id_material' => $x->id_material, 'cantidad_requerida' => round($x->consumo_unitario * $cant, 2)]);
        }
        EstadoOrden::create(['id_orden' => $o->id_orden, 'id_usuario' => Auth::id(), 'estado' => 'PENDIENTE', 'fecha' => $this->dia($creada, '08:00'), 'observacion' => 'Orden creada desde el pedido']);
        return $o;
    }

    private function estado(Orden $o, string $e, int $dia, string $obs): void
    {
        $o->update(['estado' => $e]);
        if ($e === 'ENTREGADO') $o->pedido->update(['estado' => 'ENTREGADO']);
        EstadoOrden::create(['id_orden' => $o->id_orden, 'id_usuario' => $this->u['CONTROL_MATERIALES']->id_usuario, 'estado' => $e, 'fecha' => $this->dia($dia, '11:00'), 'observacion' => $obs]);
    }

    private function asignar(Orden $o, Costurero $c, int $dia): void
    {
        Asignacion::create(['id_orden' => $o->id_orden, 'id_costurero' => $c->id_costurero, 'maquina' => 'Recta industrial', 'cantidad_piezas' => $o->pedido->cantidad, 'fecha' => $this->dia($dia)]);
    }
}
