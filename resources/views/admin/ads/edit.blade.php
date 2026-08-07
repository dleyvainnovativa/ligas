@extends('layouts.admin')

@section('title', 'Editar anuncio — PlayWinners')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.ads.index') }}" class="text-muted small text-decoration-none">
        <i class="fa-solid fa-arrow-left me-1"></i> Anuncios
    </a>
    <h1 class="mb-0 mt-2">Editar anuncio</h1>
</div>

<div class="card-soft p-4" style="max-width:640px;">
    <form method="POST" action="{{ route('admin.ads.update', $ad) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('admin.ads._form', ['ad' => $ad, 'leagues' => $leagues])
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk me-1"></i> Guardar cambios
        </button>
    </form>
</div>
@endsection
