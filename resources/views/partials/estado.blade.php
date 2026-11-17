@php
  $cls = $e === 'CANCELADA' ? 'cancel' : (in_array($e, ['TERMINADO','ENTREGADO']) ? 'ok' : ($e === 'PENDIENTE' ? 'warn' : 'brand'));
@endphp
<span class="chip c-{{ $cls }}">{{ \App\Models\Orden::ETIQUETAS[$e] ?? $e }}</span>
