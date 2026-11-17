@extends('layouts.app')
@section('titulo', 'Materiales')
@section('contenido')
@php $F = \App\Support\Fmt::class; $U = auth()->user(); @endphp
<header class="head"><div><h1>Materiales</h1><p>Telas, insumos, empaques y consumibles con su stock mínimo</p></div></header>
<section class="panel"><div class="tablewrap"><table><thead><tr><th>Material</th><th>Tipo</th><th>Stock</th><th class="r">Mínimo</th><th>Estado</th><th></th></tr></thead><tbody>
@foreach ($materiales as $m)
  <tr><td><b>{{ $m->nombre }}</b><br><span class="code">{{ $m->codigo }}</span></td><td>{{ \App\Models\Material::TIPOS[$m->tipo] }}</td><td>@include('partials.stock')</td><td class="r num">{{ $F::n($m->stock_minimo) }}</td>
    <td>@if ($m->bajo)<span class="chip c-bad">Reponer</span>@else<span class="chip c-ok">Disponible</span>@endif</td>
    <td class="r nowrap"><a class="btn ghost sm" href="{{ route('kardex', ['material' => $m->id_material]) }}">Kardex</a>
      @if ($U->puede('ajustar'))<a class="btn ghost sm" href="{{ route('materiales', $ajuste === $m->id_material ? [] : ['ajuste' => $m->id_material]) }}">Ajustar</a>@endif</td></tr>
  @if ($ajuste === $m->id_material && $U->puede('ajustar'))
  <tr class="inline"><td colspan="6">
    <form class="subform" method="post" action="{{ route('materiales.ajustar', $m) }}">@csrf<h3>Ajustar inventario de {{ $m->nombre }}</h3><div class="form">
      <div class="field"><label for="contado">Cantidad contada físicamente</label><input id="contado" name="contado" type="number" inputmode="decimal" min="0" step="0.01" required value="{{ old('contado', $m->stock_actual) }}" autofocus><small class="help">El sistema dice {{ $F::n($m->stock_actual) }} {{ $m->unidad }}.</small></div>
      <div class="field"><label for="motivo">Motivo</label><select id="motivo" name="motivo">@foreach ($motivos as $x)<option>{{ $x }}</option>@endforeach</select></div>
      <div class="field"><label for="detalle">Detalle (opcional)</label><input id="detalle" name="detalle" maxlength="80" placeholder="Ej. bolsas mojadas por la lluvia"></div>
      <div class="actions"><button class="btn" type="submit">Guardar ajuste</button><a class="btn ghost" href="{{ route('materiales') }}">Cancelar</a></div></div></form>
  </td></tr>
  @endif
@endforeach
</tbody></table></div>
@unless ($U->puede('ajustar'))<p class="help">Los ajustes de inventario solo los hace el administrador.</p>@endunless
</section>
@if ($U->puede('material'))
<form class="panel" method="post" action="{{ route('materiales.store') }}">@csrf<h2>Registrar material nuevo</h2><div class="form">
  <div class="field"><label for="nombre">Nombre</label><input id="nombre" name="nombre" required maxlength="120" value="{{ old('nombre') }}" placeholder="Ej. Cartón 40×30" autocomplete="off" @error('nombre') aria-invalid="true" @enderror></div>
  <div class="field"><label for="tipo">Tipo</label><select id="tipo" name="tipo">@foreach (\App\Models\Material::TIPOS as $k => $t)<option value="{{ $k }}" @selected(old('tipo') === $k)>{{ $t }}</option>@endforeach</select></div>
  <div class="field"><label for="unidad">Unidad</label><input id="unidad" name="unidad" required maxlength="20" value="{{ old('unidad', 'unidades') }}"></div>
  <div class="field"><label for="stock_minimo">Stock mínimo</label><input id="stock_minimo" name="stock_minimo" type="number" inputmode="decimal" min="0" step="0.01" value="{{ old('stock_minimo', 5) }}" required><small class="help">Cuando el stock llegue aquí, el sistema avisa.</small></div>
  <div class="field"><label for="costo_promedio">Precio de referencia (S/)</label><input id="costo_promedio" name="costo_promedio" type="number" inputmode="decimal" min="0" step="0.01" value="{{ old('costo_promedio', 0) }}"></div>
  <div class="actions"><button class="btn" type="submit">Guardar material</button></div></div>
  <p class="help">El material empieza con stock 0. Para sumarle stock, registra la compra en Compras.</p></form>
@endif
@endsection
