@extends('layouts.app')
@section('isi')
<form method="POST" action="{{ route('admin.ukuran.update', $size) }}" class="card">
  @csrf @method('PUT')
  <label class="fld"><span>Kategori</span>
    <select name="category_id" required>
      @foreach($kategori as $c)
        <option value="{{ $c->id }}" @selected(old('category_id', $size->category_id) == $c->id)>{{ $c->nama }}</option>
      @endforeach
    </select>
    @error('category_id')<div class="err">{{ $message }}</div>@enderror
  </label>
  <label class="fld"><span>Nama ukuran</span>
    <input type="text" name="nama" value="{{ old('nama', $size->nama) }}" maxlength="20" required>
    @error('nama')<div class="err">{{ $message }}</div>@enderror
  </label>
  <label class="fld"><span>Harga</span>
    <input type="number" name="harga" value="{{ old('harga', $size->harga) }}" min="0" inputmode="numeric" required>
    @error('harga')<div class="err">{{ $message }}</div>@enderror
  </label>
  <label class="fld"><span>Urutan</span>
    <input type="number" name="urutan" value="{{ old('urutan', $size->urutan) }}" min="0" max="255">
    @error('urutan')<div class="err">{{ $message }}</div>@enderror
  </label>
  <label class="fld">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $size->is_active))>
    <span style="display:inline">Aktif</span>
    @error('is_active')<div class="err">{{ $message }}</div>@enderror
  </label>
  <div class="aksi">
    <button type="submit" class="btn">Simpan</button>
    <a href="{{ route('admin.ukuran.index') }}" class="btn g">Batal</a>
  </div>
</form>
@endsection
