<?php

namespace App\Http\Controllers;

use App\Models\Asignacion;
use App\Models\Costurero;
use App\Models\EstadoOrden;
use App\Models\Material;
use App\Models\MaterialOrden;
use App\Models\Orden;
use App\Models\Pedido;
use App\Models\Producto;
use App\Services\Inventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrdenController extends Controller
{
    public const MOTIVOS_MERMA = ['Retazos de corte que no se pueden usar', 'Falla en la tela', 'Error de corte o confección', 'Manchas o daño en el taller', 'Otro'];

    public function index(Request $r)
    {
        $filtros = [
            'curso' => 'En curso', 'atrasadas' => 'Atrasadas', 'terminadas' => 'Terminadas',
            'canceladas' => 'Canceladas', 'todas' => 'Todas',
        ];
        $filtro = array_key_exists($r->query('filtro'), $filtros) ? $r->query('filtro') : 'curso';
        $todas = Orden::with(['pedido.producto', 'asignaciones.costurero'])->orderByDesc('id_orden')->get();
        $pasa = [
            'curso' => fn ($o) => $o->en_curso,
            'atrasadas' => fn ($o) => $o->situacion[0] === 'bad',
            'terminadas' => fn ($o) => in_array($o->estado, ['TERMINADO', 'ENTREGADO'], true),
            'canceladas' => fn ($o) => $o->cancelada,
            'todas' => fn ($o) => true,
        ];
        $conteos = collect($pasa)->map(fn ($f) => $todas->filter($f)->count());
        $ordenes = $todas->filter($pasa[$filtro]);
        $productos = Producto::with('receta.material')->orderBy('nombre')->get();
        return view('ordenes.index', compact('ordenes', 'filtros', 'filtro', 'conteos', 'productos'));
    }

    /** Nuevo pedido: crea el pedido, su orden y los materiales requeridos según la receta del producto */
    public function store(Request $r)
    {
        $this->permitir('pedido');
        $d = $r->validate([
            'cliente' => 'required|string|max:120',
            'id_producto' => 'required|exists:productos,id_producto',
            'cantidad' => 'required|integer|min:1|max:100000',
            'fecha_entrega' => 'required|date|after_or_equal:today',
            'especificaciones' => 'nullable|string|max:500',
        ], [
            'cliente.required' => 'Escribe el nombre del cliente.',
            'cantidad.min' => 'La cantidad debe ser mayor a 0.', 'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'fecha_entrega.after_or_equal' => 'La fecha de entrega no puede ser anterior a hoy.',
        ]);
        $orden = DB::transaction(function () use ($d) {
            $pedido = Pedido::create($d + ['estado' => 'EN_PRODUCCION']);
            $orden = Orden::create(['id_pedido' => $pedido->id_pedido, 'fecha_inicio' => today(), 'fecha_limite' => $d['fecha_entrega'], 'estado' => 'PENDIENTE']);
            foreach (Producto::find($d['id_producto'])->receta as $x) {
                MaterialOrden::create(['id_orden' => $orden->id_orden, 'id_material' => $x->id_material,
                    'cantidad_requerida' => round($x->consumo_unitario * $d['cantidad'], 2)]);
            }
            $this->historial($orden, 'PENDIENTE', 'Orden creada desde el pedido');
            return $orden;
        });
        return redirect()->route('ordenes.show', $orden)->with('ok', "Orden {$orden->codigo} creada.");
    }

    public function show(Request $r, Orden $orden, Inventario $inv)
    {
        $orden->load(['pedido.producto', 'asignaciones.costurero', 'historial.usuario', 'movimientos.material', 'movimientos.usuario']);
        $acciones = ['entregar' => 'Entregar material', 'devolver' => 'Devolver sobrante', 'merma' => 'Reportar merma',
                     'estado' => 'Cambiar estado', 'reasignar' => 'Costurera', 'cancelar' => 'Cancelar orden'];
        $accion = array_key_exists($r->query('accion'), $acciones) ? $r->query('accion') : null;
        return view('ordenes.show', [
            'o' => $orden, 'resumen' => $inv->resumenOrden($orden), 'costo' => $inv->costoOrden($orden),
            'acciones' => $acciones, 'accion' => $accion,
            'materiales' => Material::orderBy('nombre')->get(), 'costureros' => Costurero::orderBy('nombre')->get(),
            'motivosMerma' => self::MOTIVOS_MERMA,
        ]);
    }

    public function entregar(Request $r, Orden $orden, Inventario $inv)
    {
        $this->permitir('entregar');
        $this->abierta($orden);
        $d = $r->validate(['id_material' => 'required|exists:materiales,id_material', 'cantidad' => 'required|numeric|min:0.01'],
            ['cantidad.min' => 'La cantidad debe ser mayor a 0.']);
        $mo = MaterialOrden::where(['id_orden' => $orden->id_orden, 'id_material' => $d['id_material']])->first();
        $adicional = $mo && $mo->cantidad_requerida > 0 && $mo->cantidad_entregada + $d['cantidad'] > $mo->cantidad_requerida;
        $inv->mover($d['id_material'], 'SALIDA_ORDEN', $d['cantidad'], -$d['cantidad'], $orden->id_orden,
            $adicional ? 'Entrega adicional (supera lo estimado)' : 'Entrega a la orden');
        $m = Material::find($d['id_material']);
        return $this->volver($orden, "Entregado {$this->n($d['cantidad'])} {$m->unidad} de {$m->nombre}." . ($m->bajo ? " {$m->nombre} llegó a su stock mínimo." : ''));
    }

    public function devolver(Request $r, Orden $orden, Inventario $inv)
    {
        $this->permitir('devolver');
        $d = $r->validate(['id_material' => 'required|exists:materiales,id_material', 'cantidad' => 'required|numeric|min:0.01']);
        // Se devuelve como máximo lo que la orden tiene en el taller (entregado - devuelto - merma)
        $enPoder = $inv->enPoder($orden, (int) $d['id_material']);
        if ($d['cantidad'] > $enPoder) {
            throw ValidationException::withMessages(['cantidad' => 'La orden solo tiene ' . $this->n($enPoder) . ' de ese material (ya descontadas la merma y las devoluciones).']);
        }
        $inv->mover($d['id_material'], 'DEVOLUCION', $d['cantidad'], $d['cantidad'], $orden->id_orden, 'Sobrante devuelto por el taller');
        return $this->volver($orden, 'Devolución registrada: el material regresó al almacén.');
    }

    public function merma(Request $r, Orden $orden, Inventario $inv)
    {
        $this->permitir('merma');
        $this->abierta($orden);
        $d = $r->validate(['id_material' => 'required|exists:materiales,id_material', 'cantidad' => 'required|numeric|min:0.01',
            'motivo' => 'required|in:' . implode(',', self::MOTIVOS_MERMA), 'detalle' => 'nullable|string|max:80']);
        $disponible = $inv->enPoder($orden, (int) $d['id_material']);
        if ($d['cantidad'] > $disponible) {
            throw ValidationException::withMessages(['cantidad' => 'La merma no puede superar el material que tiene la orden (' . $this->n($disponible) . ').']);
        }
        // La merma no cambia el stock del almacén: ese material ya salió a la orden.
        $inv->mover($d['id_material'], 'MERMA', $d['cantidad'], 0, $orden->id_orden, trim($d['motivo'] . (!empty($d['detalle']) ? ' · ' . $d['detalle'] : '')));
        return $this->volver($orden, 'Merma registrada.');
    }

    public function estado(Request $r, Orden $orden)
    {
        $this->permitir('estado');
        $this->abierta($orden);
        $d = $r->validate(['estado' => 'required|in:' . implode(',', Orden::ESTADOS), 'nota' => 'nullable|string|max:150']);
        $nuevo = array_search($d['estado'], Orden::ESTADOS, true);
        if (abs($nuevo - $orden->indice) !== 1) {
            throw ValidationException::withMessages(['estado' => 'Solo se puede avanzar o regresar una etapa a la vez.']);
        }
        if ($nuevo < $orden->indice && !trim($d['nota'] ?? '')) {
            throw ValidationException::withMessages(['nota' => 'Explica por qué la orden regresa de etapa.']);
        }
        if ($d['estado'] === 'EN_CONFECCION' && !$orden->costurero_actual) {
            throw ValidationException::withMessages(['estado' => 'Asigna una costurera antes de pasar a confección.']);
        }
        DB::transaction(function () use ($orden, $d) {
            $orden->update(['estado' => $d['estado']]);
            if ($d['estado'] === 'ENTREGADO') $orden->pedido->update(['estado' => 'ENTREGADO']);
            $this->historial($orden, $d['estado'], ($d['nota'] ?? null) ?: 'Cambio de estado');
        });
        return $this->volver($orden, "{$orden->codigo} pasó a " . Orden::ETIQUETAS[$d['estado']] . '.');
    }

    public function reasignar(Request $r, Orden $orden)
    {
        $this->permitir('reasignar');
        $this->abierta($orden);
        $d = $r->validate(['id_costurero' => 'required|exists:costureros,id_costurero', 'maquina' => 'nullable|string|max:60',
            'nota' => 'nullable|string|max:120']);
        $antes = $orden->costurero_actual;
        $maqAntes = optional($orden->asignaciones->last())->maquina;
        $maquina = trim($d['maquina'] ?? '') ?: null;
        if ($antes && $antes->id_costurero == $d['id_costurero'] && $maqAntes === $maquina) return $this->volver($orden, 'Sin cambios.');
        $nueva = Costurero::find($d['id_costurero']);
        DB::transaction(function () use ($orden, $nueva, $antes, $d, $maquina, $maqAntes) {
            Asignacion::create(['id_orden' => $orden->id_orden, 'id_costurero' => $nueva->id_costurero, 'maquina' => $maquina,
                'cantidad_piezas' => $orden->pedido->cantidad, 'fecha' => today()]);
            $cambio = ($antes && $antes->id_costurero == $nueva->id_costurero)
                ? 'Máquina: ' . ($maqAntes ?? 'sin máquina') . ' → ' . ($maquina ?? 'sin máquina') . ' (' . $nueva->nombre . ')'
                : 'Costurera: ' . ($antes->nombre ?? 'sin asignar') . ' → ' . $nueva->nombre . ($maquina ? ' · máquina: ' . $maquina : '');
            $this->historial($orden, $orden->estado, $cambio . (!empty($d['nota']) ? ' · ' . $d['nota'] : ''));
        });
        return $this->volver($orden, 'Costurera actualizada.');
    }

    public function cancelar(Request $r, Orden $orden, Inventario $inv)
    {
        $this->permitir('cancelar');
        $this->abierta($orden);
        $d = $r->validate(['nota' => 'required|string|max:150'], ['nota.required' => 'Escribe el motivo de la cancelación.']);
        DB::transaction(function () use ($orden, $d) {
            $orden->update(['estado' => 'CANCELADA']);
            $orden->pedido->update(['estado' => 'ANULADO']);
            $this->historial($orden, 'CANCELADA', 'Cancelada · ' . $d['nota']);
        });
        $quedo = $inv->resumenOrden($orden)->contains(fn ($f) => $f->en_poder > 0);
        return redirect()->route('ordenes.show', $quedo ? [$orden, 'accion' => 'devolver'] : [$orden])
            ->with('ok', "{$orden->codigo} cancelada." . ($quedo ? ' Registra la devolución del material que quedó en el taller.' : ''));
    }

    private function historial(Orden $o, string $estado, string $obs): void
    {
        EstadoOrden::create(['id_orden' => $o->id_orden, 'id_usuario' => Auth::id(), 'estado' => $estado, 'fecha' => now(), 'observacion' => $obs]);
    }

    private function abierta(Orden $o): void
    {
        if ($o->cerrada) throw ValidationException::withMessages(['orden' => 'Esta orden ya está ' . ($o->cancelada ? 'cancelada' : 'entregada') . '; no se puede modificar.']);
    }

    private function volver(Orden $o, string $msg) { return redirect()->route('ordenes.show', $o)->with('ok', $msg); }

    private function n(float $x): string { return rtrim(rtrim(number_format($x, 2, '.', ''), '0'), '.'); }
}
