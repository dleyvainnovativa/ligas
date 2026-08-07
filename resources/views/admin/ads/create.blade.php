@extends('layouts.admin')

@section('title', 'Nuevo anuncio — PlayWinners')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.ads.index') }}" class="text-muted small text-decoration-none">
        <i class="fa-solid fa-arrow-left me-1"></i> Anuncios
    </a>
    <h1 class="mb-0 mt-2">Nuevo anuncio</h1>
</div>

<div class="card-soft p-4" style="max-width:640px;">
    <form method="POST" action="{{ route('admin.ads.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.ads._form', ['ad' => null, 'leagues' => $leagues])
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-plus me-1"></i> Crear anuncio
        </button>
    </form>
</div>
@endsection
