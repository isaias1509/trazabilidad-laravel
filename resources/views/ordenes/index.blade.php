@extends('layouts.app')
@section('titulo', 'Órdenes')
@section('contenido')
@php $nuevo = request('nuevo') || $errors->hasAny(['cliente','cantidad','fecha_entrega']); @endphp
<header class="head"><div><h1>Órdenes de producción</h1><p>Del pedido del cliente a la entrega</p></div>
  @if (auth()->user()->puede('pedido'))<a class="btn" href="{{ route('ordenes', ['nuevo' => $nuevo ? null : 1, 'filtro' => $filtro]) }}">{{ $nuevo ? 'Cerrar' : 'Nuevo pedido' }}</a>@endif
</header>
@if ($nuevo && auth()->user()->puede('pedido'))
<form class="panel" method="post" action="{{ route('ordenes.store') }}">@csrf
  <h2>Nuevo pedido</h2>
  <div class="form">
    <div class="field"><label for="cliente">Cliente</label><input id="cliente" name="cliente" required maxlength="120" value="{{ old('cliente') }}" placeholder="Ej. I.E. Santa Rosa" autocomplete="off" autofocus @error('cliente') aria-invalid="true" @enderror></div>
    <div class="field"><label for="id_producto">Producto</label><select id="id_producto" name="id_producto">
      @foreach ($productos as $p)<option value="{{ $p->id_producto }}" @selected(old('id_producto') == $p->id_producto) data-receta="{{ $p->receta->map(fn($x)=>$x->consumo_unitario.'|'.$x->material->unidad.'|'.$x->material->nombre)->implode(';') }}">{{ $p->nombre }} {{ $p->talla_tamano }}</option>@endforeach
    </select></div>
    <div class="field"><label for="cantidad">Cantidad</label><input id="cantidad" name="cantidad" type="number" inputmode="numeric" min="1" step="1" value="{{ old('cantidad', 20) }}" required @error('cantidad') aria-invalid="true" @enderror></div>
    <div class="field"><label for="fecha_entrega">Fecha de entrega</label><input id="fecha_entrega" name="fecha_entrega" type="date" min="{{ today()->toDateString() }}" value="{{ old('fecha_entrega', today()->addDays(10)->toDateString()) }}" required @error('fecha_entrega') aria-invalid="true" @enderror></div>
    <div class="field"><label for="especificaciones">Detalle (opcional)</label><input id="especificaciones" name="especificaciones" maxlength="500" value="{{ old('especificaciones') }}" placeholder="Ej. tallas 8 a 12"></div>
    <div class="actions"><button class="btn" type="submit">Crear orden</button></div>
  </div>
  <p class="help" id="estimado"></p>
  <script>
  (()=>{const s=document.getElementById('id_producto'),c=document.getElementById('cantidad'),o=document.getElementById('estimado');
   const upd=()=>{const r=s.selectedOptions[0].dataset.receta,n=+c.value||0;o.textContent='Material estimado: '+r.split(';').map(x=>{const[k,u,m]=x.split('|');return `${+(k*n).toFixed(2)} ${u} de ${m}`}).join(' + ')};
   s.onchange=upd;c.oninput=upd;upd()})();
  </script>
</form>
@endif
<div class="seg" aria-label="Filtrar órdenes">
  @foreach ($filtros as $k => $t)<a href="{{ route('ordenes', ['filtro' => $k]) }}" @if ($k === $filtro) aria-current="true" @endif>{{ $t }} <span>{{ $conteos[$k] }}</span></a>@endforeach
</div>
<section class="panel">
  @if ($ordenes->count())<ul class="olist">@foreach ($ordenes as $o) @include('partials.orden-item') @endforeach</ul>
  @else <div class="empty"><p><b>No hay órdenes en este filtro</b></p></div>@endif
</section>
@endsection
