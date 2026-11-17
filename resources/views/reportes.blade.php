@extends('layouts.app')
@section('titulo', 'Reportes')
@section('contenido')
@php $F = \App\Support\Fmt::class; @endphp
<header class="head"><div><h1>Reportes</h1><p>Merma, consumo y costo de materiales</p></div></header>
<section class="panel"><h2>Merma y consumo de tela por orden</h2>
@if ($filas->count())
  <div class="tablewrap"><table><thead><tr><th>Orden</th><th>Costurera</th><th class="r">Estimado</th><th class="r">Usado neto</th><th class="r">Diferencia</th><th class="r">Merma</th><th class="r">% merma</th><th class="r">Costo materiales</th></tr></thead><tbody>
  @foreach ($filas as $f)<tr><td><a class="link" href="{{ route('ordenes.show', $f->o) }}"><span class="code">{{ $f->o->codigo }}</span> {{ $f->o->pedido->cliente }}</a></td><td>{{ $f->costurera }}</td>
    <td class="r num">{{ $F::n($f->requerido) }} m</td><td class="r num">{{ $F::n($f->neto) }} m</td>
    <td class="r num">@if ($f->diferencia > 0)<b class="t-bad">+{{ $F::n($f->diferencia) }}</b>@else {{ $F::n($f->diferencia) }} @endif</td>
    <td class="r num">{{ $F::n($f->merma) }} m</td><td class="r num">@if ($f->pct >= 5)<b class="t-bad">{{ $F::n($f->pct) }} %</b>@else {{ $F::n($f->pct) }} % @endif</td><td class="r num">{{ $F::sol($f->costo) }}</td></tr>@endforeach
  </tbody></table></div>
  <p class="help">Se marca en rojo la merma de 5 % o más y el consumo por encima de lo estimado.</p>
@else <div class="empty"><p><b>Todavía no hay órdenes con material entregado</b></p></div>@endif
</section>
<section class="panel"><h2>Merma por costurera</h2>
@if ($porCosturera->count())
  <div class="tablewrap"><table><thead><tr><th>Costurera</th><th class="r">Órdenes</th><th class="r">Tela usada</th><th class="r">Merma</th><th class="r">% merma</th></tr></thead><tbody>
  @foreach ($porCosturera as $c => $v)<tr><td>{{ $c }}</td><td class="r num">{{ $v->ordenes }}</td><td class="r num">{{ $F::n($v->neto) }} m</td><td class="r num">{{ $F::n($v->merma) }} m</td><td class="r num">{{ $F::n($v->pct) }} %</td></tr>@endforeach
  </tbody></table></div>
@else <div class="empty"><p><b>Sin datos</b></p></div>@endif
</section>
@endsection
