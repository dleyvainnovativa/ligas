@php
// PadelWinners social accounts — update these URLs
$socials = [
['icon' => 'fa-instagram', 'url' => 'https://instagram.com/playwinnersligas', 'label' => 'Instagram'],
['icon' => 'fa-facebook', 'url' => 'https://www.facebook.com/p/Ligas-Padel-61591702947010/', 'label' => 'Facebook'],
];
$variant = $variant ?? 'footer'; // 'header' or 'footer'
@endphp

<div class="social-links social-links--{{ $variant }}">
    @foreach ($socials as $s)
    <a href="{{ $s['url'] }}" target="_blank" rel="noopener noreferrer"
        class="social-link" title="{{ $s['label'] }}" aria-label="{{ $s['label'] }}">
        <i class="fa-brands {{ $s['icon'] }}"></i>
    </a>
    @endforeach
</div>