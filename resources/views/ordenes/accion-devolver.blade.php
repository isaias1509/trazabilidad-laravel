@php $F = \App\Support\Fmt::class; @endphp
@if ($conNeto->count())
<form class="subform" method="post" action="{{ route('ordenes.devolver', $o) }}">@csrf<div class="form">
  <div class="field"><label for="id_material">Material que regresa al almacén</label><select id="id_material" name="id_material">
    @foreach ($conNeto as $f)<option value="{{ $f->material->id_material }}" @selected(old('id_material') == $f->material->id_material)>{{ $f->material->nombre }} · {{ $F::n($f->en_poder) }} {{ $f->material->unidad }} en la orden</option>@endforeach</select></div>
  <div class="field"><label for="cantidad">Cantidad</label><input id="cantidad" name="cantidad" type="number" inputmode="decimal" min="0.01" step="0.01" required value="{{ old('cantidad', 1) }}" @error('cantidad') aria-invalid="true" @enderror><small class="help">El stock del almacén sube.</small></div>
  <div class="actions"><button class="btn" type="submit">Registrar devolución</button></div></div></form>
@else <p class="help">Esta orden no tiene material entregado para devolver.</p>@endif
