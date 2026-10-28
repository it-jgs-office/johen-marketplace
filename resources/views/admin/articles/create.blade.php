@extends('admin.layouts.app')

@section('title', 'Tambah Artikel')

@section('content')
<div class="max-w-6xl mx-auto">
    <a href="{{ route('admin.articles.index') }}" class="inline-flex items-center gap-2 mb-4" style="color:var(--accent)">
        <i class="fas fa-arrow-left"></i> Kembali ke Artikel
    </a>
    <form method="POST" action="{{ route('admin.articles.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.articles.form', ['submitLabel' => 'Simpan Artikel'])
    </form>
</div>
@endsection
