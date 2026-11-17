@extends('layouts.app')
@section('titulo', 'Hoy')
@section('contenido')
@php $F = \App\Support\Fmt::class; $total = $atrasadas->count() + $bajos->count() + $porVencer->count(); @endphp
<header class="head"><div><h1>Hoy</h1><p>{{ $F::fecha(today()) }} · Lo que necesita atención primero</p></div></header>
<section class="panel"><h2>Para atender <span class="count">{{ $total }}</span></h2>
  @if ($total)
  <ul class="alerts">
    @foreach ($atrasadas as $o)
      <li class="alert a-bad"><div><b>{{ $o->codigo }} · {{ $o->pedido->cliente }} está atrasada</b><p>Debía entregarse el {{ $F::fecha($o->fecha_limite) }} y sigue en {{ \App\Models\Orden::ETIQUETAS[$o->estado] }}.</p></div><a class="btn ghost sm" href="{{ route('ordenes.show', $o) }}">Ver orden</a></li>
    @endforeach
    @foreach ($bajos as $m)
      <li class="alert a-bad"><div><b>{{ $m->nombre }}: quedan {{ $F::n($m->stock_actual) }} {{ $m->unidad }}</b><p>El mínimo es {{ $F::n($m->stock_minimo) }} {{ $m->unidad }}. Sugerido comprar {{ $F::n($m->sugerido) }} {{ $m->unidad }}.</p></div><a class="btn ghost sm" href="{{ route('compras', ['material' => $m->id_material, 'cantidad' => $m->sugerido]) }}">Registrar compra</a></li>
    @endforeach
    @foreach ($porVencer as $o)
      <li class="alert a-warn"><div><b>{{ $o->codigo }} · {{ $o->pedido->cliente }}: {{ mb_strtolower($o->situacion[1]) }}</b><p>Está en {{ \App\Models\Orden::ETIQUETAS[$o->estado] }}.</p></div><a class="btn ghost sm" href="{{ route('ordenes.show', $o) }}">Ver orden</a></li>
    @endforeach
  </ul>
  @else
  <div class="empty"><p><b>Todo en orden</b></p><p>No hay órdenes atrasadas ni materiales bajo el mínimo.</p></div>
  @endif
</section>
<section class="panel"><h2>Órdenes en curso <span class="count">{{ $enCurso->count() }}</span></h2>
  @if ($enCurso->count())<ul class="olist">@foreach ($enCurso as $o) @include('partials.orden-item') @endforeach</ul>
  @else <div class="empty"><p><b>No hay órdenes en curso</b></p><p>Registra un pedido desde Órdenes.</p></div>@endif
</section>
@endsection
