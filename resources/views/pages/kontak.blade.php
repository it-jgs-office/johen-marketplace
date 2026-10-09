@extends('layouts.topup')

@section('title', 'Hubungi Kami - ' . config('app.name'))

@section('content')
<div class="kontak-page">
  <div class="kontak-hero">
    <h1>Kontak Kami</h1>
    <p>Ada pertanyaan, keluhan, atau butuh bantuan? Tim customer service siap membantu anda.</p>
  </div>

  <div class="kontak-wrap">
    <form class="kontak-form" method="POST" action="{{ route('kontak') }}">
      @csrf
      <div class="kontak-form-header">
        <div class="kontak-form-info">
          <h3>Kirim Pesan</h3>
          <p class="kontak-info-label">Isi form di bawah, tim kami akan membalas lewat email atau WhatsApp.</p>
        </div>
      </div>
      <div class="kontak-grid">
        <div class="kontak-field">
          <label for="name">Nama Lengkap</label>
          <input type="text" id="name" name="name" placeholder="Masukan nama lengkap anda" value="{{ old('name') }}">
          @error('name') <span class="kontak-error">{{ $message }}</span> @enderror
        </div>
        <div class="kontak-field">
          <label for="email">Alamat Email</label>
          <input type="email" id="email" name="email" placeholder="Masukan alamat email anda" value="{{ old('email') }}">
          @error('email') <span class="kontak-error">{{ $message }}</span> @enderror
        </div>
      </div>
      <div class="kontak-grid">
        <div class="kontak-field">
          <label for="phone">Nomor Telepon</label>
          <input type="tel" id="phone" name="phone" placeholder="Masukan nomor telepon anda" value="{{ old('phone') }}">
          @error('phone') <span class="kontak-error">{{ $message }}</span> @enderror
        </div>
        <div class="kontak-field">
          <label for="category">Kategori</label>
          <select id="category" name="category">
            <option value="">Pilih kategori</option>
            <option value="topup" {{ old('category') === 'topup' ? 'selected' : '' }}>Top Up</option>
            <option value="jual-beli-akun" {{ old('category') === 'jual-beli-akun' ? 'selected' : '' }}>Jual Beli Akun</option>
            <option value="pembayaran" {{ old('category') === 'pembayaran' ? 'selected' : '' }}>Pembayaran</option>
            <option value="keluhan" {{ old('category') === 'keluhan' ? 'selected' : '' }}>Keluhan</option>
            <option value="saran" {{ old('category') === 'saran' ? 'selected' : '' }}>Saran</option>
            <option value="lainnya" {{ old('category') === 'lainnya' ? 'selected' : '' }}>Lainnya</option>
          </select>
          @error('category') <span class="kontak-error">{{ $message }}</span> @enderror
        </div>
      </div>
      <div class="kontak-field">
        <label for="message">Pesan atau Keluhan</label>
        <textarea id="message" name="message" rows="3" placeholder="Masukan pesan atau keluhan anda secara detail pada kami.">{{ old('message') }}</textarea>
        @error('message') <span class="kontak-error">{{ $message }}</span> @enderror
      </div>
      <button type="submit" class="kontak-btn">Kirim Pesan</button>
    </form>
  </div>
</div>

<style>
.kontak-page {
  width: 100%;
  max-width: var(--layout-max);
  margin: 0 auto;
  padding: 2rem var(--layout-gutter);
  min-height: calc(100vh - 140px);
  display: flex;
  flex-direction: column;
  justify-content: center;
}
.kontak-hero {
  text-align: center;
  margin-bottom: 1.8rem;
}
.kontak-hero h1 {
  font-size: 1.7rem;
  font-weight: 800;
  margin-bottom: .4rem;
}
.kontak-hero p {
  color: var(--text-dim);
  font-size: .95rem;
  max-width: 500px;
  margin: 0 auto;
}
.kontak-wrap {
  max-width: 800px;
  margin: 0 auto;
}
.kontak-form {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 1.8rem;
}
.kontak-form-header {
  display: flex;
  align-items: center;
  gap: 1.2rem;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--border);
}
.kontak-form-info h3 {
  font-size: 1.1rem;
  font-weight: 700;
  margin-bottom: .1rem;
}
.kontak-info-label {
  font-size: .85rem;
  color: var(--text-dim);
  margin-bottom: .5rem;
}
.kontak-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}
.kontak-grid .kontak-field {
  margin-bottom: 0;
}
.kontak-field {
  margin-bottom: 1rem;
}
.kontak-field label {
  display: block;
  font-size: .9rem;
  font-weight: 600;
  margin-bottom: .35rem;
}
.kontak-field input,
.kontak-field select,
.kontak-field textarea {
  width: 100%;
  padding: .65rem .85rem;
  border-radius: 8px;
  border: 1px solid var(--border);
  background: var(--bg-soft);
  color: var(--text);
  font-size: .92rem;
  font-family: inherit;
  transition: border-color .2s;
}
.kontak-field input::placeholder,
.kontak-field textarea::placeholder {
  font-size: .82rem;
}
.kontak-field select option:first-child {
  font-size: .82rem;
}
.kontak-field input:focus,
.kontak-field select:focus,
.kontak-field textarea:focus {
  outline: none;
  border-color: var(--purple-light);
}
.kontak-field textarea {
  resize: none;
  min-height: 80px;
}
.kontak-error {
  display: block;
  color: #ef4444;
  font-size: .8rem;
  margin-top: .25rem;
}
.kontak-btn {
  width: 100%;
  padding: .75rem 1.5rem;
  border-radius: 10px;
  border: none;
  background: var(--purple-light);
  color: #fff;
  font-weight: 700;
  font-size: .95rem;
  cursor: pointer;
  transition: opacity .2s;
  font-family: inherit;
}
.kontak-btn:hover {
  opacity: .9;
}
@media (max-width: 640px) {
  .kontak-page {
    padding: 1.5rem var(--layout-gutter);
  }
  .kontak-grid {
    grid-template-columns: 1fr;
  }
}
</style>
@endsection
