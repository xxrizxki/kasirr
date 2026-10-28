@extends('layouts.app')
@section('isi')
<form method="POST" action="{{ route('admin.ukuran.store') }}" class="card">
  @csrf
  <label class="fld"><span>Kategori</span>
    <select name="category_id" required>
      <option value="">Pilih kategori</option>
      @foreach($kategori as $c)
        <option value="{{ $c->id }}" @selected(old('category_id') == $c->id)>{{ $c->nama }}</option>
      @endforeach
    </select>
    @error('category_id')<div class="err">{{ $message }}</div>@enderror
  </label>
  <label class="fld"><span>Nama ukuran</span>
    <input type="text" name="nama" value="{{ old('nama') }}" maxlength="20" required>
    @error('nama')<div class="err">{{ $message }}</div>@enderror
  </label>
  <label class="fld"><span>Harga</span>
    <input type="number" name="harga" value="{{ old('harga') }}" min="0" inputmode="numeric" required>
    @error('harga')<div class="err">{{ $message }}</div>@enderror
  </label>
  <label class="fld"><span>Urutan</span>
    <input type="number" name="urutan" value="{{ old('urutan', 0) }}" min="0" max="255">
    @error('urutan')<div class="err">{{ $message }}</div>@enderror
  </label>
  <label class="fld">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
    <span style="display:inline">Aktif</span>
    @error('is_active')<div class="err">{{ $message }}</div>@enderror
  </label>
  <div class="aksi">
    <button type="submit" class="btn">Simpan</button>
    <a href="{{ route('admin.ukuran.index') }}" class="btn g">Batal</a>
  </div>
</form>
@endsection
