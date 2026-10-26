@extends('layouts.app')

@section('judul', 'Masuk · Hitung Gamis')

@section('isi')
<div style="max-width:400px; margin:0 auto">
  <div class="card" style="margin-top:40px">
    <h2 style="margin-top:0">Masuk</h2>
    
    @if($errors->has('email'))
      <div class="note" style="border-left-color:var(--red); color:var(--red)">
        {{ $errors->first('email') }}
      </div>
    @endif

    <form method="POST" action="{{ route('login') }}" style="display:flex; flex-direction:column; gap:12px">
      @csrf
      
      <div>
        <label style="display:block; font-size:.75rem; text-transform:uppercase; letter-spacing:.08em; color:var(--mute); margin-bottom:6px">
          Email
        </label>
        <input 
          type="email" 
          name="email" 
          value="{{ old('email') }}"
          placeholder="admin@example.com"
          required
          autofocus
        />
      </div>

      <div>
        <label style="display:block; font-size:.75rem; text-transform:uppercase; letter-spacing:.08em; color:var(--mute); margin-bottom:6px">
          Sandi
        </label>
        <input 
          type="password" 
          name="password" 
          placeholder="••••••"
          required
        />
      </div>

      <button type="submit" class="btn" style="margin-top:8px">Masuk</button>
    </form>
  </div>

  <p style="text-align:center; color:var(--mute); font-size:.85rem; margin-top:20px">
    Akun admin untuk akses pricing
  </p>
</div>
@endsection
