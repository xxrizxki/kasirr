@extends('layouts.app')
@section('isi')
<h2>Rekap penjualan harian</h2>
<div class="wrap"><table>
  <tr><th>Tanggal</th><th>Pesanan</th><th>Potong</th><th>Omzet</th></tr>
  @forelse($rekap as $r)
    <tr><td>{{ $r->tanggal }}</td><td>{{ $r->jumlah_pesanan }}</td><td>{{ $r->jumlah_potong }}</td><td>Rp {{ number_format($r->omzet, 0, ',', '.') }}</td></tr>
  @empty
    <tr><td colspan="4" style="text-align:center; color:var(--mute)">Belum ada data</td></tr>
  @endforelse
  <tr><th>Total</th><th>{{ collect($rekap)->sum('jumlah_pesanan') }}</th><th>{{ collect($rekap)->sum('jumlah_potong') }}</th><th>Rp {{ number_format(collect($rekap)->sum('omzet'), 0, ',', '.') }}</th></tr>
</table></div>
<p class="mut">Pesanan berstatus batal tidak dihitung.</p>
@endsection
