@php
    $u = auth()->user();
    $nBajo = \App\Models\Material::whereColumn('stock_actual', '<=', 'stock_minimo')->count();
    $nAtr = \App\Models\Orden::whereIn('estado', ['PENDIENTE','EN_CORTE','EN_CONFECCION','EN_ESTAMPADO'])->whereDate('fecha_limite', '<', today())->count();
    $tabs = ['hoy' => ['Hoy', 0], 'ordenes' => ['Órdenes', $nAtr], 'materiales' => ['Materiales', $nBajo], 'compras' => ['Compras', 0], 'kardex' => ['Kardex', 0], 'reportes' => ['Reportes', 0], 'directorio' => ['Directorio', 0]];
    $actual = explode('.', request()->route()->getName())[0];
@endphp
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('titulo', 'Trazabilidad') · M&amp;V Textil</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;700;800&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap">
<link rel="stylesheet" href="{{ asset('css/estilos.css') }}">
</head>
<body>
<div id="app">
  <aside>
    <div class="brandmark"><b>M&amp;V</b><span>TEXTIL</span></div>
    <nav aria-label="Módulos">
      @foreach ($tabs as $ruta => [$texto, $n])
        <a href="{{ route($ruta) }}" @if ($actual === $ruta) aria-current="page" @endif>{{ $texto }}@if ($n)<span class="badge" aria-label="{{ $n }} alertas">{{ $n }}</span>@endif</a>
      @endforeach
    </nav>
    <div class="user">
      <div id="who"><b>{{ $u->nombre }}</b><span>{{ $u->rol_nombre }}</span></div>
      <form method="post" action="{{ route('logout') }}" class="user-actions">@csrf<button class="btn ghost sm" type="submit">Cerrar sesión</button></form>
    </div>
  </aside>
  <main>
    @if ($errors->any())
      <div class="errors" role="alert"><b>Revisa lo siguiente:</b><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    @yield('contenido')
  </main>
</div>
@if (session('ok'))<div class="toast" id="toast" role="status" aria-live="polite">{{ session('ok') }}</div>
<script>setTimeout(()=>{const t=document.getElementById('toast');if(t)t.hidden=true},4000)</script>@endif
</body>
</html>
