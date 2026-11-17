@extends('layouts.app')
@section('titulo', $o->codigo)
@section('contenido')
@php
  $F = \App\Support\Fmt::class; $U = auth()->user(); [$sk, $st] = $o->situacion;
  $permiso = ['entregar'=>'entregar','devolver'=>'devolver','merma'=>'merma','estado'=>'estado','reasignar'=>'reasignar','cancelar'=>'cancelar'];
  $conNeto = $resumen->filter(fn($f) => $f->en_poder > 0);
  $cos = $o->costurero_actual;
@endphp
<nav class="crumb"><a class="link" href="{{ route('ordenes') }}">← Órdenes</a></nav>
<header class="head"><div>
  <h1><span class="code">{{ $o->codigo }}</span> {{ $o->pedido->cliente }}</h1>
  <p>{{ $F::n($o->pedido->cantidad) }} × {{ $o->pedido->producto->nombre }} · entrega {{ $F::fecha($o->fecha_limite) }} · {{ $cos ? $cos->nombre.' · '.$cos->zona : 'sin costurera asignada' }}</p></div>
  <div class="row">@include('partials.estado', ['e' => $o->estado])<span class="chip c-{{ $sk }}">{{ $st }}</span></div>
</header>
@if (!$o->cancelada)
<ol class="steps">@foreach (\App\Models\Orden::ESTADOS as $i => $e)<li class="{{ $i < $o->indice ? 'done' : ($i === $o->indice ? 'now' : '') }}">{{ \App\Models\Orden::ETIQUETAS[$e] }}</li>@endforeach</ol>
@endif

<section class="panel"><h2>Materiales de la orden</h2>
  <div class="tablewrap"><table><thead><tr><th>Material</th><th class="r">Estimado</th><th class="r">Entregado</th><th class="r">Devuelto</th><th class="r">Merma</th><th class="r">Usado neto</th><th class="r">Diferencia</th></tr></thead><tbody>
  @forelse ($resumen as $f)
    <tr><td>{{ $f->material->nombre }}</td><td class="r num">{{ $f->requerido ? $F::n($f->requerido) : '—' }}</td><td class="r num">{{ $F::n($f->entregado) }}</td><td class="r num">{{ $F::n($f->devuelto) }}</td>
      <td class="r num">@if ($f->merma)<b class="t-warn">{{ $F::n($f->merma) }}</b>@else 0 @endif</td><td class="r num">{{ $F::n($f->neto) }} {{ $f->material->unidad }}</td>
      <td class="r num">@if ($f->diferencia === null) — @elseif ($f->diferencia > 0)<b class="t-bad">+{{ $F::n($f->diferencia) }}</b>@elseif ($f->diferencia < 0)<span class="t-muted">{{ $F::n($f->diferencia) }} por entregar</span>@else 0 @endif</td></tr>
  @empty <tr><td colspan="7"><span class="t-muted">Sin materiales.</span></td></tr>
  @endforelse
  </tbody></table></div>
  <p class="help">Estimado = cantidad × consumo por unidad. Una diferencia positiva significa que se usó más material del previsto. Costo de materiales: <b class="num">{{ $F::sol($costo) }}</b>.</p>
</section>

@if ($o->cerrada)
<section class="panel">
  <p class="help">Esta orden está {{ $o->cancelada ? 'cancelada' : 'entregada' }}. {{ $o->cancelada ? 'Solo se puede devolver el material que quedó en el taller.' : 'Solo se puede consultar o devolver sobrantes.' }}</p>
  @if ($U->puede('devolver') && $conNeto->count())
    @if ($accion !== 'devolver')<div class="row"><a class="btn ghost" href="{{ route('ordenes.show', [$o, 'accion' => 'devolver']) }}">Devolver sobrante</a></div>@endif
  @endif
  @if ($accion === 'devolver') @include('ordenes.accion-devolver') @endif
</section>
@else
<section class="panel"><h2>Registrar en esta orden</h2>
  <div class="seg acts">
    @foreach ($acciones as $k => $t)
      @if ($U->puede($permiso[$k]))<a href="{{ route('ordenes.show', $accion === $k ? [$o] : [$o, 'accion' => $k]) }}" @if ($accion === $k) aria-current="true" @endif class="{{ $k === 'cancelar' ? 'danger' : '' }}">{{ $t }}</a>
      @else<span class="off" title="Tu rol no tiene permiso">{{ $t }}</span>@endif
    @endforeach
  </div>
  @if (collect($permiso)->contains(fn($p) => !$U->puede($p)))<p class="help">Las acciones punteadas no corresponden a tu rol ({{ $U->rol_nombre }}).</p>@endif

  @if ($accion === 'entregar')
    @php $falta = $resumen->first(fn($f) => $f->requerido > $f->neto); @endphp
    <form class="subform" method="post" action="{{ route('ordenes.entregar', $o) }}">@csrf<div class="form">
      <div class="field"><label for="id_material">Material</label><select id="id_material" name="id_material">
        @foreach ($materiales as $m)<option value="{{ $m->id_material }}" @selected(old('id_material', $falta?->material->id_material) == $m->id_material)>{{ $m->nombre }} · {{ $F::n($m->stock_actual) }} {{ $m->unidad }} en almacén</option>@endforeach</select></div>
      <div class="field"><label for="cantidad">Cantidad</label><input id="cantidad" name="cantidad" type="number" inputmode="decimal" min="0.01" step="0.01" required value="{{ old('cantidad', $falta ? round($falta->requerido - $falta->neto, 2) : 1) }}" @error('cantidad') aria-invalid="true" @enderror>
        @if ($falta)<small class="help">Por entregar: {{ $F::n($falta->requerido - $falta->neto) }} {{ $falta->material->unidad }} de {{ $falta->material->nombre }} según lo estimado.</small>@endif</div>
      <div class="actions"><button class="btn" type="submit">Entregar</button></div></div></form>
  @elseif ($accion === 'devolver')
    @include('ordenes.accion-devolver')
  @elseif ($accion === 'merma')
    @if ($conNeto->count())
    <form class="subform" method="post" action="{{ route('ordenes.merma', $o) }}">@csrf<div class="form">
      <div class="field"><label for="id_material">Material</label><select id="id_material" name="id_material">
        @foreach ($conNeto as $f)<option value="{{ $f->material->id_material }}" @selected(old('id_material') == $f->material->id_material)>{{ $f->material->nombre }} · {{ $F::n($f->en_poder) }} {{ $f->material->unidad }} en la orden</option>@endforeach</select></div>
      <div class="field"><label for="cantidad">Cantidad perdida</label><input id="cantidad" name="cantidad" type="number" inputmode="decimal" min="0.01" step="0.01" required value="{{ old('cantidad', 1) }}" @error('cantidad') aria-invalid="true" @enderror></div>
      <div class="field"><label for="motivo">Motivo</label><select id="motivo" name="motivo">@foreach ($motivosMerma as $mm)<option @selected(old('motivo') === $mm)>{{ $mm }}</option>@endforeach</select></div>
      <div class="field"><label for="detalle">Detalle (opcional)</label><input id="detalle" name="detalle" maxlength="80" value="{{ old('detalle') }}" placeholder="Ej. tela con hilos corridos"></div>
      <div class="actions"><button class="btn" type="submit">Reportar merma</button></div></div>
      <p class="help">La merma no cambia el stock del almacén: ese material ya salió a la orden. Queda registrada para el reporte de merma.</p></form>
    @else <p class="help">Primero entrega material a la orden; la merma se reporta sobre lo entregado.</p>@endif
  @elseif ($accion === 'estado')
    @php $sig = \App\Models\Orden::ESTADOS[$o->indice + 1] ?? null; $ant = $o->indice > 0 ? \App\Models\Orden::ESTADOS[$o->indice - 1] : null; @endphp
    <form class="subform" method="post" action="{{ route('ordenes.estado', $o) }}">@csrf<div class="form">
      <div class="field"><label for="estado">Cambio</label><select id="estado" name="estado">
        @if ($sig)<option value="{{ $sig }}">Avanzar a {{ \App\Models\Orden::ETIQUETAS[$sig] }}</option>@endif
        @if ($ant)<option value="{{ $ant }}" @selected(old('estado') === $ant)>Regresar a {{ \App\Models\Orden::ETIQUETAS[$ant] }} (reproceso)</option>@endif</select></div>
      <div class="field"><label for="nota">Nota</label><input id="nota" name="nota" maxlength="150" value="{{ old('nota') }}" placeholder="Ej. 5 faldas con costura torcida" @error('nota') aria-invalid="true" @enderror><small class="help">Si regresas de etapa, explica por qué.</small></div>
      <div class="actions"><button class="btn" type="submit">Guardar</button></div></div></form>
  @elseif ($accion === 'reasignar')
    <form class="subform" method="post" action="{{ route('ordenes.reasignar', $o) }}">@csrf<div class="form">
      <div class="field"><label for="id_costurero">Costurera</label><select id="id_costurero" name="id_costurero">
        @foreach ($costureros as $c)<option value="{{ $c->id_costurero }}" @selected($cos?->id_costurero == $c->id_costurero)>{{ $c->nombre }} · {{ $c->zona }}</option>@endforeach</select></div>
      <div class="field"><label for="maquina">Máquina</label><input id="maquina" name="maquina" maxlength="60" value="{{ old('maquina', optional($o->asignaciones->last())->maquina) }}" placeholder="Ej. Recta industrial" @error('maquina') aria-invalid="true" @enderror></div>
      <div class="field"><label for="nota">Motivo del cambio (opcional)</label><input id="nota" name="nota" maxlength="120" value="{{ old('nota') }}" placeholder="Ej. la costurera está con otro pedido"></div>
      <div class="actions"><button class="btn" type="submit">Guardar</button></div></div></form>
  @elseif ($accion === 'cancelar')
    <form class="subform danger-zone" method="post" action="{{ route('ordenes.cancelar', $o) }}">@csrf<div class="form">
      <div class="field"><label for="nota">Motivo de la cancelación</label><input id="nota" name="nota" required maxlength="150" value="{{ old('nota') }}" placeholder="Ej. el cliente anuló el pedido" @error('nota') aria-invalid="true" @enderror></div>
      <div class="actions"><button class="btn danger" type="submit">Cancelar la orden</button><a class="btn ghost" href="{{ route('ordenes.show', $o) }}">Volver</a></div></div>
      <p class="help">La orden no se borra: queda como cancelada en el historial.@if ($conNeto->count()) Después registra la devolución del material que quedó en el taller.@endif</p></form>
  @else
    <p class="help">Elige qué pasó con la orden. Cada registro queda en el historial con fecha y usuario.</p>
  @endif
</section>
@endif

@php
  $T = \App\Models\Movimiento::TIPOS;
  $linea = $o->historial->map(fn($h) => ['f' => $h->fecha, 'html' => view('partials.estado', ['e' => $h->estado])->render().' '.e($h->observacion), 'u' => $h->usuario->nombre, 'anul' => false])
    ->concat($o->movimientos->map(fn($x) => ['f' => $x->fecha, 'html' => '<span class="chip c-'.$T[$x->tipo][1].'">'.$T[$x->tipo][0].'</span> '.e($F::n($x->cantidad).' '.$x->material->unidad.' de '.$x->material->nombre).($x->motivo ? ' · '.e($x->motivo) : '').($x->anulado ? ' <span class="t-muted">(anulado)</span>' : ''), 'u' => $x->usuario->nombre, 'anul' => $x->anulado]))
    ->sortByDesc('f');
@endphp
<section class="panel"><h2>Historial</h2>
  <ol class="timeline">@foreach ($linea as $h)<li class="{{ $h['anul'] ? 'anul' : '' }}"><time>{{ $F::fecha($h['f'], true) }}</time><div>{!! $h['html'] !!}<small>{{ $h['u'] }}</small></div></li>@endforeach</ol>
</section>
@endsection
