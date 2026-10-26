<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('judul', 'Hitung Gamis')</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;600;700&family=Share+Tech+Mono&display=swap">
  <link rel="stylesheet" href="{{ asset('css/gamis.css') }}">
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>
  @stack('head')
</head>
<body>
<main>
  <h1>Hitung Gamis</h1>
  <nav aria-label="Menu utama">
    <a class="t" href="{{ route('kasir') }}" @if(request()->routeIs('kasir')) aria-pressed="true" @endif>Kasir</a>
    <a class="t" href="{{ route('pesanan.index') }}" @if(request()->routeIs('pesanan.*')) aria-pressed="true" @endif>Riwayat</a>
    <a class="t" href="{{ route('laporan') }}" @if(request()->routeIs('laporan')) aria-pressed="true" @endif>Laporan</a>
    <a class="t" href="{{ route('admin.harga.edit') }}" @if(request()->routeIs('admin.*')) aria-pressed="true" @endif>Admin harga</a>
  </nav>

  @yield('isi')
</main>

@yield('bar') {{-- bar bawah khusus halaman kasir --}}

@if(session('pesan'))
  <div class="toast" x-data="{ s: true }" x-show="s" x-init="setTimeout(() => s = false, 2600)">{{ session('pesan') }}</div>
@endif
@if($errors->has('server'))
  <div class="toast" x-data="{ s: true }" x-show="s" x-init="setTimeout(() => s = false, 4000)">{{ $errors->first('server') }}</div>
@endif
</body>
</html>
