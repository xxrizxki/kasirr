@extends('layouts.app')
@section('isi')
<div class="card" style="margin-bottom:12px">
  <b>{{ $size->category->nama }} {{ $size->nama }}</b>
  <div class="mut">Harga saat ini: Rp {{ number_format($size->harga, 0, ',', '.') }}</div>
</div>

@forelse($logs as $log)
  <div class="card mut">
    Rp {{ number_format($log->harga_lama, 0, ',', '.') }} → Rp {{ number_format($log->harga_baru, 0, ',', '.') }}
    <div style="font-size:.75rem; margin-top:4px">{{ \Carbon\Carbon::parse($log->diubah_pada)->format('Y-m-d H:i') }}</div>
  </div>
@empty
  <p class="mut">Belum ada perubahan harga.</p>
@endforelse

<div style="margin-top:12px">
  <a href="{{ route('admin.ukuran.index') }}" class="btn g">Kembali</a>
</div>
@endsection
