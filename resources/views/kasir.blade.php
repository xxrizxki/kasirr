@extends('layouts.app')
@section('isi')
<div x-data="kasir(@js($kategori))" x-cloak>
  <nav aria-label="Kategori">
    <template x-for="c in kategori" :key="c.id">
      <button type="button" class="t" :aria-pressed="aktif === c.id" @click="aktif = c.id">
        <span x-text="c.nama"></span><small x-text="jumlahKategori(c.id)"></small>
      </button>
    </template>
  </nav>

  <template x-for="s in ukuranAktif" :key="s.id">
    <div class="row" :class="{ on: (qty[s.id] || 0) > 0 }">
      <div class="sz" x-text="s.nama"></div>
      <div class="pr" x-text="rupiah(s.harga)"></div>
      <div class="st">
        <button type="button" :aria-label="'Kurangi ' + s.nama" @click="kurang(s.id)">−</button>
        <b x-text="qty[s.id] || 0"></b>
        <button type="button" class="p" :aria-label="'Tambah ' + s.nama" @click="tambah(s.id)">+</button>
      </div>
    </div>
  </template>
</div>
@endsection

@section('bar')
<div class="bar"><div>
  <div class="sub">
    <template x-for="c in kategoriTerpilih" :key="c.id">
      <span x-text="c.nama + ': ' + c.jumlah"></span>
    </template>
  </div>
  <div class="tot"><span class="mut" x-text="totalItem + ' potong'"></span><strong x-text="rupiah(totalHarga)"></strong></div>
  <div class="bt">
    <input type="text" x-model="nama" maxlength="100" placeholder="Nama pembeli (opsional)" aria-label="Nama pembeli">
    <button type="button" class="btn" :disabled="totalItem === 0 || mengirim" @click="simpan()">Simpan</button>
    <button type="button" class="btn g" @click="kosongkan()">Kosongkan</button>
  </div>
</div></div>

  <div class="toast" x-show="pesan" x-text="pesan" x-cloak></div>
</div>
@endsection

@push('head')
<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('kasir', (kategori) => ({
    kategori, aktif: kategori.length ? kategori[0].id : null,
    qty: {}, nama: '', mengirim: false, pesan: '',
    ukuran(id) { return this.kategori.flatMap(c => c.sizes).find(s => s.id === id); },
    get ukuranAktif() { const c = this.kategori.find(k => k.id === this.aktif); return c ? c.sizes : []; },
    jumlahKategori(cid) { return this.kategori.find(c => c.id === cid).sizes.reduce((a, s) => a + (this.qty[s.id] || 0), 0); },
    get totalItem() { return Object.values(this.qty).reduce((a, b) => a + b, 0); },
    get totalHarga() { return Object.entries(this.qty).reduce((a, [id, q]) => a + q * this.ukuran(+id).harga, 0); },
    get kategoriTerpilih() {
      return this.kategori.map(c => ({ id: c.id, nama: c.nama, jumlah: this.jumlahKategori(c.id) })).filter(c => c.jumlah > 0);
    },
    tambah(id) { this.qty[id] = (this.qty[id] || 0) + 1; },
    kurang(id) { if ((this.qty[id] || 0) > 0) this.qty[id]--; },
    kosongkan() { 
      this.qty = {}; 
      this.nama = '';
    },
    rupiah(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); },
    tampil(m) { this.pesan = m; setTimeout(() => this.pesan = '', 2800); },
    async simpan() {
      if (this.totalItem === 0 || this.mengirim) return;
      this.mengirim = true;
      try {
        const r = await fetch('/pesanan', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json', 'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
          },
          body: JSON.stringify({
            nama_pembeli: this.nama || null,
            items: Object.entries(this.qty).filter(([, q]) => q > 0).map(([id, q]) => ({ size_id: +id, qty: q })),
          }),
        });
        const d = await r.json();
        if (r.ok && d && d.ok) {
          this.tampil('Tersimpan: ' + d.kode + ' · ' + this.rupiah(d.total));
          this.qty = {}; 
          this.nama = '';
        } else if (r.status === 422 && d && d.errors) {
          this.tampil('Validasi gagal: ' + Object.values(d.errors).flat().join('; '));
        } else {
          this.tampil((d && (d.pesan || d.message)) || 'Gagal menyimpan pesanan (HTTP ' + r.status + ')');
        }
      } catch (e) {
        this.tampil('Error: ' + (e.message || 'Koneksi bermasalah'));
      } finally {
        this.mengirim = false;
      }
    },
  }));
});
</script>
@endpush
