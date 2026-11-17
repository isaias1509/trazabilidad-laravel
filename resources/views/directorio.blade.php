@extends('layouts.app')
@section('titulo', 'Directorio')
@section('contenido')
@php $U = auth()->user(); $puede = $U->puede('directorio'); @endphp
<header class="head"><div><h1>Directorio</h1><p>Proveedores de telas e insumos, y costureras a las que se asignan las órdenes</p></div></header>

<section class="panel"><h2>Proveedores <span class="count">{{ $proveedores->count() }}</span></h2>
@if ($proveedores->count())
  <div class="tablewrap"><table><thead><tr><th>Razón social</th><th>RUC</th><th>Teléfono</th><th>Rubro</th><th class="r">Compras</th></tr></thead><tbody>
  @foreach ($proveedores as $p)<tr><td><b>{{ $p->razon_social }}</b></td><td class="code">{{ $p->ruc ?: '—' }}</td><td>{{ $p->telefono ?: '—' }}</td><td>{{ $p->rubro ?: '—' }}</td><td class="r num">{{ $p->compras_count }}</td></tr>@endforeach
  </tbody></table></div>
@else <div class="empty"><p><b>Sin proveedores registrados</b></p></div>@endif
</section>
@if ($puede)
<form class="panel" method="post" action="{{ route('directorio.proveedor') }}">@csrf<h2>Registrar proveedor</h2><div class="form">
  <div class="field"><label for="razon_social">Razón social</label><input id="razon_social" name="razon_social" required maxlength="150" value="{{ old('razon_social') }}" placeholder="Ej. Textiles Gamarra S.A.C." autocomplete="off" @error('razon_social') aria-invalid="true" @enderror></div>
  <div class="field"><label for="ruc">RUC (opcional)</label><input id="ruc" name="ruc" inputmode="numeric" maxlength="11" pattern="[0-9]{11}" value="{{ old('ruc') }}" placeholder="11 dígitos" @error('ruc') aria-invalid="true" @enderror></div>
  <div class="field"><label for="telefono">Teléfono (opcional)</label><input id="telefono" name="telefono" type="tel" maxlength="20" value="{{ old('telefono') }}"></div>
  <div class="field"><label for="rubro">Rubro (opcional)</label><input id="rubro" name="rubro" maxlength="80" value="{{ old('rubro') }}" placeholder="Ej. Telas, Insumos, Empaques"></div>
  <div class="actions"><button class="btn" type="submit">Guardar proveedor</button></div></div></form>
@endif

<section class="panel"><h2>Costureras <span class="count">{{ $costureros->count() }}</span></h2>
@if ($costureros->count())
  <div class="tablewrap"><table><thead><tr><th>Nombre</th><th>Zona</th><th>Teléfono</th><th class="r">Asignaciones</th></tr></thead><tbody>
  @foreach ($costureros as $c)<tr><td><b>{{ $c->nombre }}</b></td><td>{{ $c->zona ?: '—' }}</td><td>{{ $c->telefono ?: '—' }}</td><td class="r num">{{ $c->asignaciones_count }}</td></tr>@endforeach
  </tbody></table></div>
@else <div class="empty"><p><b>Sin costureras registradas</b></p></div>@endif
</section>
@if ($puede)
<form class="panel" method="post" action="{{ route('directorio.costurero') }}">@csrf<h2>Registrar costurera o costurero</h2><div class="form">
  <div class="field"><label for="nombre">Nombre</label><input id="nombre" name="nombre" required maxlength="100" value="{{ old('nombre') }}" autocomplete="off" @error('nombre') aria-invalid="true" @enderror></div>
  <div class="field"><label for="zona">Zona (opcional)</label><input id="zona" name="zona" maxlength="60" value="{{ old('zona') }}" placeholder="Ej. Huaycán"></div>
  <div class="field"><label for="telefono_c">Teléfono (opcional)</label><input id="telefono_c" name="telefono" type="tel" maxlength="20" value="{{ old('telefono') }}"></div>
  <div class="actions"><button class="btn" type="submit">Guardar</button></div></div></form>
@else <section class="panel"><p class="locked">Tu rol ({{ $U->rol_nombre }}) solo puede consultar el directorio. Los registros los hace el administrador o control de materiales.</p></section>@endif
@endsection
