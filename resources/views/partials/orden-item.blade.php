@php [$k, $t] = $o->situacion; @endphp
<li><a href="{{ route('ordenes.show', $o) }}">
  <span class="code">{{ $o->codigo }}</span>
  <span class="oc"><b>{{ $o->pedido->cliente }}</b><small>{{ \App\Support\Fmt::n($o->pedido->cantidad) }} × {{ $o->pedido->producto->nombre }} · {{ \App\Support\Fmt::fecha($o->fecha_limite) }}</small></span>
  <span class="row">@include('partials.estado', ['e' => $o->estado])@if (in_array($k, ['bad','warn']))<span class="chip c-{{ $k }}">{{ $t }}</span>@endif</span>
</a></li>
