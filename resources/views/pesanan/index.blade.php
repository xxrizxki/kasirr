@extends('layouts.app')
@section('isi')
<form method="GET" class="card" style="margin-bottom:12px">
  <div class="bt">
    <input type="date" name="dari" value="{{ request('dari') }}" aria-label="Dari tanggal" placeholder="Dari">
    <input type="date" name="sampai" value="{{ request('sampai') }}" aria-label="Sampai tanggal" placeholder="Sampai">
    <select name="status" aria-label="Filter status">
      <option value="">Semua status</option>
      <option value="selesai" @selected(request('status') === 'selesai')>Selesai</option>
      <option value="batal" @selected(request('status') === 'batal')>Batal</option>
    </select>
    <button type="submit" class="btn s">Filter</button>
  </div>
</form>

@forelse($orders as $o)
  <div class="card" x-data="{ buka: false, yakin: false }">
    <div class="h" @click="buka = !buka">
      <div>
        <b>{{ $o->kode_pesanan }}</b>
        <div class="mut">{{ $o->created_at->format('Y-m-d') }} · {{ $o->nama_pembeli ?: 'tanpa nama' }} · {{ $o->total_item }} potong</div>
      </div>
      <div style="text-align:right">
        <b>{{ $o->total_rupiah }}</b>
        <div><span class="badge {{ $o->isSelesai() ? 'ok' : 'no' }}">{{ $o->isSelesai() ? 'selesai' : 'batal' }}</span></div>
      </div>
    </div>
    <div class="det" x-show="buka" x-cloak>
      @foreach($o->items as $i)
        <div><span>{{ $i->size->category->nama }} {{ $i->size->nama }} × {{ $i->qty }}</span><span>Rp {{ number_format($i->subtotal, 0, ',', '.') }}</span></div>
      @endforeach
      <div style="margin-top:8px; display:flex; gap:8px">
        <a href="{{ route('pesanan.show', $o) }}" class="btn g s">Buka detail</a>
        @if($o->isSelesai())
          <form method="POST" action="{{ route('pesanan.batal', $o) }}" style="display:flex">
            @csrf
            <button type="button" class="btn g s" x-show="!yakin" @click="yakin = true">Batalkan pesanan</button>
            <button type="submit" class="btn g s" x-show="yakin" x-cloak>Yakin batalkan?</button>
          </form>
        @endif
      </div>
    </div>
  </div>
@empty
  <p class="mut">Belum ada pesanan.</p>
@endforelse
{{ $orders->links('vendor.pagination.gamis') }}
@endsection
