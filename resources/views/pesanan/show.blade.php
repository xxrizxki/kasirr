@extends('layouts.app')
@section('isi')
<div class="card" x-data="{ yakin: false }">
  <div class="h">
    <div>
      <b>{{ $order->kode_pesanan }}</b>
      <div class="mut">{{ $order->created_at->format('Y-m-d') }} · {{ $order->nama_pembeli ?: 'tanpa nama' }} · {{ $order->total_item }} potong</div>
    </div>
    <div style="text-align:right">
      <b>{{ $order->total_rupiah }}</b>
      <div><span class="badge {{ $order->isSelesai() ? 'ok' : 'no' }}">{{ $order->isSelesai() ? 'selesai' : 'batal' }}</span></div>
    </div>
  </div>
  <div class="det">
    @foreach($order->items as $i)
      <div><span>{{ $i->size->category->nama }} {{ $i->size->nama }} × {{ $i->qty }}</span><span>Rp {{ number_format($i->subtotal, 0, ',', '.') }}</span></div>
    @endforeach
    @if($order->catatan)
      <div style="margin-top:12px;padding-top:8px;border-top:1px solid #444"><span>Catatan</span><span>{{ $order->catatan }}</span></div>
    @endif
    <div style="margin-top:8px;padding-top:8px;border-top:1px solid #444"><span><b>Total</b></span><span><b>{{ $order->total_rupiah }}</b></span></div>
    @if($order->isSelesai())
      <form method="POST" action="{{ route('pesanan.batal', $order) }}" style="margin-top:12px">
        @csrf
        <input type="hidden" name="alasan">
        <button type="button" class="btn g s" x-show="!yakin" @click="yakin = true">Batalkan pesanan</button>
        <button type="submit" class="btn g s" x-show="yakin" x-cloak>Yakin batalkan?</button>
      </form>
    @endif
  </div>
</div>
@endsection
