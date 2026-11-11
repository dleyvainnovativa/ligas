@php

$logoBase = trim('brand-logo ' . ($class ?? ''));
$dims = '';
if (!empty($height)) $dims .= ' height="' . e($height) . '"';
if (!empty($width)) $dims .= ' width="' . e($width) . '"';
$logoAlt = $alt ?? 'PlayWinners';

@endphp
<img src="{{ asset('img/logo.png') }}" {!! $dims !!}
    class="{{ $logoBase }} brand-logo--dark" alt="{{ $logoAlt }}">
<img src="{{ asset('img/logo-light.png') }}" {!! $dims !!}
    class="{{ $logoBase }} brand-logo--light" alt="{{ $logoAlt }}">