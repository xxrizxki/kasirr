@extends('layouts.app')
@section('isi')
<form method="POST" action="{{ route('admin.kategori.store') }}" class="card">
  @csrf
  <label class="fld"><span>Kode</span>
    <input type="text" name="kode" value="{{ old('kode') }}" maxlength="20" required>
    @error('kode')<div class="err">{{ $message }}</div>@enderror
  </label>
  <label class="fld"><span>Nama</span>
    <input type="text" name="nama" value="{{ old('nama') }}" maxlength="50" required>
    @error('nama')<div class="err">{{ $message }}</div>@enderror
  </label>
  <label class="fld"><span>Urutan</span>
    <input type="number" name="urutan" value="{{ old('urutan', 0) }}" min="0" max="255">
    @error('urutan')<div class="err">{{ $message }}</div>@enderror
  </label>
  <div class="aksi">
    <button type="submit" class="btn">Simpan</button>
    <a href="{{ route('admin.kategori.index') }}" class="btn g">Batal</a>
  </div>
</form>
@endsection
