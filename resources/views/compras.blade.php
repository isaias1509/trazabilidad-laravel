@extends('layouts.app')
@section('titulo', 'Compras')
@section('contenido')
@php $F = \App\Support\Fmt::class; $U = auth()->user(); @endphp
<header class="head"><div><h1>Compras</h1><p>Lo que se recibe suma al stock y queda en el kardex</p></div></header>
<section class="panel"><h2>Lista de compras sugerida <span class="count">{{ $sugeridos->count() }}</span></h2>
@if ($sugeridos->count())
  <div class="tablewrap"><table><thead><tr><th>Material</th><th class="r">Stock</th><th class="r">Mínimo</th><th class="r">Pendiente para órdenes</th><th class="r">Comprar</th><th></th></tr></thead><tbody>
  @foreach ($sugeridos as $m)<tr><td>{{ $m->nombre }}</td><td class="r num">{{ $F::n($m->stock_actual) }}</td><td class="r num">{{ $F::n($m->stock_minimo) }}</td><td class="r num">{{ $F::n($m->pendiente) }}</td><td class="r num"><b>{{ $F::n($m->sugerido) }} {{ $m->unidad }}</b></td>
    <td class="r">@if ($U->puede('compra'))<a class="btn ghost sm" href="{{ route('compras', ['material' => $m->id_material, 'cantidad' => $m->sugerido]) }}#compra">Registrar</a>@endif</td></tr>@endforeach
  </tbody></table></div>
  <p class="help">Sugerido = dos veces el mínimo + lo que aún necesitan las órdenes en curso − el stock actual.</p>
@else <div class="empty"><p><b>No hay compras pendientes por ahora</b></p></div>@endif
</section>
@if ($U->puede('compra'))
<form class="panel" id="compra" method="post" action="{{ route('compras.store') }}">@csrf<h2>Registrar compra recibida</h2><div class="form">
  <div class="field"><label for="id_proveedor">Proveedor</label><select id="id_proveedor" name="id_proveedor">@foreach ($proveedores as $p)<option value="{{ $p->id_proveedor }}" @selected(old('id_proveedor') == $p->id_proveedor)>{{ $p->razon_social }}</option>@endforeach</select></div>
  <div class="field"><label for="id_material">Material</label><select id="id_material" name="id_material">@foreach ($materiales as $m)<option value="{{ $m->id_material }}" @selected(old('id_material', $pre?->id_material) == $m->id_material)>{{ $m->nombre }} ({{ $m->unidad }})</option>@endforeach</select></div>
  <div class="field"><label for="cantidad">Cantidad recibida</label><input id="cantidad" name="cantidad" type="number" inputmode="decimal" min="0.01" step="0.01" required value="{{ old('cantidad', $preCantidad ?: 10) }}" @error('cantidad') aria-invalid="true" @enderror></div>
  <div class="field"><label for="cantidad_rechazada">De esa cantidad, rechazada por falla</label><input id="cantidad_rechazada" name="cantidad_rechazada" type="number" inputmode="decimal" min="0" step="0.01" value="{{ old('cantidad_rechazada', 0) }}" @error('cantidad_rechazada') aria-invalid="true" @enderror><small class="help">No entra al stock. Se devuelve al proveedor.</small></div>
  <div class="field"><label for="precio_unitario">Precio unitario (S/)</label><input id="precio_unitario" name="precio_unitario" type="number" inputmode="decimal" min="0" step="0.01" required value="{{ old('precio_unitario', $pre?->costo_promedio ?? 0) }}"></div>
  <div class="actions"><button class="btn" type="submit">Registrar entrada</button></div></div></form>
@else <section class="panel"><p class="locked">Tu rol ({{ $U->rol_nombre }}) no puede registrar compras. Pídeselo al administrador.</p></section>@endif
<section class="panel"><h2>Últimas entradas</h2>
@if ($entradas->count())
  <div class="tablewrap"><table><thead><tr><th>Fecha</th><th>Material</th><th class="r">Cantidad</th><th>Detalle</th></tr></thead><tbody>
  @foreach ($entradas as $x)<tr class="{{ $x->anulado ? 'anul' : '' }}"><td class="code nowrap">{{ $F::fecha($x->fecha, true) }}</td><td>{{ $x->material->nombre }}</td><td class="r num">+{{ $F::n($x->cantidad) }} {{ $x->material->unidad }}</td><td>{{ $x->motivo }}<br><small class="t-muted">{{ $x->usuario->nombre }}</small></td></tr>@endforeach
  </tbody></table></div>
@else <div class="empty"><p><b>Sin entradas registradas</b></p></div>@endif
</section>
@endsection
