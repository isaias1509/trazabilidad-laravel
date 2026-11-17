@extends('layouts.app')
@section('titulo', 'Kardex')
@section('contenido')
@php $F = \App\Support\Fmt::class; $U = auth()->user(); $T = \App\Models\Movimiento::TIPOS; @endphp
<header class="head"><div><h1>Kardex</h1><p>Cada entrada y salida del material, con su saldo y la orden a la que fue</p></div></header>
<section class="panel">
  <form method="get" action="{{ route('kardex') }}" class="form one"><div class="field"><label for="material">Material</label>
    <select id="material" name="material" onchange="this.form.submit()">@foreach ($materiales as $x)<option value="{{ $x->id_material }}" @selected($m && $m->id_material === $x->id_material)>{{ $x->nombre }}</option>@endforeach</select></div>
    <noscript><button class="btn" type="submit">Ver</button></noscript></form>
@if ($m)
  <div class="facts"><div><span>Stock actual</span><b class="num">{{ $F::n($m->stock_actual) }} {{ $m->unidad }}</b></div><div><span>Mínimo</span><b class="num">{{ $F::n($m->stock_minimo) }}</b></div><div><span>Movimientos</span><b class="num">{{ $movs->total() }}</b></div></div>
  <div class="tablewrap"><table><thead><tr><th>Fecha</th><th>Tipo</th><th class="r">Cantidad</th><th class="r">Saldo</th><th>Orden</th><th>Detalle</th><th></th></tr></thead><tbody>
  @forelse ($movs as $x)
    <tr class="{{ $x->anulado ? 'anul' : '' }}"><td class="code nowrap">{{ $F::fecha($x->fecha, true) }}</td><td><span class="chip c-{{ $T[$x->tipo][1] }}">{{ $T[$x->tipo][0] }}</span></td>
      <td class="r num">@if ($x->efecto > 0)+{{ $F::n($x->efecto) }}@elseif ($x->efecto < 0)−{{ $F::n(abs($x->efecto)) }}@else {{ $F::n($x->cantidad) }} <small class="t-muted">sin efecto</small>@endif</td>
      <td class="r num">{{ $F::n($x->saldo) }}</td>
      <td class="nowrap">@if ($x->orden)<a class="link code" href="{{ route('ordenes.show', $x->orden) }}">{{ $x->orden->codigo }}</a>@endif</td>
      <td>{{ $x->motivo }}<br><small class="t-muted">{{ $x->usuario->nombre }}</small></td>
      <td class="r">@if (!$x->anulado && $x->tipo !== 'ANULACION' && $U->puede('anular'))
        @if ($confirmar === $x->id_movimiento)
          <form class="confirm" method="post" action="{{ route('kardex.anular', $x) }}">@csrf<input name="motivo" required maxlength="100" placeholder="Motivo de la anulación" aria-label="Motivo de la anulación" autofocus><button class="btn danger sm" type="submit">Anular</button><a class="btn ghost sm" href="{{ route('kardex', ['material' => $m->id_material]) }}">Volver</a></form>
        @else <a class="btn ghost sm" href="{{ route('kardex', ['material' => $m->id_material, 'anular' => $x->id_movimiento]) }}">Anular</a>@endif
      @endif</td></tr>
  @empty <tr><td colspan="7"><span class="t-muted">Sin movimientos.</span></td></tr>
  @endforelse
  </tbody></table></div>
  @if ($movs->hasPages())<div class="pager">@if ($movs->previousPageUrl())<a class="btn ghost sm" href="{{ $movs->previousPageUrl() }}">Más recientes</a>@endif<span class="help">Página {{ $movs->currentPage() }} de {{ $movs->lastPage() }}</span>@if ($movs->nextPageUrl())<a class="btn ghost sm" href="{{ $movs->nextPageUrl() }}">Anteriores</a>@endif</div>@endif
  <p class="help">Nada se borra: un registro equivocado se anula y el sistema crea el movimiento contrario. La merma en taller no cambia el stock del almacén porque ese material ya había salido a la orden.</p>
@endif
</section>
@endsection
