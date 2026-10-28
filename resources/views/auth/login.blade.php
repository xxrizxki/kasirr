@extends('layouts.app')

@section('judul', 'Masuk · Hitung Gamis')

@section('isi')
<div style="max-width:400px; margin:0 auto">
  <div class="card" style="margin-top:40px">
    <h2 style="margin-top:0">Masuk</h2>
    
    @if($errors->has('email'))
      <div class="err">{{ $errors->first('email') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
      @csrf
      
      <label class="fld"><span>Email</span>
        <input type="email" name="email" value="{{ old('email') }}" placeholder="admin@example.com" required autofocus>
        @error('email')<div class="err">{{ $message }}</div>@enderror
      </label>

      <label class="fld"><span>Sandi</span>
        <input type="password" name="password" placeholder="••••••" required>
        @error('password')<div class="err">{{ $message }}</div>@enderror
      </label>

      <div class="aksi">
        <button type="submit" class="btn">Masuk</button>
      </div>
    </form>
  </div>

  <p style="text-align:center; color:var(--mute); font-size:.85rem; margin-top:20px">
    Akun admin untuk akses pricing
  </p>
</div>
@endsection
