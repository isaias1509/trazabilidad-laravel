@php $pct = min(100, round($m->stock_actual / max($m->stock_minimo * 3, 1) * 100)); @endphp
<div class="stock"><span class="num">{{ \App\Support\Fmt::n($m->stock_actual) }} {{ $m->unidad }}</span><div class="bar" aria-hidden="true"><i class="{{ $m->bajo ? 'low' : '' }}" style="width:{{ $pct }}%"></i></div></div>
