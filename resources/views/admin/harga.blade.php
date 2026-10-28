@extends('layouts.app')
@section('isi')
<div style="margin-bottom:12px">
  <a href="{{ route('admin.kategori.index') }}" class="t">Kelola kategori</a>
  <a href="{{ route('admin.ukuran.index') }}" class="t">Kelola ukuran</a>
</div>

<p class="mut">Ubah harga lalu tekan Simpan. Semua berubah atau tidak sama sekali.</p>

<form method="POST" action="{{ route('admin.harga.update') }}">
  @csrf @method('PUT')
  @foreach($kategori as $c)
    <h2>{{ $c->nama }}</h2>
    @foreach($c->sizes as $s)
      <div class="row">
        <div class="sz">{{ $s->nama }}</div>
        <div class="pr">
          <input type="number" min="0" inputmode="numeric" name="harga[{{ $s->id }}]"
                 value="{{ old('harga.'.$s->id, $s->harga) }}" aria-label="Harga {{ $s->nama }}">
        </div>
        <label class="sw">
          <input type="hidden" name="aktif[{{ $s->id }}]" value="0">
          <input type="checkbox" name="aktif[{{ $s->id }}]" value="1" @checked($s->is_active)> aktif
        </label>
        <a href="{{ route('admin.ukuran.riwayat', $s) }}" class="btn g s" style="font-size:.7rem">Riwayat</a>
      </div>
    @endforeach
  @endforeach
  <div class="aksi"><button class="btn">Simpan semua harga</button></div>
</form>

<h2>Ubah massal per kategori</h2>
@foreach($kategori as $c)
  <form method="POST" action="{{ route('admin.harga.massal') }}" class="bt" style="margin-bottom:8px">
    @csrf
    <input type="hidden" name="category_id" value="{{ $c->id }}">
    <input type="number" name="selisih" inputmode="numeric" placeholder="{{ $c->nama }}: selisih, contoh 2000 atau -1500" aria-label="Selisih harga {{ $c->nama }}">
    <button class="btn g s">Ubah massal</button>
  </form>
@endforeach

<h2>Riwayat perubahan harga</h2>
@forelse($logs as $l)
  <div class="card mut">{{ $l->size->category->nama }} {{ $l->size->nama }}: Rp {{ number_format($l->harga_lama, 0, ',', '.') }} → Rp {{ number_format($l->harga_baru, 0, ',', '.') }} · {{ \Illuminate\Support\Carbon::parse($l->diubah_pada)->format('Y-m-d H:i') }}</div>
@empty
  <p class="mut">Belum ada perubahan.</p>
@endforelse
@endsection
