@extends('layouts.app')
@section('isi')
<div style="margin-bottom:12px">
  <a href="{{ route('admin.kategori.create') }}" class="btn">Tambah kategori</a>
</div>

@forelse($kategori as $c)
  <div class="card">
    <div class="h" style="justify-content:space-between">
      <div>
        <b>{{ $c->nama }}</b>
        <div class="mut">{{ $c->kode }} · {{ $c->sizes->count() }} ukuran</div>
      </div>
      <div class="aksi">
        <a href="{{ route('admin.kategori.edit', $c) }}" class="btn g s">Ubah</a>
        <form method="POST" action="{{ route('admin.kategori.destroy', $c) }}" style="display:inline" x-data="{ yakin: false }">
          @csrf @method('DELETE')
          <button type="button" class="btn g s" x-show="!yakin" @click="yakin = true">Hapus</button>
          <button type="submit" class="btn g s" x-show="yakin" x-cloak>Yakin?</button>
        </form>
      </div>
    </div>
  </div>
@empty
  <p class="mut">Belum ada kategori.</p>
@endforelse
@endsection
