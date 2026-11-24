@php
    // Brand logo that swaps with the theme: the gold/white logo on dark, the
    // black logo on light. Both <img>s render; CSS shows the right one per
    // data-bs-theme (set pre-paint by partials.theme-init, so no flash).
    $logoBase = trim('brand-logo ' . ($class ?? ''));
    $dims = '';
    if (!empty($height)) $dims .= ' height="' . e($height) . '"';
    if (!empty($width))  $dims .= ' width="' . e($width) . '"';
    $logoAlt = $alt ?? 'PlayWinners';
@endphp
<img src="{{ asset('img/logo.png') }}" {!! $dims !!}
    class="{{ $logoBase }} brand-logo--dark" alt="{{ $logoAlt }}">
<img src="{{ asset('img/logo-light.png') }}" {!! $dims !!}
    class="{{ $logoBase }} brand-logo--light" alt="{{ $logoAlt }}">
