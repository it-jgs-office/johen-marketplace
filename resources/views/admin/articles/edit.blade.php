@extends('admin.layouts.app')

@section('title', 'Edit Artikel')

@section('content')
<div class="max-w-6xl mx-auto">
    <a href="{{ route('admin.articles.index') }}" class="inline-flex items-center gap-2 mb-4" style="color:var(--accent)">
        <i class="fas fa-arrow-left"></i> Kembali ke Artikel
    </a>
    <form method="POST" action="{{ route('admin.articles.update', $article) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.articles.form', ['submitLabel' => 'Simpan Perubahan'])
    </form>
</div>
@endsection
