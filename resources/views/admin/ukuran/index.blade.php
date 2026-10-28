@extends('layouts.app')
@section('isi')
<div style="margin-bottom:12px">
  <a href="{{ route('admin.ukuran.create') }}" class="btn">Tambah ukuran</a>
</div>

@forelse($ukuran as $s)
  <div class="row">
    <div class="sz">{{ $s->category->nama }} {{ $s->nama }}</div>
    <div class="pr">Rp {{ number_format($s->harga, 0, ',', '.') }}</div>
    <div class="st" style="gap:4px">
      <a href="{{ route('admin.ukuran.edit', $s) }}" class="btn g s">Ubah</a>
      <a href="{{ route('admin.ukuran.riwayat', $s) }}" class="btn g s">Riwayat</a>
      <form method="POST" action="{{ route('admin.ukuran.destroy', $s) }}" style="display:inline" x-data="{ yakin: false }">
        @csrf @method('DELETE')
        <button type="button" class="btn g s" x-show="!yakin" @click="yakin = true">Hapus</button>
        <button type="submit" class="btn g s" x-show="yakin" x-cloak>Yakin?</button>
      </form>
    </div>
  </div>
@empty
  <p class="mut">Belum ada ukuran.</p>
@endforelse
@endsection
