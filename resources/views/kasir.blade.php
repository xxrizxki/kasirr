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
    <button type="button" class="btn" :disabled="totalItem === 0 || mengirim" @click="preview()">Simpan</button>
    <button type="button" class="btn g" @click="kosongkan()">Kosongkan</button>
  </div>
</div></div>

  <div class="toast" x-show="pesan" x-text="pesan" x-cloak></div>

  <!-- Modal Preview -->
  <div x-show="showPreview" @click.self="batalkanPreview()" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);z-index:1000;display:flex;align-items:center;justify-content:center;">
    <div style="background:#0f1829;border:1px solid #2fd8e6;border-radius:4px;padding:24px;max-width:500px;max-height:80vh;overflow:auto;box-shadow:0 0 20px rgba(47,216,230,0.2);">
      <h2 style="color:#2fd8e6;font-size:1.4rem;margin:0 0 16px;font-family:Share Tech Mono;">Preview Pesanan</h2>
      
      <div style="margin-bottom:16px;color:#dbe6f5;">
        <div x-show="nama" style="margin-bottom:12px;"><strong>Pembeli:</strong> <span x-text="nama"></span></div>
        
        <div style="margin-bottom:12px;"><strong>Rincian:</strong></div>
        <div style="margin-left:12px;border-left:2px solid #ff2a4d;padding-left:12px;">
          <template x-for="item in previewItems" :key="item.size_id">
            <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-family:Share Tech Mono;font-size:0.9rem;">
              <span><span x-text="item.nama"></span> × <span x-text="item.qty"></span></span>
              <span><span x-text="rupiah(item.harga)"></span> = <strong x-text="rupiah(item.subtotal)"></strong></span>
            </div>
          </template>
        </div>
        
        <div style="margin-top:12px;border-top:1px solid #ff2a4d;padding-top:12px;font-size:1.1rem;font-family:Share Tech Mono;">
          <span x-text="totalItem + ' potong'"></span> · <strong x-text="rupiah(totalHarga)"></strong>
        </div>
      </div>

      <div style="display:flex;gap:12px;margin-top:24px;">
        <button type="button" class="btn g" style="flex:1;" @click="batalkanPreview()" :disabled="mengirim">Kembali</button>
        <button type="button" class="btn" style="flex:1;" @click="confirmSimpan()" :disabled="mengirim" x-text="mengirim ? 'Menyimpan...' : 'Konfirmasi & Simpan'"></button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('head')
<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('kasir', (kategori) => ({
    kategori, aktif: kategori.length ? kategori[0].id : null,
    qty: {}, nama: '', mengirim: false, pesan: '', showPreview: false,
    ukuran(id) { return this.kategori.flatMap(c => c.sizes).find(s => s.id === id); },
    get ukuranAktif() { const c = this.kategori.find(k => k.id === this.aktif); return c ? c.sizes : []; },
    jumlahKategori(cid) { return this.kategori.find(c => c.id === cid).sizes.reduce((a, s) => a + (this.qty[s.id] || 0), 0); },
    get totalItem() { return Object.values(this.qty).reduce((a, b) => a + b, 0); },
    get totalHarga() { return Object.entries(this.qty).reduce((a, [id, q]) => a + q * this.ukuran(+id).harga, 0); },
    get kategoriTerpilih() {
      return this.kategori.map(c => ({ id: c.id, nama: c.nama, jumlah: this.jumlahKategori(c.id) })).filter(c => c.jumlah > 0);
    },
    get previewItems() {
      return Object.entries(this.qty)
        .filter(([, q]) => q > 0)
        .map(([id, q]) => {
          const sz = this.ukuran(+id);
          return { size_id: +id, nama: sz.nama, qty: q, harga: sz.harga, subtotal: q * sz.harga };
        });
    },
    tambah(id) { this.qty[id] = (this.qty[id] || 0) + 1; },
    kurang(id) { if ((this.qty[id] || 0) > 0) this.qty[id]--; },
    kosongkan() { 
      if (confirm('Kosongkan semua pilihan?')) {
        this.qty = {}; 
        this.nama = '';
        this.showPreview = false;
      }
    },
    preview() {
      if (this.totalItem === 0) return;
      this.showPreview = true;
    },
    batalkanPreview() {
      this.showPreview = false;
    },
    rupiah(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); },
    tampil(m) { this.pesan = m; setTimeout(() => this.pesan = '', 2800); },
    async confirmSimpan() {
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
            items: this.previewItems.map(({ size_id, qty }) => ({ size_id, qty })),
          }),
        });
        const d = await r.json();
        if (r.ok && d.ok) {
          this.tampil('✓ Tersimpan: ' + d.kode + ' · ' + this.rupiah(d.total));
          this.qty = {}; 
          this.nama = '';
          this.showPreview = false;
        } else {
          this.tampil('✗ ' + (d.pesan || d.message || 'Gagal menyimpan pesanan'));
          this.showPreview = false;
        }
      } catch (e) {
        this.tampil('✗ Koneksi bermasalah, coba lagi');
      } finally {
        this.mengirim = false;
      }
    },
  }));
});
</script>
@endpush
