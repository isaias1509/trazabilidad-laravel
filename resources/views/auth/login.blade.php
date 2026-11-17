<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ingresar · M&amp;V Textil</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;700;800&family=IBM+Plex+Sans:wght@400;500;600&display=swap">
<link rel="stylesheet" href="{{ asset('css/estilos.css') }}">
</head>
<body>
<section id="login">
  <form class="login-card" method="post" action="{{ url('/login') }}" novalidate>
    @csrf
    <div class="brandmark"><b>M&amp;V</b><span>TEXTIL</span></div>
    <div><h1 class="login-title">Trazabilidad de materiales y órdenes</h1></div>
    <div class="field"><label for="correo">Correo</label>
      <input id="correo" name="correo" type="email" autocomplete="username" value="{{ old('correo') }}" required @error('correo') aria-invalid="true" @enderror></div>
    <div class="field"><label for="password">Contraseña</label>
      <input id="password" name="password" type="password" autocomplete="current-password" required></div>
    @if ($errors->any())<p class="err">{{ $errors->first() }}</p>@endif
    <button class="btn" type="submit">Ingresar</button>
    @if (app()->environment('local'))
    <p class="hint">Usuarios de prueba: lorenzo@, alejandro@, almacen@, ventas@ o consulta@mvtextil.pe · contraseña demo123</p>
    @endif
  </form>
</section>
</body>
</html>
